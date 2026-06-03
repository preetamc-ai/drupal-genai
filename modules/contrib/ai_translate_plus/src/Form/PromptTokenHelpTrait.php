<?php

namespace Drupal\ai_translate_plus\Form;

/**
 * Small helpers for building token help in forms.
 */
trait PromptTokenHelpTrait {

  /**
   * Builds a simple HTML snippet with common example tokens.
   *
   * @param string $entity_type_id
   *   Entity type machine name (e.g., node).
   * @param string|null $bundle
   *   Bundle (optional, currently unused).
   * @param int $max_depth
   *   Max chain depth (unused, reserved for future).
   * @param bool $base_fields_only
   *   Whether to show only base tokens (unused, reserved for future).
   *
   * @return string
   *   An HTML snippet with example tokens or empty string.
   */
  protected function buildTokensListHtml(string $entity_type_id, ?string $bundle = NULL, int $max_depth = 4, bool $base_fields_only = FALSE): string {
    // Read token info directly from Drupal’s token service (no DI required in trait).
    $token_info = \Drupal::token()->getInfo();

    $type = $entity_type_id;
    if (!isset($token_info['types'][$type])) {
      // Fall back to generic “entity” if specific type unavailable.
      $type = isset($token_info['types']['entity']) ? 'entity' : NULL;
    }
    if (!$type) {
      return '';
    }

    $tokens = $token_info['tokens'][$type] ?? [];
    if ($tokens === []) {
      return '';
    }

    // Pick a few common examples if available.
    $candidates = ['title', 'author', 'label', 'id', 'created', 'changed', 'uid'];
    $examples = [];
    foreach ($candidates as $name) {
      if (isset($tokens[$name])) {
        $examples[] = '[' . $type . ':' . $name . ']';
        if ($name === 'author') {
          $examples[] = '[' . $type . ':' . $name . ':enity:uid]';
        }
      }
      if (count($examples) >= 5) {
        break;
      }
    }

    if (!$examples) {
      // Fallback to the first few tokens available.
      foreach (array_keys($tokens) as $name) {
        $examples[] = '[' . $type . ':' . $name . ']';
        if (count($examples) >= 5) {
          break;
        }
      }
    }

    if ($examples === []) {
      return '';
    }

    return '<br><small>' . $this->t('You can use any replacement token based on the entity. The replacement happens before the Twig Rendering starts. Example tokens: @examples. Within Twig you can use the Twig syntax to access the entity and fields.', ['@examples' => implode(', ', $examples)])->render() . '</small>';
  }

  /**
   * Returns a simple explanation about using tokens in prompts.
   */
  protected function getTokenHelpText(string $entity_type_id): string {
    return $this->t('You can use tokens like [@t:title] and [@t:id] in prompts. They will be replaced with values from the entity being translated.', [
      '@t' => $entity_type_id,
    ])->render();
  }

}
