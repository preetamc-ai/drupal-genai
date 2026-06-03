<?php

namespace Drupal\ai_translate_plus\Form;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overview form for AI Translate Plus settings.
 */
final class AiTranslatePlusOverviewForm extends FormBase {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ai_translate_plus_overview';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['description'] = [
      '#markup' => '<p>' . $this->t('Configure AI Translate Plus settings for each translatable entity type. Click on the tabs above to configure specific entity types.') . '</p>',
    ];

    // Get and sort entity types by human-readable label.
    $definitions = $this->entityTypeManager->getDefinitions();
    $entity_types = [];
    foreach ($definitions as $entity_type_id => $def) {
      if ($def instanceof ContentEntityTypeInterface && $def->isTranslatable() && $def->getBundleEntityType()) {
        $entity_types[$entity_type_id] = [
          'label' => (string) $def->getLabel(),
          'id' => $entity_type_id,
          'url' => Url::fromRoute('ai_translate_plus.settings.entity_type', ['entity_type_id' => $entity_type_id]),
        ];
      }
    }

    uasort($entity_types, function ($a, $b) {
      return strcasecmp($a['label'], $b['label']);
    });

    if ($entity_types) {
      $form['entity_types'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Entity Type'),
          $this->t('Operations'),
        ],
      ];

      foreach ($entity_types as $entity_type) {
        $form['entity_types'][$entity_type['id']] = [
          'label' => [
            '#markup' => $entity_type['label'] . ' (' . $entity_type['id'] . ')',
          ],
          'operations' => [
            '#type' => 'link',
            '#title' => $this->t('Configure'),
            '#url' => $entity_type['url'],
            '#attributes' => [
              'class' => ['button'],
            ],
          ],
        ];
      }
    }
    else {
      $form['no_entity_types'] = [
        '#markup' => '<p>' . $this->t('No translatable entity types with bundles found.') . '</p>',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // This form doesn't need submission handling.
  }

}
