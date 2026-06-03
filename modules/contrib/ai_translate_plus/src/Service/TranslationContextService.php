<?php

namespace Drupal\ai_translate_plus\Service;

/**
 * Service to store and retrieve translation context during processing.
 */
final class TranslationContextService {

  /**
   * The current translation context.
   */
  private array $context = [];

  /**
   * Set the current translation context.
   *
   * @param array $context
   *   The context data.
   */
  public function setContext(array $context): void {
    $this->context = $context;
  }

  /**
   * Get the current translation context.
   *
   * @return array
   *   The context data.
   */
  public function getContext(): array {
    return $this->context;
  }

  /**
   * Clear the current translation context.
   */
  public function clearContext(): void {
    $this->context = [];
  }

  /**
   * Check if we have active context.
   *
   * @return bool
   *   TRUE if context is available.
   */
  public function hasContext(): bool {
    return !empty($this->context['entity_type_id']) &&
      !empty($this->context['bundle']) &&
      !empty($this->context['lang_to']);
  }

}
