<?php

namespace Drupal\ai_translate_plus\Form;

use Drupal\ai\Entity\AiPrompt;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\ai_translate\TextExtractorInterface;
use Drupal\ai\AiProviderPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure AI Translate Plus module.
 */
final class AiTranslatePlusSettingsForm extends EntityForm {

  use PromptTokenHelpTrait;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ModuleHandlerInterface $moduleHandler,
    RouteMatchInterface $routeMatch,
    private readonly EntityFieldManagerInterface $entityFieldManager,
    private readonly LanguageManagerInterface $languageManager,
    private readonly TwigEnvironment $twig,
    private readonly TextExtractorInterface $textExtractor,
    private readonly AiProviderPluginManager $aiProviderManager,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $moduleHandler;
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('current_route_match'),
      $container->get('entity_field.manager'),
      $container->get('language_manager'),
      $container->get('twig'),
      $container->get('ai_translate_plus.text_extractor'),
      $container->get('ai.provider'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ai_translate_plus_settings_' . $this->routeMatch->getParameter('entity_type_id') . '_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $entity_type_id = NULL): array {
    if (!$entity_type_id) {
      $form['error'] = [
        '#markup' => $this->t('No entity type specified.'),
      ];
      return $form;
    }

    // Validate entity type
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id, FALSE);
    if (!$entity_type || !($entity_type instanceof ContentEntityTypeInterface) || !$entity_type->isTranslatable() || !$entity_type->getBundleEntityType()) {
      $form['error'] = [
        '#markup' => $this->t('Invalid entity type: @type', ['@type' => $entity_type_id]),
      ];
      return $form;
    }

    // Load or create the settings entity
    $settings_storage = $this->entityTypeManager->getStorage('ai_translate_plus_settings');
    $settings_entity = $settings_storage->load($entity_type_id);

    if (!$settings_entity) {
      $settings_entity = $settings_storage->create([
        'id' => $entity_type_id,
        'entity_type' => $entity_type_id,
        'label' => (string) $entity_type->getLabel(),
      ]);
    }

    $this->setEntity($settings_entity);

    // Get available AI models
    $chat_models = [];
    try {
      $chat_models = $this->aiProviderManager->getSimpleProviderModelOptions('chat');
      if (isset($chat_models[''])) {
        unset($chat_models['']);
      }
    } catch (\Exception $e) {
      \Drupal::logger('ai_translate_plus')->warning('Failed to load AI models: @error', ['@error' => $e->getMessage()]);
    }

    $promptStorage = $this->entityTypeManager->getStorage('ai_prompt');
    $prompts = $promptStorage->loadByProperties(['type' => 'ai_translate']);
    foreach ($prompts as $prompt) {
      $prompt_options[$prompt->id()] = $prompt->label();
    }

    $form['#tree'] = TRUE;

    $entity_label = (string) $entity_type->getLabel();

    $form['description'] = [
      '#markup' => $this->t('Configure AI Translate Plus settings for @label (@id).', [
        '@label' => $entity_label,
        '@id' => $entity_type_id,
      ]),
    ];

    $languages = $this->languageManager->getLanguages();
    $prompt_description = $this->t('Uses the Twig renderer. Available variables include: {sourceLang}, {sourceLangName}, {destLang}, {destLangName }}, {{ input_text }}. Leave empty to fall back to more general defaults.');

    // Entity type level configuration
    $form['entity_type_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Entity type default configuration'),
      '#description' => $this->t('These settings apply to all bundles of this entity type unless a bundle-specific setting is defined.'),
      '#open' => FALSE,
    ];

    // Entity type default for all languages
    $form['entity_type_config']['default_all'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Default for all languages'),
    ];

    $tokens_html_type = $this->buildTokensListHtml($entity_type_id, NULL, 4, TRUE);
    $form['entity_type_config']['default_all']['prompt'] = [
      '#type' => 'select',
      '#options' => $prompt_options,
      '#empty_option' => $this->t('-- Use default from AI module --'),
      '#title' => $this->t('Default prompt for all languages'),
      '#description' => Markup::create($prompt_description . ($tokens_html_type ? ' ' . $tokens_html_type : '')),
      '#default_value' => $settings_entity->getEntityTypeDefaultPrompt(),
    ];

    $form['entity_type_config']['default_all']['model'] = [
      '#type' => 'select',
      '#title' => $this->t('Default AI model for all languages'),
      '#options' => $chat_models,
      '#empty_option' => $this->t('-- Use default from AI module --'),
      '#default_value' => $settings_entity->getEntityTypeDefaultModel(),
      '#access' => !empty($chat_models),
    ];

    // Per-language entity-type configuration
    $entity_type_prompts = $settings_entity->getEntityTypePrompts();
    $entity_type_models = $settings_entity->getEntityTypeModels();

    $form['entity_type_config']['per_language'] = [
      '#type' => 'details',
      '#title' => $this->t('Per-language configuration'),
      '#description' => $this->t('Define custom prompts and models for specific languages. Empty values will fall back to entity type defaults, then global defaults.'),
      '#open' => FALSE,
    ];

    foreach ($languages as $langcode => $language) {
      $form['entity_type_config']['per_language'][$langcode] = [
        '#type' => 'fieldset',
        '#title' => $this->t('For @lang (@langcode)', [
          '@lang' => $language->getName(),
          '@langcode' => $langcode,
        ]),
      ];

      $tokens_html = $this->buildTokensListHtml($entity_type_id, NULL, 4, TRUE);
      $form['entity_type_config']['per_language'][$langcode]['prompt'] = [
        '#type' => 'select',
        '#options' => $prompt_options,
        '#empty_option' => $this->t('-- Use default from AI module --'),
        '#title' => $this->t('Prompt for translating to @lang', ['@lang' => $language->getName()]),
        '#description' => Markup::create($prompt_description . ($tokens_html ? ' ' . $tokens_html : '')),
        '#default_value' => $entity_type_prompts[$langcode] ?? '',
      ];

      $form['entity_type_config']['per_language'][$langcode]['model'] = [
        '#type' => 'select',
        '#title' => $this->t('AI model for translating to @lang', ['@lang' => $language->getName()]),
        '#options' => $chat_models,
        '#empty_option' => $this->t('-- Use entity type default or AI module default --'),
        '#default_value' => $entity_type_models[$langcode] ?? '',
        '#access' => !empty($chat_models),
      ];
    }

    // Bundle-specific settings
    $bundles = $this->loadBundles($entity_type);
    $prompts = $settings_entity->getBundlePrompts();
    $bundle_default_prompts = $settings_entity->getBundleDefaultPrompts();
    $models = $settings_entity->getBundleModels();
    $bundle_default_models = $settings_entity->getBundleDefaultModels();
    $disabled_fields = $settings_entity->getDisabledFields();

    foreach ($bundles as $bundle_id => $bundle_label) {
      $form[$bundle_id] = [
        '#type' => 'details',
        '#title' => $this->t('Bundle: @label (@id)', ['@label' => $bundle_label, '@id' => $bundle_id]),
        '#open' => FALSE,
      ];

      // Bundle default for all languages
      $form[$bundle_id]['bundle_default'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Bundle default (all languages)'),
      ];

      $tokens_html_bundle = $this->buildTokensListHtml($entity_type_id, $bundle_id, 4, FALSE);
      $form[$bundle_id]['bundle_default']['prompt'] = [
        '#type' => 'select',
        '#options' => $prompt_options,
        '#empty_option' => $this->t('-- Use default from AI module --'),
        '#title' => $this->t('Default prompt for all languages'),
        '#description' => Markup::create($prompt_description . ($tokens_html_bundle ? ' ' . $tokens_html_bundle : '')),
        '#default_value' => $bundle_default_prompts[$bundle_id] ?? '',
      ];

      $form[$bundle_id]['bundle_default']['model'] = [
        '#type' => 'select',
        '#title' => $this->t('Default AI model for all languages'),
        '#options' => $chat_models,
        '#empty_option' => $this->t('-- Use entity type default --'),
        '#default_value' => $bundle_default_models[$bundle_id] ?? '',
        '#access' => !empty($chat_models),
      ];

      // Per-language bundle configuration
      $form[$bundle_id]['per_language'] = [
        '#type' => 'details',
        '#title' => $this->t('Per-language configuration'),
        '#description' => $this->t('Define custom prompts and models for specific languages. Empty values will fall back to bundle default, entity type defaults, then global defaults.'),
        '#open' => FALSE,
      ];

      foreach ($languages as $langcode => $language) {
        $form[$bundle_id]['per_language'][$langcode] = [
          '#type' => 'fieldset',
          '#title' => $this->t('For @lang (@langcode)', [
            '@lang' => $language->getName(),
            '@langcode' => $langcode,
          ]),
        ];

        $tokens_html = $this->buildTokensListHtml($entity_type_id, $bundle_id, 4, FALSE);
        $form[$bundle_id]['per_language'][$langcode]['prompt'] = [
          '#type' => 'select',
          '#options' => $prompt_options,
          '#empty_option' => $this->t('-- Use default from AI module --'),
          '#title' => $this->t('Prompt for translating to @lang', ['@lang' => $language->getName()]),
          '#description' => Markup::create($prompt_description . ($tokens_html ? ' ' . $tokens_html : '')),
          '#default_value' => $prompts[$bundle_id][$langcode] ?? '',
        ];

        $form[$bundle_id]['per_language'][$langcode]['model'] = [
          '#type' => 'select',
          '#title' => $this->t('AI model for translating to @lang', ['@lang' => $language->getName()]),
          '#options' => $chat_models,
          '#empty_option' => $this->t('-- Use bundle default --'),
          '#default_value' => $models[$bundle_id][$langcode] ?? '',
          '#access' => !empty($chat_models),
        ];
      }

      // Field disable checkboxes
      $field_options = $this->getTranslatableFieldOptions($entity_type_id, $bundle_id);
      if ($field_options !== []) {
        $form[$bundle_id]['fields_container'] = [
          '#type' => 'details',
          '#title' => $this->t('Field settings'),
          '#description' => $this->t('Configure which fields should be skipped during translation.'),
          '#open' => FALSE,
        ];

        $form[$bundle_id]['fields_container']['disabled_fields'] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Do not translate these fields'),
          '#description' => $this->t('Selected fields will be ignored by AI Translate.'),
          '#options' => $field_options,
          '#default_value' => array_values($disabled_fields[$bundle_id] ?? []),
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Load bundles for an entity type.
   */
  private function loadBundles(EntityTypeInterface $entity_type): array {
    $bundles = [];
    $bundle_entity_type_id = $entity_type->getBundleEntityType();
    if (!$bundle_entity_type_id) {
      return $bundles;
    }
    $bundle_storage = $this->entityTypeManager->getStorage($bundle_entity_type_id);
    $bundle_entities = $bundle_storage->loadMultiple();
    foreach ($bundle_entities as $bundle) {
      $bundles[$bundle->id()] = $bundle->label();
    }
    asort($bundles, SORT_NATURAL | SORT_FLAG_CASE);
    return $bundles;
  }

  /**
   * Returns a list of translatable fields for the given entity type and bundle.
   */
  private function getTranslatableFieldOptions(string $entity_type_id, string $bundle_id): array {
    $options = [];
    $definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle_id);

    foreach ($definitions as $field_name => $definition) {
      if ($definition->isTranslatable() && !$definition->isComputed() && !$definition->isReadOnly()) {
        $label = $definition->getLabel();
        $options[$field_name] = $label ? (string) $label : $field_name;
      }
    }

    if ($options === []) {
      return $options;
    }

    try {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $entity_keys = $this->entityTypeManager->getDefinition($entity_type_id)->getKeys();
      $bundle_key = $entity_keys['bundle'] ?? NULL;
      $values = $bundle_key ? [$bundle_key => $bundle_id] : [];
      $stub = $storage->create($values);

      foreach (array_keys($options) as $field_name) {
        if ($stub->hasField($field_name) && $stub->get($field_name)->isEmpty()) {
          try {
            $stub->get($field_name)->generateSampleItems();
            if ($stub->get($field_name)->isEmpty()) {
              $stub->get($field_name)->setValue('Some dummy text.');
            }
          }
          catch (\Exception $e) {
            // Not all fields can generate sample items.
          }
        }
      }

      $metadata = $this->textExtractor->getInner()->extractTextMetadata($stub);
      $extractable_field_names = array_unique(array_column($metadata, 'field_name'));

      if ($extractable_field_names !== []) {
        $allowed = array_fill_keys($extractable_field_names, TRUE);
        $options = array_intersect_key($options, $allowed);
      }
      else {
        $options = [];
      }
    }
    catch (\Throwable $e) {
      \Drupal::logger('ai_translate_plus')->warning('Failed to determine extractable fields: @m', ['@m' => $e->getMessage()]);
    }

    asort($options);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\ai_translate_plus\Entity\AiTranslatePlusSettings $entity */
    $entity = $this->getEntity();
    $form_values = $form_state->getValues();

    // Process entity type level configurations
    if (isset($form_values['entity_type_config'])) {
      $entity_type_config = $form_values['entity_type_config'];

      $entity->setEntityTypeDefaultPrompt($entity_type_config['default_all']['prompt'] ?? '');
      $entity->setEntityTypeDefaultModel($entity_type_config['default_all']['model'] ?? '');

      $entity_type_prompts = [];
      $entity_type_models = [];

      foreach ($this->languageManager->getLanguages() as $langcode => $language) {
        if (!empty($entity_type_config['per_language'][$langcode]['prompt'])) {
          $entity_type_prompts[$langcode] = (string) $entity_type_config['per_language'][$langcode]['prompt'];
        }

        if (!empty($entity_type_config[$langcode]['model'])) {
          $entity_type_models[$langcode] = (string) $entity_type_config['per_language'][$langcode]['model'];
        }
      }

      $entity->setEntityTypePrompts($entity_type_prompts);
      $entity->setEntityTypeModels($entity_type_models);
    }

    // Process bundle-specific values
    $prompts = [];
    $bundle_default_prompts = [];
    $models = [];
    $bundle_default_models = [];
    $disabled_fields = [];

    foreach ($form_values as $bundle_id => $bundle_values) {
      if ($bundle_id === 'entity_type_config' || !is_array($bundle_values) || !array_key_exists('bundle_default', $bundle_values) || !array_key_exists('per_language', $bundle_values)) {
        continue;
      }

      if (!empty($bundle_values['bundle_default']['prompt'])) {
        $bundle_default_prompts[$bundle_id] = (string) $bundle_values['bundle_default']['prompt'];
      }

      if (!empty($bundle_values['bundle_default']['model'])) {
        $bundle_default_models[$bundle_id] = (string) $bundle_values['bundle_default']['model'];
      }

      $per_language = $bundle_values['per_language'] ?? [];
      foreach ($per_language as $langcode => $lang_values) {
        if (!empty($lang_values['prompt'])) {
          $prompts[$bundle_id][$langcode] = (string) $lang_values['prompt'];
        }

        if (!empty($lang_values['model'])) {
          $models[$bundle_id][$langcode] = (string) $lang_values['model'];
        }
      }

      if (isset($bundle_values['fields_container']['disabled_fields']) && is_array($bundle_values['fields_container']['disabled_fields'])) {
        $disabled_fields[$bundle_id] = array_values(array_filter($bundle_values['fields_container']['disabled_fields']));
      }
    }

    $entity->setBundlePrompts($prompts);
    $entity->setBundleDefaultPrompts($bundle_default_prompts);
    $entity->setBundleModels($models);
    $entity->setBundleDefaultModels($bundle_default_models);
    $entity->setDisabledFields($disabled_fields);

    $entity->save();

    $this->messenger()->addMessage($this->t('The AI Translate Plus settings for %entity_type_id have been saved.', ['%entity_type_id' => $entity->getEntityTypeId()]));
    $form_state->setRedirect('ai_translate_plus.settings_form');

    parent::submitForm($form, $form_state);
  }

}
