<?php

declare(strict_types=1);

namespace Drupal\ai_translate_plus\Decorator;

use Drupal\ai_translate\TextExtractorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Decorator for the text extractor that filters disabled fields.
 */
final class TextExtractorDecorator implements TextExtractorInterface {

  public function __construct(
    private readonly TextExtractorInterface $inner,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function extractTextMetadata(ContentEntityInterface $entity, array $parents = []): array {
    $metadata = $this->inner->extractTextMetadata($entity, $parents);

    $entity_type_id = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    // Load the settings entity for this entity type to get disabled fields
    $disabled_for_bundle = [];
    try {
      $settings_storage = $this->entityTypeManager->getStorage('ai_translate_plus_settings');
      /** @var \Drupal\ai_translate_plus\Entity\AiTranslatePlusSettings $settings_entity */
      $settings_entity = $settings_storage->load($entity_type_id);

      if ($settings_entity) {
        $disabled_fields = $settings_entity->getDisabledFields();
        $disabled_for_bundle = $disabled_fields[$bundle] ?? [];
      }
    }
    catch (\Throwable $e) {
      // If we can't load the config entity, continue without filtering
      // This ensures the decorator works even if the config entity doesn't exist yet
    }

    // Filter out disabled fields.
    if ($disabled_for_bundle !== []) {
      $disabled_set = array_flip($disabled_for_bundle);

      $metadata = array_values(array_filter($metadata, function (array $item) use ($disabled_set): bool {
        $field_name = $item['field_name'] ?? NULL;
        return !$field_name || !isset($disabled_set[$field_name]);
      }));
    }

    // Add context information to each metadata item for the translator decorator.
    foreach ($metadata as &$item) {
      $item['ai_translate_plus_context'] = [
        'entity_type_id' => $entity_type_id,
        'bundle' => $bundle,
        'entity_id' => $entity->id(),
      ];
    }

    return $metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function insertTextMetadata(ContentEntityInterface $entity, array $metadata): void {
    $this->inner->insertTextMetadata($entity, $metadata);
  }

  /**
   * Gets the decorated inner service.
   */
  public function getInner(): TextExtractorInterface {
    return $this->inner;
  }

}
