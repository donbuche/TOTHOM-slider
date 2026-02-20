<?php

namespace Drupal\drupal_splide\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Form controller for Splide carousel add/edit forms.
 */
class SplideCarouselForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    /** @var \Drupal\drupal_splide\Entity\SplideCarousel $carousel */
    $carousel = $this->entity;
    $options = $carousel->get('options') ?? [];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $carousel->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $carousel->id(),
      '#machine_name' => [
        'exists' => '\Drupal\drupal_splide\Entity\SplideCarousel::load',
      ],
      '#disabled' => !$carousel->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $carousel->status(),
    ];

    $form['content'] = [
      '#type' => 'details',
      '#title' => $this->t('Carousel content'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['content']['semantics'] = [
      '#type' => 'radios',
      '#title' => $this->t('Carousel semantics'),
      '#options' => [
        'content' => $this->t('Content carousel'),
        'decorative' => $this->t('Decorative carousel'),
      ],
      '#default_value' => $options['content']['semantics'] ?? 'content',
    ];

    $form['content']['semantics_markup_content'] = [
      '#type' => 'details',
      '#title' => $this->t('Show HTML example'),
      '#open' => FALSE,
      '#markup' => '<p><strong>HTML example for Content carousels</strong></p><pre><code>&lt;section class="splide" aria-label="Splide Basic HTML Example"&gt;
  &lt;div class="splide__track"&gt;
    &lt;ul class="splide__list"&gt;
      &lt;li class="splide__slide"&gt;Slide 01&lt;/li&gt;
      &lt;li class="splide__slide"&gt;Slide 02&lt;/li&gt;
      &lt;li class="splide__slide"&gt;Slide 03&lt;/li&gt;
    &lt;/ul&gt;
  &lt;/div&gt;
&lt;/section&gt;</code></pre>',
      '#states' => [
        'visible' => [
          ':input[name="content[semantics]"]' => ['value' => 'content'],
        ],
      ],
    ];

    $form['content']['semantics_markup_decorative'] = [
      '#type' => 'details',
      '#title' => $this->t('Show HTML example'),
      '#open' => FALSE,
      '#markup' => '<p><strong>HTML example for Decorative carousels</strong></p><pre><code>&lt;div class="splide" role="group" aria-label="Splide Basic HTML Example"&gt;
  &lt;div class="splide__track"&gt;
    &lt;ul class="splide__list"&gt;
      &lt;li class="splide__slide"&gt;Slide 01&lt;/li&gt;
      &lt;li class="splide__slide"&gt;Slide 02&lt;/li&gt;
      &lt;li class="splide__slide"&gt;Slide 03&lt;/li&gt;
    &lt;/ul&gt;
  &lt;/div&gt;
&lt;/div&gt;</code></pre>',
      '#states' => [
        'visible' => [
          ':input[name="content[semantics]"]' => ['value' => 'decorative'],
        ],
      ],
    ];

    $form['content']['source'] = [
      '#type' => 'radios',
      '#title' => $this->t('Content source'),
      '#options' => [
        'node' => $this->t('Content provided by nodes'),
        'views' => $this->t('Content provided by Views'),
      ],
      '#default_value' => $options['content']['source'] ?? '',
    ];

    $form['content']['node'] = [
      '#type' => 'details',
      '#title' => $this->t('Content provided by nodes'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="content[source]"]' => ['value' => 'node'],
        ],
      ],
    ];

    $allowed_bundles_default = $options['content']['node']['allowed_bundles'] ?? [];
    $allowed_bundles = array_filter($form_state->getValue(['content', 'node', 'allowed_bundles']) ?? $allowed_bundles_default);

    $form['content']['node']['allowed_bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed content types'),
      '#options' => $this->getContentTypeOptions(),
      '#default_value' => $allowed_bundles_default,
      '#description' => $this->t('Select at least one content type to enable the node autocomplete below.'),
      '#ajax' => [
        'callback' => '::updateNodeAutocomplete',
        'wrapper' => 'splide-node-autocomplete-wrapper',
      ],
    ];

    $form['content']['node']['items_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'splide-node-autocomplete-wrapper'],
    ];

    $default_node_ids = $options['content']['node']['items'] ?? [];
    $items_count = $form_state->get('node_items_count');
    if ($items_count === NULL) {
      $items_count = max(1, count($default_node_ids));
      $form_state->set('node_items_count', $items_count);
    }

    $form['content']['node']['items_wrapper']['items'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Selected nodes'),
    ];

    for ($i = 0; $i < $items_count; $i++) {
      $form['content']['node']['items_wrapper']['items'][$i] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('Node title'),
        '#target_type' => 'node',
        '#selection_settings' => [
          'target_bundles' => $allowed_bundles,
        ],
        '#default_value' => $this->loadSingleNodeFromId($default_node_ids[$i] ?? NULL),
        '#description' => $this->t('Start typing to search nodes.'),
      ];
    }

    $form['content']['node']['items_wrapper']['add_more'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another node'),
      '#submit' => ['::addOneNode'],
      '#ajax' => [
        'callback' => '::updateNodeAutocomplete',
        'wrapper' => 'splide-node-autocomplete-wrapper',
      ],
    ];

    $form['content']['views'] = [
      '#type' => 'details',
      '#title' => $this->t('Content provided by Views'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="content[source]"]' => ['value' => 'views'],
        ],
      ],
    ];
    $form['content']['views']['view_machine_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('View machine name'),
      '#default_value' => $options['content']['views']['view_machine_name'] ?? '',
    ];
    $form['content']['views']['view_display_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('View display name'),
      '#default_value' => $options['content']['views']['view_display_name'] ?? '',
    ];
    $form['content']['views']['carousel_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CSS selector for the carousel'),
      '#default_value' => $options['content']['views']['carousel_selector'] ?? '',
    ];

    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Splide options'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    $form['options']['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General'),
      '#open' => TRUE,
    ];
    $form['options']['general']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => [
        'slide' => $this->t('slide'),
        'loop' => $this->t('loop'),
        'fade' => $this->t('fade'),
      ],
      '#default_value' => $options['type'] ?? 'slide',
    ];
    $form['options']['general']['start'] = [
      '#type' => 'number',
      '#title' => $this->t('Start index'),
      '#default_value' => $options['start'] ?? 0,
    ];
    $form['options']['general']['perPage'] = [
      '#type' => 'number',
      '#title' => $this->t('Per page'),
      '#default_value' => $options['perPage'] ?? 1,
    ];
    $form['options']['general']['perMove'] = [
      '#type' => 'number',
      '#title' => $this->t('Per move'),
      '#default_value' => $options['perMove'] ?? 1,
    ];
    $form['options']['general']['gap'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Gap'),
      '#description' => $this->t('CSS size, e.g. 1rem or 10px.'),
      '#default_value' => $options['gap'] ?? '',
    ];
    $form['options']['general']['padding'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Padding'),
      '#description' => $this->t('CSS size or object notation (leave empty for none).'),
      '#default_value' => $options['padding'] ?? '',
    ];
    $form['options']['general']['focus'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Focus'),
      '#description' => $this->t('center, left, right, number, or empty.'),
      '#default_value' => $options['focus'] ?? '',
    ];
    $form['options']['general']['rewind'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Rewind'),
      '#default_value' => $options['rewind'] ?? FALSE,
    ];
    $form['options']['general']['rewindSpeed'] = [
      '#type' => 'number',
      '#title' => $this->t('Rewind speed (ms)'),
      '#default_value' => $options['rewindSpeed'] ?? '',
    ];
    $form['options']['general']['rewindByDrag'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Rewind by drag'),
      '#default_value' => $options['rewindByDrag'] ?? FALSE,
    ];
    $form['options']['general']['speed'] = [
      '#type' => 'number',
      '#title' => $this->t('Speed (ms)'),
      '#default_value' => $options['speed'] ?? 400,
    ];
    $form['options']['general']['easing'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Easing'),
      '#description' => $this->t('CSS easing string, e.g. ease.'),
      '#default_value' => $options['easing'] ?? '',
    ];
    $form['options']['general']['easingFunc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Easing function'),
      '#description' => $this->t('JS function name (advanced).'),
      '#default_value' => $options['easingFunc'] ?? '',
    ];

    $form['options']['layout'] = [
      '#type' => 'details',
      '#title' => $this->t('Layout'),
      '#open' => FALSE,
    ];
    $form['options']['layout']['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#description' => $this->t('CSS size (e.g. 100%, 600px).'),
      '#default_value' => $options['width'] ?? '',
    ];
    $form['options']['layout']['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#description' => $this->t('CSS size (e.g. 400px).'),
      '#default_value' => $options['height'] ?? '',
    ];
    $form['options']['layout']['fixedWidth'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fixed width'),
      '#default_value' => $options['fixedWidth'] ?? '',
    ];
    $form['options']['layout']['fixedHeight'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fixed height'),
      '#default_value' => $options['fixedHeight'] ?? '',
    ];
    $form['options']['layout']['heightRatio'] = [
      '#type' => 'number',
      '#step' => '0.01',
      '#title' => $this->t('Height ratio'),
      '#default_value' => $options['heightRatio'] ?? '',
    ];
    $form['options']['layout']['autoWidth'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto width'),
      '#default_value' => $options['autoWidth'] ?? FALSE,
    ];
    $form['options']['layout']['autoHeight'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto height'),
      '#default_value' => $options['autoHeight'] ?? FALSE,
    ];
    $form['options']['layout']['cover'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cover'),
      '#default_value' => $options['cover'] ?? FALSE,
    ];
    $form['options']['layout']['trimSpace'] = [
      '#type' => 'select',
      '#title' => $this->t('Trim space'),
      '#options' => [
        'move' => $this->t('move'),
        'trim' => $this->t('trim'),
        'true' => $this->t('true'),
        'false' => $this->t('false'),
      ],
      '#default_value' => $options['trimSpace'] ?? 'true',
    ];
    $form['options']['layout']['omitEnd'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Omit end'),
      '#default_value' => $options['omitEnd'] ?? FALSE,
    ];
    $form['options']['layout']['clones'] = [
      '#type' => 'number',
      '#title' => $this->t('Clones'),
      '#default_value' => $options['clones'] ?? '',
    ];
    $form['options']['layout']['cloneStatus'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Clone status'),
      '#default_value' => $options['cloneStatus'] ?? TRUE,
    ];

    $form['options']['navigation'] = [
      '#type' => 'details',
      '#title' => $this->t('Navigation'),
      '#open' => FALSE,
    ];
    $form['options']['navigation']['arrows'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Arrows'),
      '#default_value' => $options['arrows'] ?? TRUE,
    ];
    $form['options']['navigation']['pagination'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pagination'),
      '#default_value' => $options['pagination'] ?? TRUE,
    ];
    $form['options']['navigation']['paginationKeyboard'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pagination keyboard'),
      '#default_value' => $options['paginationKeyboard'] ?? TRUE,
    ];
    $form['options']['navigation']['paginationDirection'] = [
      '#type' => 'select',
      '#title' => $this->t('Pagination direction'),
      '#options' => [
        'ltr' => $this->t('ltr'),
        'rtl' => $this->t('rtl'),
        'ttb' => $this->t('ttb'),
      ],
      '#default_value' => $options['paginationDirection'] ?? 'ltr',
    ];
    $form['options']['navigation']['arrowPath'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Arrow path'),
      '#description' => $this->t('SVG path string.'),
      '#default_value' => $options['arrowPath'] ?? '',
    ];
    $form['options']['navigation']['slideFocus'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Slide focus'),
      '#default_value' => $options['slideFocus'] ?? TRUE,
    ];
    $form['options']['navigation']['isNavigation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Is navigation'),
      '#default_value' => $options['isNavigation'] ?? FALSE,
    ];
    $form['options']['navigation']['focusableNodes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Focusable nodes'),
      '#description' => $this->t('CSS selectors, comma-separated.'),
      '#default_value' => $options['focusableNodes'] ?? '',
    ];

    $form['options']['autoplay'] = [
      '#type' => 'details',
      '#title' => $this->t('Autoplay'),
      '#open' => FALSE,
    ];
    $form['options']['autoplay']['autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay'),
      '#default_value' => $options['autoplay'] ?? FALSE,
    ];
    $form['options']['autoplay']['interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Interval (ms)'),
      '#default_value' => $options['interval'] ?? 5000,
    ];
    $form['options']['autoplay']['pauseOnHover'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause on hover'),
      '#default_value' => $options['pauseOnHover'] ?? TRUE,
    ];
    $form['options']['autoplay']['pauseOnFocus'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause on focus'),
      '#default_value' => $options['pauseOnFocus'] ?? TRUE,
    ];
    $form['options']['autoplay']['resetProgress'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reset progress'),
      '#default_value' => $options['resetProgress'] ?? TRUE,
    ];

    $form['options']['lazy'] = [
      '#type' => 'details',
      '#title' => $this->t('Lazy load'),
      '#open' => FALSE,
    ];
    $form['options']['lazy']['lazyLoad'] = [
      '#type' => 'select',
      '#title' => $this->t('Lazy load'),
      '#options' => [
        '' => $this->t('Disabled'),
        'nearby' => $this->t('nearby'),
        'sequential' => $this->t('sequential'),
      ],
      '#default_value' => $options['lazyLoad'] ?? '',
    ];
    $form['options']['lazy']['preloadPages'] = [
      '#type' => 'number',
      '#title' => $this->t('Preload pages'),
      '#default_value' => $options['preloadPages'] ?? '',
    ];

    $form['options']['drag'] = [
      '#type' => 'details',
      '#title' => $this->t('Drag & wheel'),
      '#open' => FALSE,
    ];
    $form['options']['drag']['drag'] = [
      '#type' => 'select',
      '#title' => $this->t('Drag'),
      '#options' => [
        'true' => $this->t('true'),
        'false' => $this->t('false'),
        'free' => $this->t('free'),
      ],
      '#default_value' => $options['drag'] ?? 'true',
    ];
    $form['options']['drag']['snap'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Snap'),
      '#default_value' => $options['snap'] ?? TRUE,
    ];
    $form['options']['drag']['noDrag'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No drag selectors'),
      '#default_value' => $options['noDrag'] ?? '',
    ];
    $form['options']['drag']['dragMinThreshold'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Drag min threshold'),
      '#default_value' => $options['dragMinThreshold'] ?? '',
    ];
    $form['options']['drag']['flickPower'] = [
      '#type' => 'number',
      '#title' => $this->t('Flick power'),
      '#default_value' => $options['flickPower'] ?? '',
    ];
    $form['options']['drag']['flickMaxPages'] = [
      '#type' => 'number',
      '#title' => $this->t('Flick max pages'),
      '#default_value' => $options['flickMaxPages'] ?? '',
    ];
    $form['options']['drag']['waitForTransition'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Wait for transition'),
      '#default_value' => $options['waitForTransition'] ?? TRUE,
    ];
    $form['options']['drag']['wheel'] = [
      '#type' => 'select',
      '#title' => $this->t('Wheel'),
      '#options' => [
        'true' => $this->t('true'),
        'false' => $this->t('false'),
        'global' => $this->t('global'),
      ],
      '#default_value' => $options['wheel'] ?? 'false',
    ];
    $form['options']['drag']['wheelMinThreshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Wheel min threshold'),
      '#default_value' => $options['wheelMinThreshold'] ?? '',
    ];
    $form['options']['drag']['wheelSleep'] = [
      '#type' => 'number',
      '#title' => $this->t('Wheel sleep (ms)'),
      '#default_value' => $options['wheelSleep'] ?? '',
    ];
    $form['options']['drag']['releaseWheel'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Release wheel'),
      '#default_value' => $options['releaseWheel'] ?? FALSE,
    ];

    $form['options']['accessibility'] = [
      '#type' => 'details',
      '#title' => $this->t('Accessibility'),
      '#open' => FALSE,
    ];
    $form['options']['accessibility']['role'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Role'),
      '#default_value' => $options['role'] ?? '',
    ];
    $form['options']['accessibility']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $options['label'] ?? '',
    ];
    $form['options']['accessibility']['labelledby'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Labelledby'),
      '#default_value' => $options['labelledby'] ?? '',
    ];
    $form['options']['accessibility']['focusableNodes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Focusable nodes'),
      '#description' => $this->t('CSS selectors, comma-separated.'),
      '#default_value' => $options['focusableNodes'] ?? '',
    ];

    $form['options']['behavior'] = [
      '#type' => 'details',
      '#title' => $this->t('Behavior'),
      '#open' => FALSE,
    ];
    $form['options']['behavior']['direction'] = [
      '#type' => 'select',
      '#title' => $this->t('Direction'),
      '#options' => [
        'ltr' => $this->t('ltr'),
        'rtl' => $this->t('rtl'),
        'ttb' => $this->t('ttb'),
      ],
      '#default_value' => $options['direction'] ?? 'ltr',
    ];
    $form['options']['behavior']['mediaQuery'] = [
      '#type' => 'select',
      '#title' => $this->t('Media query'),
      '#options' => [
        'min' => $this->t('min'),
        'max' => $this->t('max'),
      ],
      '#default_value' => $options['mediaQuery'] ?? 'max',
    ];
    $form['options']['behavior']['updateOnMove'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Update on move'),
      '#default_value' => $options['updateOnMove'] ?? TRUE,
    ];
    $form['options']['behavior']['keyboard'] = [
      '#type' => 'select',
      '#title' => $this->t('Keyboard'),
      '#options' => [
        'true' => $this->t('true'),
        'false' => $this->t('false'),
        'global' => $this->t('global'),
      ],
      '#default_value' => $options['keyboard'] ?? 'false',
    ];
    $form['options']['behavior']['live'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Live'),
      '#default_value' => $options['live'] ?? TRUE,
    ];
    $form['options']['behavior']['destroy'] = [
      '#type' => 'select',
      '#title' => $this->t('Destroy'),
      '#options' => [
        '' => $this->t('No'),
        'true' => $this->t('true'),
        'false' => $this->t('false'),
        'completely' => $this->t('completely'),
      ],
      '#default_value' => $options['destroy'] ?? '',
    ];

    $form['options']['breakpoints'] = [
      '#type' => 'details',
      '#title' => $this->t('Breakpoints'),
      '#open' => FALSE,
    ];
    $form['options']['breakpoints']['items'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Breakpoints JSON'),
      '#description' => $this->t('JSON object of breakpoint => options.'),
      '#default_value' => $options['breakpoints'] ?? '',
    ];

    $form['options']['reducedMotion'] = [
      '#type' => 'details',
      '#title' => $this->t('Reduced motion'),
      '#open' => FALSE,
    ];
    $form['options']['reducedMotion']['speed'] = [
      '#type' => 'number',
      '#title' => $this->t('Speed (ms)'),
      '#default_value' => $options['reducedMotion']['speed'] ?? '',
    ];
    $form['options']['reducedMotion']['rewindSpeed'] = [
      '#type' => 'number',
      '#title' => $this->t('Rewind speed (ms)'),
      '#default_value' => $options['reducedMotion']['rewindSpeed'] ?? '',
    ];
    $form['options']['reducedMotion']['autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay'),
      '#default_value' => $options['reducedMotion']['autoplay'] ?? FALSE,
    ];

    $form['options']['classes'] = [
      '#type' => 'details',
      '#title' => $this->t('Classes'),
      '#open' => FALSE,
    ];
    $form['options']['classes']['items'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Classes (key: value per line)'),
      '#default_value' => $options['classes'] ?? '',
    ];

    $form['options']['i18n'] = [
      '#type' => 'details',
      '#title' => $this->t('i18n'),
      '#open' => FALSE,
    ];
    $form['options']['i18n']['items'] = [
      '#type' => 'textarea',
      '#title' => $this->t('i18n (key: value per line)'),
      '#default_value' => $options['i18n'] ?? '',
    ];

    return parent::form($form, $form_state);
  }

  /**
   * AJAX callback to refresh node autocomplete.
   */
  public function updateNodeAutocomplete(array &$form, FormStateInterface $form_state): array {
    return $form['content']['node']['items_wrapper'];
  }

  /**
   * Add one more node autocomplete element.
   */
  public function addOneNode(array &$form, FormStateInterface $form_state): void {
    $count = $form_state->get('node_items_count') ?? 1;
    $form_state->set('node_items_count', $count + 1);
    $form_state->setRebuild();
  }

  /**
   * Get content type options.
   */
  protected function getContentTypeOptions(): array {
    $types = NodeType::loadMultiple();
    $options = [];
    foreach ($types as $type) {
      $options[$type->id()] = $type->label();
    }
    return $options;
  }

  /**
   * Load nodes for default values.
   */
  protected function loadNodesFromIds(array $ids): array {
    $ids = array_filter($ids);
    if (empty($ids)) {
      return [];
    }
    return Node::loadMultiple($ids);
  }

  /**
   * Load a single node for default value.
   */
  protected function loadSingleNodeFromId(?string $id): ?Node {
    if (empty($id)) {
      return NULL;
    }
    return Node::load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $status = parent::save($form, $form_state);

    $this->messenger()->addStatus(
      $status === SAVED_NEW
        ? $this->t('Created the %label carousel.', ['%label' => $this->entity->label()])
        : $this->t('Updated the %label carousel.', ['%label' => $this->entity->label()])
    );

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $status;
  }

}
