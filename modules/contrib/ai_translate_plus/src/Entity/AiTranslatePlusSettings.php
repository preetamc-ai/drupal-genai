<?php

namespace Drupal\ai_translate_plus\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the AI Translate Plus entity type settings config entity.
 *
 * @ConfigEntityType(
 *   id = "ai_translate_plus_settings",
 *   label = @Translation("AI Translate Plus entity type settings"),
 *   label_collection = @Translation("AI Translate Plus entity type settings"),
 *   label_singular = @Translation("entity type setting"),
 *   label_plural = @Translation("entity type settings"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "list_builder" = "Drupal\ai_translate_plus\EntityTypeSettingsListBuilder",
 *   },
 *   config_prefix = "entity_type_settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "entity_type",
 *     "entity_type_default_prompt",
 *     "entity_type_prompts",
 *     "entity_type_default_model",
 *     "entity_type_models",
 *     "bundle_default_prompts",
 *     "bundle_prompts",
 *     "bundle_default_models",
 *     "bundle_models",
 *     "disabled_fields",
 *   },
 * )
 */
class AiTranslatePlusSettings extends ConfigEntityBase {

  /**
   * The entity type settings ID.
   *
   * @var string
   */
  protected string $id;

  /**
   * The entity type settings label.
   *
   * @var string
   */
  protected string $label;

  /**
   * The entity type machine name.
   *
   * @var string
   */
  protected string $entity_type;

  /**
   * Per-language prompts for entity type.
   *
   * @var array
   */
  protected array $entity_type_prompts = [];

  /**
   * Default prompt for all languages.
   *
   * @var string
   */
  protected string $entity_type_default_prompt = '';

  /**
   * Bundle default prompts (all languages).
   *
   * @var array
   */
  protected array $bundle_default_prompts = [];

  /**
   * Per-bundle, per-language prompts.
   *
   * @var array
   */
  protected array $bundle_prompts = [];

  /**
   * Per-bundle, per-language models.
   *
   * @var array
   */
  protected array $bundle_models = [];

  /**
   * Per-language models for entity type.
   *
   * @var array
   */
  protected array $entity_type_models = [];

  /**
   * Default model for all languages.
   *
   * @var string
   */
  protected string $entity_type_default_model = '';

  /**
   * Bundle default models (all languages).
   *
   * @var array
   */
  protected array $bundle_default_models = [];

  /**
   * Disabled fields per bundle.
   *
   * @var array
   */
  protected array $disabled_fields = [];

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    if (empty($this->label)) {
      $entity_type_manager = \Drupal::entityTypeManager();
      $entity_type = $entity_type_manager->getDefinition($this->entity_type);
      $this->label = (string) $entity_type->getLabel();
    }
  }

  /**
   * Get prompts.
   */
  public function getBundlePrompts(): array {
    return $this->bundle_prompts;
  }

  /**
   * Set prompts.
   */
  public function setBundlePrompts(array $prompts): static {
    $this->bundle_prompts = $prompts;
    return $this;
  }

  /**
   * Get entity type prompts.
   */
  public function getEntityTypePrompts(): array {
    return $this->entity_type_prompts;
  }

  /**
   * Set entity type prompts.
   */
  public function setEntityTypePrompts(array $prompts): static {
    $this->entity_type_prompts = $prompts;
    return $this;
  }

  /**
   * Get entity type default prompt.
   */
  public function getEntityTypeDefaultPrompt(): string {
    return $this->entity_type_default_prompt;
  }

  /**
   * Set entity type default prompt.
   */
  public function setEntityTypeDefaultPrompt(string $prompt): static {
    $this->entity_type_default_prompt = $prompt;
    return $this;
  }

  /**
   * Get bundle default prompts.
   */
  public function getBundleDefaultPrompts(): array {
    return $this->bundle_default_prompts;
  }

  /**
   * Set bundle default prompts.
   */
  public function setBundleDefaultPrompts(array $prompts): static {
    $this->bundle_default_prompts = $prompts;
    return $this;
  }

  /**
   * Get models.
   */
  public function getBundleModels(): array {
    return $this->bundle_models;
  }

  /**
   * Set models.
   */
  public function setBundleModels(array $models): static {
    $this->bundle_models = $models;
    return $this;
  }

  /**
   * Get entity type models.
   */
  public function getEntityTypeModels(): array {
    return $this->entity_type_models;
  }

  /**
   * Set entity type models.
   */
  public function setEntityTypeModels(array $models): static {
    $this->entity_type_models = $models;
    return $this;
  }

  /**
   * Get entity type default model.
   */
  public function getEntityTypeDefaultModel(): string {
    return $this->entity_type_default_model;
  }

  /**
   * Set entity type default model.
   */
  public function setEntityTypeDefaultModel(string $model): static {
    $this->entity_type_default_model = $model;
    return $this;
  }

  /**
   * Get bundle default models.
   */
  public function getBundleDefaultModels(): array {
    return $this->bundle_default_models;
  }

  /**
   * Set bundle default models.
   */
  public function setBundleDefaultModels(array $models): static {
    $this->bundle_default_models = $models;
    return $this;
  }

  /**
   * Get disabled fields.
   */
  public function getDisabledFields(): array {
    return $this->disabled_fields;
  }

  /**
   * Set disabled fields.
   */
  public function setDisabledFields(array $fields): static {
    $this->disabled_fields = $fields;
    return $this;
  }

}
