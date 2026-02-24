<?php

namespace Drupal\tothom_slider\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\tothom_slider\TothomSliderInterface;

/**
 * Defines the TOTHOM Slider config entity.
 *
 * @ConfigEntityType(
 *   id = "tothom_slider",
 *   label = @Translation("TOTHOM Slider"),
 *   label_collection = @Translation("TOTHOM Sliders"),
 *   label_singular = @Translation("TOTHOM Slider"),
 *   label_plural = @Translation("TOTHOM Sliders"),
 *   label_count = @PluralTranslation(
 *     singular = "@count TOTHOM Slider",
 *     plural = "@count TOTHOM Sliders"
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\tothom_slider\TothomSliderListBuilder",
 *     "form" = {
 *       "add" = "Drupal\tothom_slider\Form\TothomSliderForm",
 *       "edit" = "Drupal\tothom_slider\Form\TothomSliderForm",
 *       "delete" = "Drupal\tothom_slider\Form\TothomSliderDeleteForm"
 *     },
 *     "translation" = "Drupal\Core\Config\Entity\ConfigEntityTranslationHandler"
 *   },
 *   admin_permission = "administer tothom sliders",
 *   config_prefix = "tothom_slider",
 *   config_export = {
 *     "id",
 *     "langcode",
 *     "label",
 *     "uuid",
 *     "status",
 *     "options"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status",
 *     "langcode" = "langcode"
 *   },
 *   translatable = TRUE,
 *   links = {
 *     "collection" = "/admin/content/sliders",
 *     "add-form" = "/admin/content/sliders/add",
 *     "edit-form" = "/admin/content/sliders/{tothom_slider}",
 *     "delete-form" = "/admin/content/sliders/{tothom_slider}/delete",
 *     "translation-overview" = "/admin/content/sliders/{tothom_slider}/translate",
 *     "translate-form" = "/admin/content/sliders/{tothom_slider}/translate/{language}"
 *   }
 * )
 */
class TothomSlider extends ConfigEntityBase implements TothomSliderInterface {

  /**
   * The slider ID.
   *
   * @var string
   */
  protected string $id;

  /**
   * The slider label.
   *
   * @var string
   */
  protected string $label;

  /**
   * Whether the slider is enabled.
   *
   * @var bool
   */
  protected $status = TRUE;

  /**
   * Splide options.
   *
   * @var array
   */
  protected array $options = [];

}
