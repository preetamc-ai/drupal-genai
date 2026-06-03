<?php

namespace Drupal\ai_translate_plus\Plugin\AiProvider;

use Drupal\ai\Entity\AiPrompt;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\ai\OperationType\TranslateText\TranslateTextInput;
use Drupal\ai\OperationType\TranslateText\TranslateTextOutput;
use Drupal\ai\Plugin\ProviderProxy;
use Drupal\ai_translate\Plugin\AiProvider\ChatTranslationProvider;
use Drupal\ai_translate_plus\Service\TranslationContextService;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\Token;
use Drupal\ai\Attribute\AiProvider;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Enhanced ChatTranslationProvider with support for custom prompts and models.
 *
 * Things are configurable per entity/bundle/language.
 */
#[AiProvider(
  id: 'chat_translation_plus',
  label: new TranslatableMarkup('Chat proxy to LLM (using Plus Settings)'),
)]
final class ChatTranslationPlusProvider extends ChatTranslationProvider {

  private Token $token;

  private TranslationContextService $contextService;

  private  string $provider_id;

  private string $model_id;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->token = $container->get('token');
    $instance->contextService = $container->get('ai_translate_plus.translation_context');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function translateText(TranslateTextInput $input, string $model_id, array $options = []): TranslateTextOutput {
    $text = $input->getText();

    // We can guess source, but not target language.
    /** @var \Drupal\language\Entity\ConfigurableLanguage $targetLanguage */
    $targetLanguage = $this->entityTypeManager->getStorage('configurable_language')->load($input->getTargetLanguage());
    if (!$targetLanguage) {
      $this->loggerFactory->get('ai_translate')->warning(
        $this->t('Unable to guess target language, code @langcode', [
          '@langcode' => $input->getTargetLanguage(),
        ])
      );
      return new TranslateTextOutput('', '', '');
    }

    $lang_to = $targetLanguage->getId();

    // Get custom provider/model configuration if available
    $custom_model = $this->getCustomModel($lang_to);

    // Use custom provider if configured, otherwise fall back to the passed model
    if ($custom_model) {
      try {
        $this->provider_id = $this->manager->loadProviderFromSimpleOption($custom_model)->getPluginId();
        $this->model_id = $this->manager->getModelNameFromSimpleOption($custom_model);
      }
      catch (\Throwable $e) {
        // If custom provider fails, fall back to default
        $this->loggerFactory->get('ai_translate_plus')->warning(
          'Failed to load custom provider @provider: @error. Falling back to default.',
          ['@provider' => $custom_model, '@error' => $e->getMessage()]
        );
        $this->provider_id = '';
        $this->model_id = $model_id;
      }
    }
    else {
      $this->provider_id = '';
      $this->model_id = $model_id;
    }

    // Get the prompt - either custom or default
    $prompt = $this->getPromptForTranslation($lang_to);

    // Define replacement variables.
    $twigContext = [];
    $replacements = [
      '{destLang}' => $targetLanguage->getId(),
      '{destLangName}' => $targetLanguage->getName(),
      '{inputText}' => $text,
    ];
    try {
      /** @var \Drupal\language\Entity\ConfigurableLanguage $sourceLanguage */
      $sourceLanguage = $this->entityTypeManager->getStorage('configurable_language')->load($input->getSourceLanguage());
      if ($sourceLanguage) {
        $twigContext['sourceLang'] = $sourceLanguage->getId();
        $replacements['{sourceLang}'] = $sourceLanguage->getId();
        $twigContext['sourceLangName'] = $sourceLanguage->getName();
        $replacements['{sourceLangName}'] = $sourceLanguage->getName();
      }
    }
      // Ignore failure to load source language.
    catch (\AssertionError) {
    }

    // Enhance Twig context with the current entity, keyed by its entity type.
    // The entity is not known directly here, so we load it from the context
    // captured earlier by the TextTranslatorDecorator.
    try {
      $plusContext = $this->contextService->getContext();
      $entity_type_id = $plusContext['entity_type_id'] ?? NULL;
      $entity_id = $plusContext['entity_id'] ?? NULL;

      if ($entity_type_id && $entity_id) {
        $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
        if ($entity) {
          $twigContext[$entity_type_id] = $entity;
        }
      }
    }
    catch (\Throwable $e) {
    }
    // Only use Twig for conditional logic. We only need source lang Twig
    // variables for this use case. Other variables are replaced via AI prompts
    // replacement logic.
    $promptText = (string) $this->twig->renderInline($prompt, $twigContext);
    $promptText = strtr($promptText, $replacements);
    try {
      $messages = new ChatInput([
        new chatMessage('user', $promptText),
      ]);
      $messages->setSystemPrompt('You are a helpful translator.');

      $this->loadTranslator($messages);
      /** @var /Drupal\ai\OperationType\Chat\ChatOutput $message */
      $message = $this->realTranslator->chat($messages, $this->chatConfiguration['model_id'], ['ai_translate']);
    }
    catch (GuzzleException $exception) {
      // Error handling for the API call.
      $this->loggerFactory->get('ai_translate')
        ->warning($exception->getMessage());
      return new TranslateTextOutput('', '', '');
    }

    return new TranslateTextOutput($message->getNormalized()->getText(),
      $message->getRawOutput(), []);
  }

  /**
   * Get the appropriate prompt for translation.
   *
   * @param string $lang_to
   *   The target language code.
   *
   * @return string
   *   The prompt to use.
   */
  private function getPromptForTranslation(string $lang_to): string {
    // Check if we have ai_translate_plus context for custom prompts.
    if ($this->contextService->hasContext()) {
      $context = $this->contextService->getContext();

      $entity = NULL;

      // Load entity if we have entity_id.
      if (!empty($context['entity_id'])) {
        try {
          $entity = $this->entityTypeManager
            ->getStorage($context['entity_type_id'])
            ->load($context['entity_id']);
        }
        catch (\Throwable $e) {
          // Continue without entity if loading fails.
        }
      }

      $customPrompt = $this->getCustomPrompt(
        $context['entity_type_id'],
        $context['bundle'],
        $context['lang_to'],
        $entity
      );

      if ($customPrompt) {
        return $customPrompt;
      }
    }

    // Fall back to default ai_translate prompts.
    $aiConfig = $this->configFactory->get('ai_translate.settings');
    $prompt = $aiConfig->get($lang_to . '_prompt');
    if (empty($prompt)) {
      $prompt = $aiConfig->get('prompt');
    }

    return $prompt ?: 'Translate the following text to {{ dest_lang_name }}: {{ input_text }}';
  }

  /**
   * Get the appropriate model for translation.
   *
   * @param string $lang_to
   *   The target language code.
   *
   * Get custom provider configuration for translation.
   *
   * @param string $lang_to
   *   The target language code.
   *
   * @return string|null
   *   The provider configuration string, or NULL if none found.
   */
  private function getCustomModel(string $lang_to): ?string {
    // Check if we have ai_translate_plus context for custom provider configs
    if (!$this->contextService->hasContext()) {
      return NULL;
    }

    $context = $this->contextService->getContext();
    $entity_type_id = $context['entity_type_id'];
    $bundle = $context['bundle'];

    // Load the settings entity for this entity type
    try {
      $settings_storage = $this->entityTypeManager->getStorage('ai_translate_plus_settings');
      /** @var \Drupal\ai_translate_plus\Entity\AiTranslatePlusSettings $settings_entity */
      $settings_entity = $settings_storage->load($entity_type_id);

      if (!$settings_entity) {
        return NULL;
      }
    }
    catch (\Throwable $e) {
      return NULL;
    }

    // Check for provider in order of specificity: bundle+lang, bundle default, entity type+lang, entity type default
    $models = $settings_entity->getBundleModels();
    $bundle_default_models = $settings_entity->getBundleDefaultModels();
    $entity_type_models = $settings_entity->getEntityTypeModels();
    $entity_type_default_model = $settings_entity->getEntityTypeDefaultModel();

    $model = $models[$bundle][$lang_to] ?? '';
    if ($model === '' && isset($bundle_default_models[$bundle])) {
      $model = $bundle_default_models[$bundle] ?? '';
    }
    if ($model === '' && isset($entity_type_models[$lang_to])) {
      $model = $entity_type_models[$lang_to] ?? '';
    }
    if ($model === '' && $entity_type_default_model) {
      $model = $entity_type_default_model;
    }

    return is_string($model) && $model !== '' ? $model : NULL;
  }

  /**
   * Get custom prompt for entity type, bundle and language.
   */
  private function getCustomPrompt(string $entity_type_id, string $bundle, string $lang_to, $entity = NULL): ?string {
    // Load the settings entity for this entity type
    try {
      $settings_storage = $this->entityTypeManager->getStorage('ai_translate_plus_settings');
      /** @var \Drupal\ai_translate_plus\Entity\AiTranslatePlusSettings $settings_entity */
      $settings_entity = $settings_storage->load($entity_type_id);

      if (!$settings_entity) {
        return NULL;
      }
    }
    catch (\Throwable $e) {
      return NULL;
    }

    // Get all prompt data from the config entity
    $bundle_prompts = $settings_entity->getBundlePrompts();
    $bundle_default_prompts = $settings_entity->getBundleDefaultPrompts();
    $entity_type_prompts = $settings_entity->getEntityTypePrompts();
    $entity_type_default_prompt = $settings_entity->getEntityTypeDefaultPrompt();

    // Try bundle-specific prompt for language.
    $prompt_id = $bundle_prompts[$bundle][$lang_to] ?? '';

    // Fall back to bundle default.
    if ($prompt_id === '' && isset($bundle_default_prompts[$bundle])) {
      $prompt_id = $bundle_default_prompts[$bundle] ?? '';
    }

    // Fall back to entity type prompt for language.
    if ($prompt_id === '' && isset($entity_type_prompts[$lang_to])) {
      $prompt_id = $entity_type_prompts[$lang_to] ?? '';
    }

    // Fall back to entity type default.
    if ($prompt_id === '' && $entity_type_default_prompt) {
      $prompt_id = $entity_type_default_prompt;
    }

    if (is_string($prompt_id) && $prompt_id !== '') {
      if ($promptEntity = AiPrompt::load($prompt_id)) {
        $prompt = $promptEntity->getPrompt();
        if ($entity && str_contains($prompt, '[')) {
          try {
            $token_data = [$entity_type_id => $entity];
            $prompt = $this->token->replace($prompt, $token_data, ['clear' => TRUE]);
          } catch (\Throwable $e) {
            // Keep prompt on failure.
          }
        }
        return $prompt;
      }
    }

    return NULL;
  }

  /**
   * Load real translator and its configuration.
   *
   * @return \Drupal\ai\Plugin\ProviderProxy|null
   *   Real provider or NULL on failure.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function loadTranslator(ChatInput $messages) :? ProviderProxy {
    if (empty($this->provider_id)) {
      return parent::loadTranslator($messages);
    }

    $chatConfig = [
      'provider_id' => $this->provider_id,
      'model_id' => $this->model_id,
    ];

    // Allow other modules to take over.
    $this->moduleHandler->alter('ai_translate_translation', $messages,
      $chatConfig['provider_id'], $chatConfig['model_id']);

    $this->realTranslator = $this->manager->createInstance($chatConfig['provider_id'],
      $chatConfig);
    $this->chatConfiguration = $chatConfig;
    return $this->realTranslator;
  }

}
