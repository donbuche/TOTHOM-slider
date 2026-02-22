<?php

namespace Drupal\drupal_splide\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\drupal_splide\SplideCarouselInterface;

/**
 * Defines the Splide carousel config entity.
 *
 * @ConfigEntityType(
 *   id = "splide_carousel",
 *   label = @Translation("Splide carousel"),
 *   label_collection = @Translation("Splide carousels"),
 *   label_singular = @Translation("Splide carousel"),
 *   label_plural = @Translation("Splide carousels"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Splide carousel",
 *     plural = "@count Splide carousels"
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\drupal_splide\SplideCarouselListBuilder",
 *     "form" = {
 *       "add" = "Drupal\drupal_splide\Form\SplideCarouselForm",
 *       "edit" = "Drupal\drupal_splide\Form\SplideCarouselForm",
 *       "delete" = "Drupal\drupal_splide\Form\SplideCarouselDeleteForm"
 *     },
 *     "translation" = "Drupal\Core\Config\Entity\ConfigEntityTranslationHandler"
 *   },
 *   admin_permission = "administer splide carousels",
 *   config_prefix = "splide_carousel",
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
 *     "collection" = "/admin/content/carousels",
 *     "add-form" = "/admin/content/carousels/add",
 *     "edit-form" = "/admin/content/carousels/{splide_carousel}",
 *     "delete-form" = "/admin/content/carousels/{splide_carousel}/delete"
 *   }
 * )
 */
class SplideCarousel extends ConfigEntityBase implements SplideCarouselInterface {

  /**
   * The carousel ID.
   *
   * @var string
   */
  protected string $id;

  /**
   * The carousel label.
   *
   * @var string
   */
  protected string $label;

  /**
   * Whether the carousel is enabled.
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
