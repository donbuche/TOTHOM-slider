<?php

namespace Drupal\drupal_splide\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a selectable Splide carousel block.
 *
 * @Block(
 *   id = "drupal_splide_carousel_select_block",
 *   admin_label = @Translation("Splide carousel (select)")
 * )
 */
class SplideCarouselSelectBlock extends SplideCarouselBlock {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'carousel_id' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);

    $storage = $this->entityTypeManager->getStorage('splide_carousel');
    $carousels = $storage->loadMultiple();
    $options = [];
    foreach ($carousels as $id => $carousel) {
      $options[$id] = $carousel->label();
    }

    $form['settings']['carousel_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Carousel'),
      '#options' => $options,
      '#empty_option' => $this->t('- Select a carousel -'),
      '#default_value' => $this->configuration['carousel_id'] ?? '',
      '#required' => TRUE,
      '#description' => $this->t('Create a new carousel. <a href=":url" target="_blank" rel="noopener noreferrer">Open in a new tab</a>.', [
        ':url' => Url::fromRoute('entity.splide_carousel.add_form')->toString(),
      ]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);
    $this->configuration['carousel_id'] = $form_state->getValue(['settings', 'carousel_id']) ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $carousel_id = $this->configuration['carousel_id'] ?? '';
    if ($carousel_id === '') {
      return [];
    }

    return $this->buildCarousel($carousel_id);
  }

}
