<?php

namespace Drupal\tothom_slider\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a selectable TOTHOM Slider block.
 *
 * @Block(
 *   id = "tothom_slider_block",
 *   admin_label = @Translation("TOTHOM Slider")
 * )
 */
class TothomSliderSelectBlock extends TothomSliderBlock {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'slider_id' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);

    $storage = $this->entityTypeManager->getStorage('tothom_slider');
    $sliders = $storage->loadMultiple();
    $options = [];
    foreach ($sliders as $id => $slider) {
      $options[$id] = $slider->label();
    }

    $form['settings']['slider_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Slider'),
      '#options' => $options,
      '#empty_option' => $this->t('- Select a slider -'),
      '#default_value' => $this->configuration['slider_id'] ?? '',
      '#required' => TRUE,
      '#description' => $this->t('Create a new slider. <a href=":url" target="_blank" rel="noopener noreferrer">Open in a new tab</a>.', [
        ':url' => Url::fromRoute('entity.tothom_slider.add_form')->toString(),
      ]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    parent::blockSubmit($form, $form_state);
    $this->configuration['slider_id'] = $form_state->getValue(['settings', 'slider_id']) ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $slider_id = $this->configuration['slider_id'] ?? '';
    if ($slider_id === '') {
      return [];
    }

    return $this->buildCarousel($slider_id);
  }

}
