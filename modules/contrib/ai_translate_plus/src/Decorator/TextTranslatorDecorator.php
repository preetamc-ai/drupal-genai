<?php

namespace Drupal\ai_translate_plus\Decorator;

use Drupal\ai_translate\TextTranslatorInterface;
use Drupal\ai_translate_plus\Service\TranslationContextService;
use Drupal\Core\Language\LanguageInterface;

/**
 * Decorates the TextTranslator to set context for ChatTranslationPlusProvider.
 */
final class TextTranslatorDecorator implements TextTranslatorInterface {

  public function __construct(
    private readonly TextTranslatorInterface $inner,
    private readonly TranslationContextService $contextService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function translateContent(
    string $input_text,
    LanguageInterface $langTo,
    ?LanguageInterface $langFrom = NULL,
    array $context = [],
  ): string {
    // Set the context for ChatTranslationPlusProvider to access.
    if (!empty($context['entity_type_id']) && !empty($context['bundle']) && !empty($context['lang_to'])) {
      $this->contextService->setContext($context);
    }

    try {
      $result = $this->inner->translateContent($input_text, $langTo, $langFrom, $context);

      // Clear context after translation.
      $this->contextService->clearContext();

      return $result;
    }
    catch (\Throwable $e) {
      // Clear context on error.
      $this->contextService->clearContext();
      throw $e;
    }
  }

}
