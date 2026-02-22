<?php

namespace Drupal\drupal_splide\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Splide carousel blocks.
 */
class SplideCarouselBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a new SplideCarouselBlock instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $carousel_id = $this->getDerivativeId();
    if (!$carousel_id) {
      return [];
    }
    return $this->buildCarousel($carousel_id);
  }

  /**
   * Builds the render array for a specific carousel.
   */
  protected function buildCarousel(string $carousel_id): array {
    if ($carousel_id === '') {
      return [];
    }

    $storage = $this->entityTypeManager->getStorage('splide_carousel');
    $carousel = $storage->load($carousel_id);
    if (!$carousel || !$carousel->status()) {
      return [];
    }

    $options = $carousel->get('options') ?? [];
    $content = $options['content'] ?? [];
    $source = $content['source'] ?? '';
    $prefix = $content['prefix'] ?? [];
    $suffix = $content['suffix'] ?? [];

    $selector = $this->getCarouselSelector($carousel_id, $content);
    $wrapper_attributes = [
      'class' => ['splide'],
    ];
    if (!empty($content['aria_label'])) {
      $wrapper_attributes['aria-label'] = $content['aria_label'];
    }
    if (!empty($selector['id'])) {
      $wrapper_attributes['id'] = $selector['id'];
    }
    if (!empty($selector['class'])) {
      $wrapper_attributes['class'][] = $selector['class'];
    }

    $build = [
      '#type' => 'container',
      '#attributes' => $wrapper_attributes,
      'prefix' => $this->buildFormattedText($prefix, 'splide-carousel__prefix'),
      'slider' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['splide__slider']],
        'track' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['splide__track']],
        ],
      ],
      'suffix' => $this->buildFormattedText($suffix, 'splide-carousel__suffix'),
    ];
    $build['#attached']['drupalSettings']['drupalSplide']['carousels'][$carousel_id] = [
      'selector' => $selector['raw'],
      'options' => $this->buildSplideOptions($options),
    ];
    $splide_options = $build['#attached']['drupalSettings']['drupalSplide']['carousels'][$carousel_id]['options'] ?? [];
    $is_autoplay = !empty($splide_options['autoplay']);
    if ($is_autoplay) {
      $i18n = $splide_options['i18n'] ?? [];
      $play_label = $i18n['play'] ?? $this->t('Start autoplay');
      $pause_label = $i18n['pause'] ?? $this->t('Pause autoplay');
      $build['toggle'] = [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#attributes' => [
          'class' => ['splide__toggle'],
          'type' => 'button',
        ],
        'play' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => ['class' => ['splide__toggle__play']],
          '#value' => $play_label,
        ],
        'pause' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => ['class' => ['splide__toggle__pause']],
          '#value' => $pause_label,
        ],
      ];
    }

    if ($source === 'node') {
      $items = $content['node']['items'] ?? [];
      $view_modes = $content['node']['view_modes'] ?? [];
      $node_ids = array_values(array_filter(array_map(static function (array $item): ?int {
        return isset($item['id']) ? (int) $item['id'] : NULL;
      }, $items)));
      if ($node_ids) {
        $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($node_ids);
        $view_builder = $this->entityTypeManager->getViewBuilder('node');

        $build['slider']['track']['list'] = [
          '#type' => 'html_tag',
          '#tag' => 'ul',
          '#attributes' => ['class' => ['splide__list']],
        ];

        foreach ($items as $delta => $item) {
          $nid = isset($item['id']) ? (int) $item['id'] : 0;
          if (!$nid || empty($nodes[$nid])) {
            continue;
          }
          $node = $nodes[$nid];
          $bundle = $node->bundle();
          $view_mode = $view_modes[$bundle] ?? 'teaser';
          $build['slider']['track']['list'][$delta] = [
            '#type' => 'html_tag',
            '#tag' => 'li',
            '#attributes' => ['class' => ['splide__slide']],
            'content' => $view_builder->view($node, $view_mode),
          ];
        }
      }
    }
    elseif ($source === 'views') {
      $view_machine = $content['views']['view_machine_name'] ?? '';
      $display = $content['views']['view_display_name'] ?? '';
      if ($view_machine && $display) {
        $view = Views::getView($view_machine);
        if ($view && $view->access($display)) {
          $view->setDisplay($display);
          $view->preExecute();
          $view->execute();

          $items = [];
          $style_render = $view->style_plugin->render();
          if (is_array($style_render)) {
            if (!empty($style_render['#rows'])) {
              $items = $style_render['#rows'];
            }
            elseif (array_is_list($style_render) && !empty($style_render[0]['#rows'])) {
              $items = $style_render[0]['#rows'];
            }
          }

          if (!$items) {
            $items[] = $view->render();
          }

          $build['slider']['track']['list'] = [
            '#type' => 'html_tag',
            '#tag' => 'ul',
            '#attributes' => ['class' => ['splide__list']],
          ];
          foreach ($items as $delta => $item) {
            $build['slider']['track']['list'][$delta] = [
              '#type' => 'html_tag',
              '#tag' => 'li',
              '#attributes' => ['class' => ['splide__slide']],
              'content' => $item,
            ];
          }

          // Bubble cache metadata and attachments from the view render array.
          $view_render = $view->render();
          if (!empty($view_render['#attached'])) {
            $build['#attached'] = array_replace_recursive(
              $build['#attached'] ?? [],
              $view_render['#attached']
            );
          }
          $view_cache = CacheableMetadata::createFromRenderArray($view_render);
          $view_cache->applyTo($build);
        }
      }
    }

    $cache = CacheableMetadata::createFromObject($carousel);
    $build['#attached']['library'][] = 'drupal_splide/splide';
    $cache->applyTo($build);

    return $build;
  }

  /**
   * Builds a render array for formatted text.
   */
  protected function buildFormattedText(array $data, string $css_class): array {
    $value = $data['value'] ?? '';
    $format = $data['format'] ?? NULL;
    if (!is_string($value) || trim($value) === '') {
      return [];
    }

    return [
      '#type' => 'processed_text',
      '#text' => $value,
      '#format' => $format,
      '#wrapper_attributes' => [
        'class' => [$css_class],
      ],
    ];
  }

  /**
   * Normalizes the selector data from settings.
   */
  protected function getCarouselSelector(string $carousel_id, array $content): array {
    $source = $content['source'] ?? '';
    $raw = '';
    if ($source === 'views') {
      $raw = $content['views']['carousel_selector'] ?? '';
    }
    $raw = is_string($raw) ? trim($raw) : '';

    if ($raw !== '') {
      if (str_starts_with($raw, '#')) {
        return [
          'raw' => $raw,
          'id' => substr($raw, 1),
          'class' => NULL,
        ];
      }
      if (str_starts_with($raw, '.')) {
        return [
          'raw' => $raw,
          'id' => NULL,
          'class' => substr($raw, 1),
        ];
      }
      return [
        'raw' => '.' . $raw,
        'id' => NULL,
        'class' => $raw,
      ];
    }

    return [
      'raw' => '.splide--' . $carousel_id,
      'id' => NULL,
      'class' => 'splide--' . $carousel_id,
    ];
  }

  /**
   * Builds the Splide options payload for JS.
   */
  protected function buildSplideOptions(array $options): array {
    $splide_options = [];
    unset($options['content']);

    $groups = [
      'general',
      'layout',
      'navigation',
      'autoplay',
      'drag',
      'lazy',
      'accessibility',
      'behavior',
      'reducedMotion',
      'classes',
      'i18n',
      'breakpoints',
    ];

    foreach ($groups as $group) {
      if (empty($options[$group]) || !is_array($options[$group])) {
        continue;
      }
      foreach ($options[$group] as $key => $value) {
        $normalized = $this->normalizeSplideValue($value);
        if ($normalized === NULL) {
          continue;
        }
        if ($group === 'classes' && $key === 'items') {
          $classes = $this->buildSplideClasses($normalized);
          if (!empty($classes)) {
            $splide_options['classes'] = $classes;
          }
          continue;
        }
        if ($group === 'i18n' && $key === 'items') {
          $splide_options['i18n'] = $normalized;
          continue;
        }
        if ($group === 'breakpoints' && $key === 'items') {
          $breakpoints = $this->buildSplideBreakpoints($normalized);
          if (!empty($breakpoints)) {
            $splide_options['breakpoints'] = $breakpoints;
          }
          continue;
        }
        if ($group === 'reducedMotion') {
          $splide_options['reducedMotion'] = $splide_options['reducedMotion'] ?? [];
          $splide_options['reducedMotion'][$key] = $normalized;
          continue;
        }
        $splide_options[$key] = $normalized;
      }
      unset($options[$group]);
    }

    foreach ($options as $key => $value) {
      if (is_array($value)) {
        continue;
      }
      $normalized = $this->normalizeSplideValue($value);
      if ($normalized === NULL) {
        continue;
      }
      $splide_options[$key] = $normalized;
    }

    return $splide_options;
  }

  /**
   * Builds the classes option by appending custom classes to defaults.
   */
  protected function buildSplideClasses($custom): array {
    if (!is_array($custom)) {
      return [];
    }

    $defaults = [
      'arrows' => 'splide__arrows',
      'arrow' => 'splide__arrow',
      'prev' => 'splide__arrow--prev',
      'next' => 'splide__arrow--next',
      'pagination' => 'splide__pagination',
      'page' => 'splide__pagination__page',
    ];

    $classes = [];
    foreach ($defaults as $key => $default) {
      $extra = trim((string) ($custom[$key] ?? ''));
      if ($extra === '') {
        continue;
      }
      $classes[$key] = trim($default . ' ' . $extra);
    }

    return $classes;
  }

  /**
   * Builds breakpoints options from JSON or array.
   */
  protected function buildSplideBreakpoints($raw): array {
    if (is_string($raw)) {
      $decoded = json_decode($raw, TRUE);
      if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return $this->normalizeBreakpoints($decoded);
      }
      return [];
    }
    if (is_array($raw)) {
      return $this->normalizeBreakpoints($raw);
    }
    return [];
  }

  /**
   * Normalizes breakpoint option values.
   */
  protected function normalizeBreakpoints(array $breakpoints): array {
    $normalized = [];
    foreach ($breakpoints as $breakpoint => $options) {
      if (!is_array($options)) {
        continue;
      }
      $normalized_options = [];
      foreach ($options as $key => $value) {
        if (is_array($value)) {
          $normalized_options[$key] = $value;
          continue;
        }
        $normalized_value = $this->normalizeSplideValue($value);
        if ($normalized_value === NULL) {
          continue;
        }
        $normalized_options[$key] = $normalized_value;
      }
      if (!empty($normalized_options)) {
        $normalized[$breakpoint] = $normalized_options;
      }
    }
    return $normalized;
  }

  /**
   * Normalizes values coming from the config form.
   */
  protected function normalizeSplideValue($value) {
    if (is_array($value)) {
      $filtered = array_filter($value, static function ($item) {
        return $item !== '' && $item !== NULL;
      });
      return $filtered ?: NULL;
    }

    if (is_bool($value) || is_int($value) || is_float($value)) {
      return $value;
    }

    if (!is_string($value)) {
      return $value ?: NULL;
    }

    $value = trim($value);
    if ($value === '') {
      return NULL;
    }

    $lower = strtolower($value);
    if ($lower === 'true') {
      return TRUE;
    }
    if ($lower === 'false') {
      return FALSE;
    }
    if ($lower === 'null') {
      return NULL;
    }

    if (ctype_digit($value)) {
      return (int) $value;
    }
    if (is_numeric($value)) {
      $float_value = (float) $value;
      return $float_value;
    }

    return $value;
  }

}
