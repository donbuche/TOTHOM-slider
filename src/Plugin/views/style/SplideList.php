<?php

namespace Drupal\drupal_splide\Plugin\views\style;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\views\Plugin\views\row\EntityRow;

/**
 * Style plugin to render Splide list markup.
 *
 * @ViewsStyle(
 *   id = "splide_list",
 *   title = @Translation("Splide list"),
 *   help = @Translation("Renders rows as a Splide list (ul.splide__list)."),
 *   theme = "views_view_unformatted",
 *   display_types = {"normal"}
 * )
 */
class SplideList extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  public function render() {
    // Splide needs the full result set; disable the pager for this style.
    $this->view->setItemsPerPage(0);

    $rows = [];
    foreach ($this->view->result as $index => $row) {
      $row_render = NULL;
      if ($this->view->rowPlugin instanceof EntityRow && isset($row->_entity) && $row->_entity) {
        $entity = $row->_entity;
        /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
        $entity_repository = \Drupal::service('entity.repository');
        /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
        $entity_type_manager = \Drupal::service('entity_type.manager');
        $entity = $entity_repository->getTranslationFromContext($entity);
        $view_mode = $this->view->rowPlugin->options['view_mode'] ?? 'default';
        $view_builder = $entity_type_manager->getViewBuilder($entity->getEntityTypeId());
        $row_render = $view_builder->view($entity, $view_mode);
      }
      else {
        $row_render = $this->view->rowPlugin->render($row);
      }
      if ($row_render === NULL) {
        $row_render = ['#markup' => ''];
      }
      elseif (!is_array($row_render)) {
        $row_render = ['#markup' => (string) $row_render];
      }
      $rows[$index] = [
        '#type' => 'html_tag',
        '#tag' => 'li',
        '#attributes' => ['class' => ['splide__slide']],
        'content' => $row_render,
      ];
    }

    return [
      '#type' => 'html_tag',
      '#tag' => 'ul',
      '#attributes' => ['class' => ['splide__list']],
      'items' => $rows,
    ];
  }

}
