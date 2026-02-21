<?php

namespace Drupal\drupal_splide\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides derivative block definitions for Splide carousels.
 */
class SplideCarouselBlockDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a new SplideCarouselBlockDeriver.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id): self {
    return new self($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    $this->derivatives = [];
    $storage = $this->entityTypeManager->getStorage('splide_carousel');
    $ids = $storage->getQuery()->accessCheck(TRUE)->execute();
    if (!$ids) {
      return $this->derivatives;
    }

    $carousels = $storage->loadMultiple($ids);
    foreach ($carousels as $id => $carousel) {
      $this->derivatives[$id] = $base_plugin_definition;
      $this->derivatives[$id]['admin_label'] = $carousel->label();
      $this->derivatives[$id]['config_dependencies'] = [
        'config' => [$carousel->getConfigDependencyName()],
      ];
    }

    return $this->derivatives;
  }

}
