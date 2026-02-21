<?php

namespace Drupal\drupal_splide\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\views\Views;

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
    $general = $options['general'] ?? [];
    $layout = $options['layout'] ?? [];
    $navigation = $options['navigation'] ?? [];
    $autoplay = $options['autoplay'] ?? [];
    $lazy = $options['lazy'] ?? [];
    $drag = $options['drag'] ?? [];
    $accessibility = $options['accessibility'] ?? [];
    $behavior = $options['behavior'] ?? [];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Administrative title'),
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

    $form['content']['aria_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ARIA label'),
      '#default_value' => $options['content']['aria_label'] ?? '',
      '#description' => $this->t('Accessible label for the carousel container.'),
    ];

    $form['content']['semantics'] = [
      '#type' => 'radios',
      '#title' => $this->t('Carousel semantics'),
      '#description' => $this->t('Use “Content carousel” when the slides are part of the main content (e.g. products, cards, gallery). Use “Decorative carousel” when the slides are purely ornamental; it will be rendered with decorative semantics.'),
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
    $user_input = $form_state->getUserInput();
    $allowed_bundles_input = $user_input['content']['node']['allowed_bundles'] ?? NULL;
    $allowed_bundles_source = is_array($allowed_bundles_input) ? $allowed_bundles_input : $allowed_bundles_default;
    $allowed_bundles = array_filter($allowed_bundles_source);
    $allowed_bundles = array_filter($allowed_bundles, static function ($value, $key) {
      return is_string($key) && $value && !str_ends_with($key, '_view_mode');
    }, ARRAY_FILTER_USE_BOTH);
    $allowed_bundles_list = array_keys($allowed_bundles);

    $form['content']['node']['allowed_bundles'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Allowed content types'),
      '#attributes' => ['class' => ['splide-allowed-bundles']],
      '#description' => $this->t('Select at least one content type to enable the node autocomplete below.'),
      '#tree' => TRUE,
    ];

    $form['content']['node']['items_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'splide-node-autocomplete-wrapper'],
    ];

    $default_node_ids = $options['content']['node']['items'] ?? [];
    if (!empty($default_node_ids) && is_array($default_node_ids) && isset($default_node_ids[0]['id'])) {
      $default_node_ids = array_column($default_node_ids, 'id');
    }
    if (!empty($default_node_ids)) {
      $valid_nodes = Node::loadMultiple($default_node_ids);
      $default_node_ids = array_values(array_keys($valid_nodes));
    }
    $items_count = $form_state->get('node_items_count');
    if ($items_count === NULL) {
      $items_count = max(1, count($default_node_ids));
      $form_state->set('node_items_count', $items_count);
    }

    $form['content']['node']['items_wrapper']['items'] = [
      '#type' => 'table',
      '#title' => $this->t('Selected nodes'),
      '#header' => [
        $this->t('Node title'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'splide-node-weight',
        ],
      ],
    ];

    for ($i = 0; $i < $items_count; $i++) {
      $form['content']['node']['items_wrapper']['items'][$i]['#attributes']['class'][] = 'draggable';

      $form['content']['node']['items_wrapper']['items'][$i]['node'] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('Node title'),
        '#title_display' => 'invisible',
        '#target_type' => 'node',
        '#selection_settings' => [
          'target_bundles' => $allowed_bundles_list,
        ],
        '#default_value' => $this->loadSingleNodeFromId($default_node_ids[$i] ?? NULL),
        '#description' => $this->t('Start typing to search nodes.'),
      ];

      $form['content']['node']['items_wrapper']['items'][$i]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight'),
        '#title_display' => 'invisible',
        '#default_value' => $i,
        '#attributes' => ['class' => ['splide-node-weight']],
      ];
      $form['content']['node']['items_wrapper']['items'][$i]['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#name' => 'remove_node_' . $i,
        '#submit' => ['::removeNode'],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => '::updateNodeAutocomplete',
          'wrapper' => 'splide-node-autocomplete-wrapper',
        ],
      ];
    }

    $form['content']['node']['items_wrapper']['add_more'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another node'),
      '#submit' => ['::addOneNode'],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => '::updateNodeAutocomplete',
        'wrapper' => 'splide-node-autocomplete-wrapper',
      ],
    ];

    $view_mode_options = $this->getNodeViewModeOptions();
    $saved_view_modes = $options['content']['node']['view_modes'] ?? [];
    foreach ($this->getContentTypeOptions() as $bundle_id => $bundle_label) {
      $form['content']['node']['allowed_bundles'][$bundle_id] = [
        '#type' => 'checkbox',
        '#title' => $bundle_label,
        '#default_value' => !empty($allowed_bundles_default[$bundle_id]) ? 1 : 0,
        '#ajax' => [
          'callback' => '::updateNodeAutocomplete',
          'wrapper' => 'splide-node-autocomplete-wrapper',
        ],
      ];
      $form['content']['node']['allowed_bundles'][$bundle_id . '_view_mode'] = [
        '#type' => 'select',
        '#title' => $this->t('@type view mode', ['@type' => $bundle_label]),
        '#options' => $view_mode_options,
        '#default_value' => $saved_view_modes[$bundle_id] ?? '',
        '#empty_option' => $this->t('- Use default -'),
        '#states' => [
          'visible' => [
            ':input[name="content[node][allowed_bundles][' . $bundle_id . ']"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

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
    $default_view = '';
    if (!empty($options['content']['views']['view_machine_name']) && !empty($options['content']['views']['view_display_name'])) {
      $default_view = $options['content']['views']['view_machine_name'] . ':' . $options['content']['views']['view_display_name'];
    }
    $form['content']['views']['view_display'] = [
      '#type' => 'select',
      '#title' => $this->t('View [display]'),
      '#options' => $this->getViewDisplayOptions(),
      '#default_value' => $default_view,
      '#description' => $this->t('Select the view and display to use for this carousel.'),
      '#empty_option' => $this->t('- Select a view display -'),
    ];
    $form['content']['views']['view_help'] = [
      '#type' => 'details',
      '#title' => $this->t('How to create a view'),
      '#open' => FALSE,
      '#markup' => ''
        . '<p>1. Go to Structure → Views and click on "<a target="_blank" href="/admin/structure/views/add">Add view</a>".</p>'
        . '<ul>'
        . '<li>' . $this->t('Choose the content type you want to show in the carousel.') . '</li>'
        . '<li>' . $this->t('Add a Block display.') . '</li>'
        . '<li>' . $this->t('Do not use pagination.') . '</li>'
        . '</ul>'
        . '<p>2. ' . $this->t('Once in the View configuration page:') . '</p>'
        . '<ul>'
        . '<li>' . $this->t('Under “Display format”, pick “Unformatted list” (any format will work, but “Unformatted list” works best).') . '</li>'
        . '<li>' . $this->t('Set “Show” to “Fields” (do not use “Content”).') . '</li>'
        . '<li>' . $this->t('Add the fields you want to appear in each slide (e.g. title, image, body).') . '</li>'
        . '<li>' . $this->t('Save the view.') . '</li>'
        . '</ul>'
        . '<p>3. ' . $this->t('Refresh this page and select it from the dropdown above.') . '</p>',
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
      '#default_value' => $general['type'] ?? 'slide',
    ];
    $form['options']['general']['start'] = [
      '#type' => 'number',
      '#title' => $this->t('Start index'),
      '#default_value' => $general['start'] ?? 0,
    ];
    $form['options']['general']['perPage'] = [
      '#type' => 'number',
      '#title' => $this->t('Per page'),
      '#default_value' => $general['perPage'] ?? 1,
    ];
    $form['options']['general']['perMove'] = [
      '#type' => 'number',
      '#title' => $this->t('Per move'),
      '#default_value' => $general['perMove'] ?? 1,
    ];
    $form['options']['general']['gap'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Gap'),
      '#description' => $this->t('CSS size, e.g. 1rem or 10px.'),
      '#default_value' => $general['gap'] ?? '',
    ];
    $form['options']['general']['padding'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Padding'),
      '#description' => $this->t('CSS size or object notation (leave empty for none).'),
      '#default_value' => $general['padding'] ?? '',
    ];
    $form['options']['general']['focus'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Focus'),
      '#description' => $this->t('center, left, right, number, or empty.'),
      '#default_value' => $general['focus'] ?? '',
    ];
    $form['options']['general']['rewind'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Rewind'),
      '#default_value' => $general['rewind'] ?? FALSE,
    ];
    $form['options']['general']['rewindSpeed'] = [
      '#type' => 'number',
      '#title' => $this->t('Rewind speed (ms)'),
      '#default_value' => $general['rewindSpeed'] ?? '',
    ];
    $form['options']['general']['rewindByDrag'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Rewind by drag'),
      '#default_value' => $general['rewindByDrag'] ?? FALSE,
    ];
    $form['options']['general']['speed'] = [
      '#type' => 'number',
      '#title' => $this->t('Speed (ms)'),
      '#default_value' => $general['speed'] ?? 400,
    ];
    $form['options']['general']['easing'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Easing'),
      '#description' => $this->t('CSS easing string, e.g. ease.'),
      '#default_value' => $general['easing'] ?? '',
    ];
    $form['options']['general']['easingFunc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Easing function'),
      '#description' => $this->t('JS function name (advanced).'),
      '#default_value' => $general['easingFunc'] ?? '',
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
      '#default_value' => $layout['width'] ?? '',
    ];
    $form['options']['layout']['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#description' => $this->t('CSS size (e.g. 400px).'),
      '#default_value' => $layout['height'] ?? '',
    ];
    $form['options']['layout']['fixedWidth'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fixed width'),
      '#default_value' => $layout['fixedWidth'] ?? '',
    ];
    $form['options']['layout']['fixedHeight'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fixed height'),
      '#default_value' => $layout['fixedHeight'] ?? '',
    ];
    $form['options']['layout']['heightRatio'] = [
      '#type' => 'number',
      '#step' => '0.01',
      '#title' => $this->t('Height ratio'),
      '#default_value' => $layout['heightRatio'] ?? '',
    ];
    $form['options']['layout']['autoWidth'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto width'),
      '#default_value' => $layout['autoWidth'] ?? FALSE,
    ];
    $form['options']['layout']['autoHeight'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto height'),
      '#default_value' => $layout['autoHeight'] ?? FALSE,
    ];
    $form['options']['layout']['cover'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cover'),
      '#default_value' => $layout['cover'] ?? FALSE,
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
      '#default_value' => $layout['trimSpace'] ?? 'true',
    ];
    $form['options']['layout']['omitEnd'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Omit end'),
      '#default_value' => $layout['omitEnd'] ?? FALSE,
    ];
    $form['options']['layout']['clones'] = [
      '#type' => 'number',
      '#title' => $this->t('Clones'),
      '#default_value' => $layout['clones'] ?? '',
    ];
    $form['options']['layout']['cloneStatus'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Clone status'),
      '#default_value' => $layout['cloneStatus'] ?? TRUE,
    ];

    $form['options']['navigation'] = [
      '#type' => 'details',
      '#title' => $this->t('Navigation'),
      '#open' => FALSE,
    ];
    $form['options']['navigation']['arrows'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Arrows'),
      '#default_value' => $navigation['arrows'] ?? TRUE,
    ];
    $form['options']['navigation']['pagination'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pagination'),
      '#default_value' => $navigation['pagination'] ?? TRUE,
    ];
    $form['options']['navigation']['paginationKeyboard'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pagination keyboard'),
      '#default_value' => $navigation['paginationKeyboard'] ?? TRUE,
    ];
    $form['options']['navigation']['paginationDirection'] = [
      '#type' => 'select',
      '#title' => $this->t('Pagination direction'),
      '#options' => [
        'ltr' => $this->t('ltr'),
        'rtl' => $this->t('rtl'),
        'ttb' => $this->t('ttb'),
      ],
      '#default_value' => $navigation['paginationDirection'] ?? 'ltr',
    ];
    $form['options']['navigation']['arrowPath'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Arrow path'),
      '#description' => $this->t('SVG path string.'),
      '#default_value' => $navigation['arrowPath'] ?? '',
    ];
    $form['options']['navigation']['slideFocus'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Slide focus'),
      '#default_value' => $navigation['slideFocus'] ?? TRUE,
    ];
    $form['options']['navigation']['isNavigation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Is navigation'),
      '#default_value' => $navigation['isNavigation'] ?? FALSE,
    ];
    $form['options']['navigation']['focusableNodes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Focusable nodes'),
      '#description' => $this->t('CSS selectors, comma-separated.'),
      '#default_value' => $navigation['focusableNodes'] ?? '',
    ];

    $form['options']['autoplay'] = [
      '#type' => 'details',
      '#title' => $this->t('Autoplay'),
      '#open' => FALSE,
    ];
    $form['options']['autoplay']['autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay'),
      '#default_value' => $autoplay['autoplay'] ?? FALSE,
    ];
    $form['options']['autoplay']['interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Interval (ms)'),
      '#default_value' => $autoplay['interval'] ?? 5000,
    ];
    $form['options']['autoplay']['pauseOnHover'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause on hover'),
      '#default_value' => $autoplay['pauseOnHover'] ?? TRUE,
    ];
    $form['options']['autoplay']['pauseOnFocus'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause on focus'),
      '#default_value' => $autoplay['pauseOnFocus'] ?? TRUE,
    ];
    $form['options']['autoplay']['resetProgress'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reset progress'),
      '#default_value' => $autoplay['resetProgress'] ?? TRUE,
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
      '#default_value' => $lazy['lazyLoad'] ?? '',
    ];
    $form['options']['lazy']['preloadPages'] = [
      '#type' => 'number',
      '#title' => $this->t('Preload pages'),
      '#default_value' => $lazy['preloadPages'] ?? '',
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
      '#default_value' => $drag['drag'] ?? 'true',
    ];
    $form['options']['drag']['snap'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Snap'),
      '#default_value' => $drag['snap'] ?? TRUE,
    ];
    $form['options']['drag']['noDrag'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No drag selectors'),
      '#default_value' => $drag['noDrag'] ?? '',
    ];
    $form['options']['drag']['dragMinThreshold'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Drag min threshold'),
      '#default_value' => $drag['dragMinThreshold'] ?? '',
    ];
    $form['options']['drag']['flickPower'] = [
      '#type' => 'number',
      '#title' => $this->t('Flick power'),
      '#default_value' => $drag['flickPower'] ?? '',
    ];
    $form['options']['drag']['flickMaxPages'] = [
      '#type' => 'number',
      '#title' => $this->t('Flick max pages'),
      '#default_value' => $drag['flickMaxPages'] ?? '',
    ];
    $form['options']['drag']['waitForTransition'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Wait for transition'),
      '#default_value' => $drag['waitForTransition'] ?? TRUE,
    ];
    $form['options']['drag']['wheel'] = [
      '#type' => 'select',
      '#title' => $this->t('Wheel'),
      '#options' => [
        'true' => $this->t('true'),
        'false' => $this->t('false'),
        'global' => $this->t('global'),
      ],
      '#default_value' => $drag['wheel'] ?? 'false',
    ];
    $form['options']['drag']['wheelMinThreshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Wheel min threshold'),
      '#default_value' => $drag['wheelMinThreshold'] ?? '',
    ];
    $form['options']['drag']['wheelSleep'] = [
      '#type' => 'number',
      '#title' => $this->t('Wheel sleep (ms)'),
      '#default_value' => $drag['wheelSleep'] ?? '',
    ];
    $form['options']['drag']['releaseWheel'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Release wheel'),
      '#default_value' => $drag['releaseWheel'] ?? FALSE,
    ];

    $form['options']['accessibility'] = [
      '#type' => 'details',
      '#title' => $this->t('Accessibility'),
      '#open' => FALSE,
    ];
    $form['options']['accessibility']['role'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Role'),
      '#default_value' => $accessibility['role'] ?? '',
    ];
    $form['options']['accessibility']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $accessibility['label'] ?? '',
    ];
    $form['options']['accessibility']['labelledby'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Labelledby'),
      '#default_value' => $accessibility['labelledby'] ?? '',
    ];
    $form['options']['accessibility']['focusableNodes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Focusable nodes'),
      '#description' => $this->t('CSS selectors, comma-separated.'),
      '#default_value' => $accessibility['focusableNodes'] ?? '',
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
      '#default_value' => $behavior['direction'] ?? 'ltr',
    ];
    $form['options']['behavior']['mediaQuery'] = [
      '#type' => 'select',
      '#title' => $this->t('Media query'),
      '#options' => [
        'min' => $this->t('min'),
        'max' => $this->t('max'),
      ],
      '#default_value' => $behavior['mediaQuery'] ?? 'max',
    ];
    $form['options']['behavior']['updateOnMove'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Update on move'),
      '#default_value' => $behavior['updateOnMove'] ?? TRUE,
    ];
    $form['options']['behavior']['keyboard'] = [
      '#type' => 'select',
      '#title' => $this->t('Keyboard'),
      '#options' => [
        'true' => $this->t('true'),
        'false' => $this->t('false'),
        'global' => $this->t('global'),
      ],
      '#default_value' => $behavior['keyboard'] ?? 'false',
    ];
    $form['options']['behavior']['live'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Live'),
      '#default_value' => $behavior['live'] ?? TRUE,
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
      '#default_value' => $behavior['destroy'] ?? '',
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
      '#default_value' => $options['breakpoints']['items'] ?? '',
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
      '#default_value' => $options['classes']['items'] ?? '',
    ];

    $form['options']['i18n'] = [
      '#type' => 'details',
      '#title' => $this->t('i18n'),
      '#open' => FALSE,
    ];
    $form['options']['i18n']['items'] = [
      '#type' => 'textarea',
      '#title' => $this->t('i18n (key: value per line)'),
      '#default_value' => $options['i18n']['items'] ?? '',
    ];

    $form = parent::form($form, $form_state);
    $form['cache_notice'] = [
      '#type' => 'item',
      '#markup' => '<small class="description">' . $this->t('After creating or updating a carousel, you may need to clear caches to make the block available and apply changes.') . '</small>',
      '#weight' => 1000,
    ];

    return $form;
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
   * Remove a node row from the table.
   */
  public function removeNode(array &$form, FormStateInterface $form_state): void {
    $trigger = $form_state->getTriggeringElement();
    $name = $trigger['#name'] ?? '';
    if (preg_match('/remove_node_(\d+)/', $name, $matches)) {
      $index = (int) $matches[1];
      $values = $form_state->getValue(['content', 'node', 'items_wrapper', 'items']) ?? [];
      if (isset($values[$index])) {
        unset($values[$index]);
        $values = array_values($values);
        $form_state->setValue(['content', 'node', 'items_wrapper', 'items'], $values);
      }
      $count = max(1, ($form_state->get('node_items_count') ?? 1) - 1);
      $form_state->set('node_items_count', $count);
    }
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
   * Returns view mode options for node entities.
   */
  protected function getNodeViewModeOptions(): array {
    $repository = \Drupal::service('entity_display.repository');
    return $repository->getViewModeOptions('node');
  }

  /**
   * Builds a list of available view displays.
   */
  protected function getViewDisplayOptions(): array {
    $options = [];
    $views = Views::getAllViews();
    foreach ($views as $view_id => $view) {
      $label = $view->label() ?: $view_id;
      $displays = $view->get('display');
      foreach ($displays as $display_id => $display) {
        if (empty($display['display_plugin'])) {
          continue;
        }
        if ($display['display_plugin'] !== 'block') {
          continue;
        }
        $display_label = $display['display_title'] ?? $display_id;
        $options[$view_id . ':' . $display_id] = $label . ' — ' . '[' . $display_id . ']';
      }
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
    // Persist options and content settings.
    $options = $form_state->getValue('options') ?? [];
    $content = $form_state->getValue('content') ?? [];
    $content_raw = $content;

    // Normalize node items with weights into an ordered list.
    $node_rows = $content_raw['node']['items_wrapper']['items'] ?? [];
    $nodes = [];
    foreach ($node_rows as $row) {
      $nid = $row['node'] ?? NULL;
      if ($nid && Node::load($nid)) {
        $nodes[] = [
          'id' => $nid,
          'weight' => (int) ($row['weight'] ?? 0),
        ];
      }
    }
    if (!empty($nodes)) {
      usort($nodes, static function ($a, $b) {
        return $a['weight'] <=> $b['weight'];
      });
    }

    // Keep only the relevant content keys.
    $source = $content_raw['source'] ?? '';
    $content = [
      'aria_label' => $content_raw['aria_label'] ?? '',
      'semantics' => $content_raw['semantics'] ?? '',
      'source' => $source,
      'node' => [],
      'views' => [],
    ];

    if ($source === 'node') {
      $view_modes = [];
      foreach ($content_raw['node']['allowed_bundles'] ?? [] as $bundle_id => $enabled) {
        if (empty($enabled)) {
          continue;
        }
        if (str_ends_with($bundle_id, '_view_mode')) {
          continue;
        }
        $view_modes[$bundle_id] = $content_raw['node']['allowed_bundles'][$bundle_id . '_view_mode'] ?? '';
      }
      $content['node'] = [
        'allowed_bundles' => array_filter($content_raw['node']['allowed_bundles'] ?? [], static function ($value, $key) {
          return is_string($key) && $value && !str_ends_with($key, '_view_mode');
        }, ARRAY_FILTER_USE_BOTH),
        'items' => $nodes,
        'view_modes' => $view_modes,
      ];
    }
    elseif ($source === 'views') {
      $selected_display = $content_raw['views']['view_display'] ?? '';
      $view_machine = '';
      $view_display = '';
      if (is_string($selected_display) && strpos($selected_display, ':') !== FALSE) {
        [$view_machine, $view_display] = explode(':', $selected_display, 2);
      }
      $content['views'] = [
        'view_machine_name' => $view_machine,
        'view_display_name' => $view_display,
      ];
    }

    $options['content'] = $content;
    $this->entity->set('options', $options);

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
