<?php

namespace Drupal\ai_translate_plus\Controller;

use Drupal\ai_translate\Controller\AiTranslateController as BaseAiTranslateController;
use Drupal\Core\Language\LanguageInterface;
use Drupal\ai_translate\TranslationException;

/**
 * Extends the AI Translate Controller to pass enhanced context to the translator.
 */
class AiTranslateController extends BaseAiTranslateController {

  /**
   * Batch callback - translate a single (text) field with enhanced context.
   *
   * Overrides the parent method to pass additional context to the translator
   * for custom prompts per entity type/bundle/language.
   *
   * @param array $singleField
   *   Chunk of (text) field metadata to translate.
   * @param \Drupal\Core\Language\LanguageInterface $langFrom
   *   The source language.
   * @param \Drupal\Core\Language\LanguageInterface $langTo
   *   The target language.
   * @param array $context
   *   The batch context.
   */
  public function translateSingleField(
    array $singleField,
    LanguageInterface $langFrom,
    LanguageInterface $langTo,
    array &$context,
  ) {
    // Get translations for each extracted field property.
    $translated_text = [];
    foreach ($singleField['_columns'] as $column) {
      try {
        $translated_text[$column] = '';
        if (!empty($singleField[$column])) {
          // Prepare enhanced context for the translator
          $translationContext = [];

          // Add ai_translate_plus context if available
          if (!empty($singleField['ai_translate_plus_context'])) {
            $plusContext = $singleField['ai_translate_plus_context'];
            $translationContext = [
              'entity_type_id' => $plusContext['entity_type_id'],
              'bundle' => $plusContext['bundle'],
              'entity_id' => $plusContext['entity_id'],
              'lang_to' => $langTo->getId(),
              'field_name' => $singleField['field_name'] ?? '',
              'field_type' => $singleField['field_type'] ?? '',
            ];
          }

          $translated_text[$column] = $this->aiTranslator->translateContent(
            $singleField[$column],
            $langTo,
            $langFrom,
            $translationContext
          );
        }
      }
      catch (TranslationException) {
        $context['results']['failures'][] = $singleField[$column];
        return;
      }
    }

    // Decodes HTML entities in translation.
    // Because of sanitation in StringFormatter/Markup, this should be safe.
    foreach ($translated_text as &$translated_text_item) {
      $translated_text_item = html_entity_decode($translated_text_item);
    }

    $singleField['translated'] = $translated_text;
    $context['results']['processedTranslations'][] = $singleField;
  }

}
