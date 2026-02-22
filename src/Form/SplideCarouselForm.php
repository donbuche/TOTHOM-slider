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
      '#description' => $this->t('Internal name used to identify this carousel in the admin UI.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $carousel->id(),
      '#description' => $this->t('Unique machine-readable ID for this carousel.'),
      '#machine_name' => [
        'exists' => '\Drupal\drupal_splide\Entity\SplideCarousel::load',
      ],
      '#disabled' => !$carousel->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $carousel->status(),
      '#description' => $this->t('If unchecked, the block will not render.'),
    ];

    $form['content'] = [
      '#type' => 'details',
      '#title' => $this->t('Carousel content'),
      '#open' => FALSE,
      '#tree' => TRUE,
    ];

    $form['content']['prefix'] = [
      '#type' => 'details',
      '#title' => $this->t('Prefix content'),
      '#open' => FALSE,
      '#description' => $this->t('Content shown above the carousel.'),
    ];
    $form['content']['prefix']['prefix_content'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Prefix content'),
      '#format' => $options['content']['prefix']['format'] ?? NULL,
      '#default_value' => $options['content']['prefix']['value'] ?? '',
      '#description' => $this->t('Optional formatted text displayed before the carousel.'),
    ];

    $form['content']['aria_group'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('ARIA label'),
    ];
    $form['content']['aria_group']['aria_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ARIA label'),
      '#default_value' => $options['content']['aria_label'] ?? '',
      '#description' => $this->t('Accessible label for the carousel container.'),
      '#parents' => ['content', 'aria_label'],
    ];

    $form['content']['semantics_group'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Carousel semantics'),
    ];
    $form['content']['semantics_group']['semantics'] = [
      '#type' => 'radios',
      '#parents' => ['content', 'semantics'],
      '#description' => $this->t('Use “Content carousel” when the slides are part of the main content (e.g. products, cards, gallery). Use “Decorative carousel” when the slides are purely ornamental; it will be rendered with decorative semantics.'),
      '#options' => [
        'content' => $this->t('Content carousel'),
        'decorative' => $this->t('Decorative carousel'),
      ],
      '#default_value' => $options['content']['semantics'] ?? 'content',
    ];

    $form['content']['semantics_group']['semantics_markup_content'] = [
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

    $form['content']['semantics_group']['semantics_markup_decorative'] = [
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

    $form['content']['source_group'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Content source'),
    ];
    $form['content']['source_group']['source'] = [
      '#type' => 'radios',
      '#parents' => ['content', 'source'],
      '#description' => $this->t('Select where slides are loaded from.'),
      '#options' => [
        'node' => $this->t('Content provided by nodes'),
        'views' => $this->t('Content provided by Views'),
      ],
      '#default_value' => $options['content']['source'] ?? '',
    ];

    $form['content']['source_group']['node'] = [
      '#type' => 'details',
      '#title' => $this->t('Content provided by nodes'),
      '#open' => FALSE,
      '#description' => $this->t('Pick specific nodes to render as slides.'),
      '#parents' => ['content', 'node'],
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

    $form['content']['source_group']['node']['allowed_bundles'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Allowed content types'),
      '#attributes' => ['class' => ['splide-allowed-bundles']],
      '#description' => $this->t('Select at least one content type to enable the node autocomplete below.'),
      '#tree' => TRUE,
    ];

    $form['content']['source_group']['node']['items_wrapper'] = [
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

    $form['content']['source_group']['node']['items_wrapper']['items'] = [
      '#type' => 'table',
      '#title' => $this->t('Selected nodes'),
      '#description' => $this->t('Choose and order the nodes that will appear as slides.'),
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
      $form['content']['source_group']['node']['items_wrapper']['items'][$i]['#attributes']['class'][] = 'draggable';

      $form['content']['source_group']['node']['items_wrapper']['items'][$i]['node'] = [
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

      $form['content']['source_group']['node']['items_wrapper']['items'][$i]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight'),
        '#title_display' => 'invisible',
        '#description' => $this->t('Lower weights appear first.'),
        '#default_value' => $i,
        '#attributes' => ['class' => ['splide-node-weight']],
      ];
      $form['content']['source_group']['node']['items_wrapper']['items'][$i]['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#name' => 'remove_node_' . $i,
        '#description' => $this->t('Remove this row.'),
        '#submit' => ['::removeNode'],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => '::updateNodeAutocomplete',
          'wrapper' => 'splide-node-autocomplete-wrapper',
        ],
      ];
    }

    $form['content']['source_group']['node']['items_wrapper']['add_more'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another node'),
      '#description' => $this->t('Append a new node selector row.'),
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
      $form['content']['source_group']['node']['allowed_bundles'][$bundle_id] = [
        '#type' => 'checkbox',
        '#title' => $bundle_label,
        '#default_value' => !empty($allowed_bundles_default[$bundle_id]) ? 1 : 0,
        '#ajax' => [
          'callback' => '::updateNodeAutocomplete',
          'wrapper' => 'splide-node-autocomplete-wrapper',
        ],
      ];
      $form['content']['source_group']['node']['allowed_bundles'][$bundle_id . '_view_mode'] = [
        '#type' => 'select',
        '#title' => $this->t('@type view mode', ['@type' => $bundle_label]),
        '#description' => $this->t('Optional view mode override for this content type.<br><a href=":url" target="_blank" rel="noopener noreferrer">Create a view mode</a>.', [
          ':url' => '/admin/structure/display-modes/view/add',
        ]),
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

    $form['content']['source_group']['views'] = [
      '#type' => 'details',
      '#title' => $this->t('Content provided by Views'),
      '#open' => FALSE,
      '#description' => $this->t('Render slides from a Views block display.'),
      '#parents' => ['content', 'views'],
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
    $form['content']['source_group']['views']['view_display'] = [
      '#type' => 'select',
      '#title' => $this->t('View [display]'),
      '#options' => $this->getViewDisplayOptions(),
      '#default_value' => $default_view,
      '#description' => $this->t('Select the view and display to use for this carousel.'),
      '#empty_option' => $this->t('- Select a view display -'),
    ];
    $form['content']['source_group']['views']['view_help'] = [
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

    $form['content']['suffix'] = [
      '#type' => 'details',
      '#title' => $this->t('Suffix content'),
      '#open' => FALSE,
      '#description' => $this->t('Content shown below the carousel.'),
    ];
    $form['content']['suffix']['suffix_content'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Suffix content'),
      '#format' => $options['content']['suffix']['format'] ?? NULL,
      '#default_value' => $options['content']['suffix']['value'] ?? '',
      '#description' => $this->t('Optional formatted text displayed after the carousel.'),
    ];

    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Splide options'),
      '#open' => FALSE,
      '#tree' => TRUE,
    ];

    $form['options']['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General'),
      '#open' => FALSE,
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
      '#description' => $this->optionHelp($this->t('The carousel transition type.'), 'type'),
    ];
    $form['options']['general']['start'] = [
      '#type' => 'number',
      '#title' => $this->t('Start index'),
      '#default_value' => $general['start'] ?? 0,
      '#description' => $this->optionHelp($this->t('Initial slide index.'), 'start'),
    ];
    $form['options']['general']['perPage'] = [
      '#type' => 'number',
      '#title' => $this->t('Per page'),
      '#default_value' => $general['perPage'] ?? 1,
      '#description' => $this->optionHelp($this->t('Number of slides visible per page.'), 'perpage'),
    ];
    $form['options']['general']['perMove'] = [
      '#type' => 'number',
      '#title' => $this->t('Per move'),
      '#default_value' => $general['perMove'] ?? 1,
      '#description' => $this->optionHelp($this->t('Number of slides to move per action.'), 'permove'),
    ];
    $form['options']['general']['gap'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Gap'),
      '#description' => $this->optionHelp($this->t('Space between slides (CSS size).'), 'gap'),
      '#default_value' => $general['gap'] ?? '',
    ];
    $form['options']['general']['padding'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Padding'),
      '#description' => $this->optionHelp($this->t('Inner padding around the track (CSS size or object notation).'), 'padding'),
      '#default_value' => $general['padding'] ?? '',
    ];
    $form['options']['general']['focus'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Focus'),
      '#description' => $this->optionHelp($this->t('Keeps a slide in focus (center, left, right, or index).'), 'focus'),
      '#default_value' => $general['focus'] ?? '',
    ];
    $form['options']['general']['rewind'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Rewind'),
      '#default_value' => $general['rewind'] ?? FALSE,
      '#description' => $this->optionHelp($this->t('Jump back to the first slide at the end.'), 'rewind'),
    ];
    $form['options']['general']['rewindSpeed'] = [
      '#type' => 'number',
      '#title' => $this->t('Rewind speed (ms)'),
      '#default_value' => $general['rewindSpeed'] ?? '',
      '#description' => $this->optionHelp($this->t('Transition speed when rewinding.'), 'rewindspeed'),
    ];
    $form['options']['general']['rewindByDrag'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Rewind by drag'),
      '#default_value' => $general['rewindByDrag'] ?? FALSE,
      '#description' => $this->optionHelp($this->t('Allow rewinding by dragging past the end.'), 'rewindbydrag'),
    ];
    $form['options']['general']['speed'] = [
      '#type' => 'number',
      '#title' => $this->t('Speed (ms)'),
      '#default_value' => $general['speed'] ?? 400,
      '#description' => $this->optionHelp($this->t('Transition speed.'), 'speed'),
    ];
    $form['options']['general']['easing'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Easing'),
      '#description' => $this->optionHelp($this->t('CSS easing string (e.g. ease).'), 'easing'),
      '#default_value' => $general['easing'] ?? '',
    ];
    $form['options']['general']['easingFunc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Easing function'),
      '#description' => $this->optionHelp($this->t('Custom easing function name (advanced).'), 'easingfunc'),
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
      '#description' => $this->optionHelp($this->t('Carousel width (CSS size).'), 'width'),
      '#default_value' => $layout['width'] ?? '',
    ];
    $form['options']['layout']['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#description' => $this->optionHelp($this->t('Carousel height (CSS size).'), 'height'),
      '#default_value' => $layout['height'] ?? '',
    ];
    $form['options']['layout']['fixedWidth'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fixed width'),
      '#description' => $this->optionHelp($this->t('Fixed slide width (CSS size).'), 'fixedwidth'),
      '#default_value' => $layout['fixedWidth'] ?? '',
    ];
    $form['options']['layout']['fixedHeight'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fixed height'),
      '#description' => $this->optionHelp($this->t('Fixed slide height (CSS size).'), 'fixedheight'),
      '#default_value' => $layout['fixedHeight'] ?? '',
    ];
    $form['options']['layout']['heightRatio'] = [
      '#type' => 'number',
      '#step' => '0.01',
      '#title' => $this->t('Height ratio'),
      '#description' => $this->optionHelp($this->t('Slide height based on carousel width.'), 'heightratio'),
      '#default_value' => $layout['heightRatio'] ?? '',
    ];
    $form['options']['layout']['autoWidth'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto width'),
      '#default_value' => $layout['autoWidth'] ?? FALSE,
      '#description' => $this->optionHelp($this->t('Let each slide define its own width.'), 'autowidth'),
    ];
    $form['options']['layout']['autoHeight'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto height'),
      '#default_value' => $layout['autoHeight'] ?? FALSE,
      '#description' => $this->optionHelp($this->t('Let the carousel height adapt to slides.'), 'autoheight'),
    ];
    $form['options']['layout']['cover'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cover'),
      '#default_value' => $layout['cover'] ?? FALSE,
      '#description' => $this->optionHelp($this->t('Covers slides with the image like object-fit: cover.'), 'cover'),
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
      '#description' => $this->optionHelp($this->t('How to trim empty space at the end.'), 'trimspace'),
    ];
    $form['options']['layout']['omitEnd'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Omit end'),
      '#default_value' => $layout['omitEnd'] ?? FALSE,
      '#description' => $this->optionHelp($this->t('Omit extra space at the end of the track.'), 'omitend'),
    ];
    $form['options']['layout']['clones'] = [
      '#type' => 'number',
      '#title' => $this->t('Clones'),
      '#default_value' => $layout['clones'] ?? '',
      '#description' => $this->optionHelp($this->t('Number of clone slides for loop mode.'), 'clones'),
    ];
    $form['options']['layout']['cloneStatus'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Clone status'),
      '#default_value' => $layout['cloneStatus'] ?? TRUE,
      '#description' => $this->optionHelp($this->t('Add status classes to clone slides.'), 'clonestatus'),
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
      '#description' => $this->optionHelp($this->t('Show previous/next arrows.'), 'arrows'),
    ];
    $form['options']['navigation']['pagination'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pagination'),
      '#default_value' => $navigation['pagination'] ?? TRUE,
      '#description' => $this->optionHelp($this->t('Show pagination bullets.'), 'pagination'),
    ];
    $form['options']['navigation']['paginationKeyboard'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pagination keyboard'),
      '#default_value' => $navigation['paginationKeyboard'] ?? TRUE,
      '#description' => $this->optionHelp($this->t('Enable keyboard control for pagination.'), 'paginationkeyboard'),
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
      '#description' => $this->optionHelp($this->t('Direction of pagination items.'), 'paginationdirection'),
    ];
    $form['options']['navigation']['arrowPath'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Arrow path'),
      '#description' => $this->optionHelp($this->t('Custom SVG path for arrow icons.'), 'arrowpath'),
      '#default_value' => $navigation['arrowPath'] ?? '',
    ];
    $form['options']['navigation']['slideFocus'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Slide focus'),
      '#default_value' => $navigation['slideFocus'] ?? TRUE,
      '#description' => $this->optionHelp($this->t('Focus the active slide for accessibility.'), 'slidefocus'),
    ];
    $form['options']['navigation']['isNavigation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Is navigation'),
      '#default_value' => $navigation['isNavigation'] ?? FALSE,
      '#description' => $this->optionHelp($this->t('Make this carousel act as navigation for another. Disable pagination if you enable this option. Otherwise roles and ARIA attributes will be messed up.'), 'isnavigation'),
    ];
    $form['options']['navigation']['focusableNodes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Focusable nodes'),
      '#description' => $this->optionHelp($this->t('CSS selectors of focusable elements.'), 'focusablenodes'),
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
      '#description' => $this->optionHelp($this->t('Automatically advance slides.'), 'autoplay'),
    ];
    $form['options']['autoplay']['interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Interval (ms)'),
      '#default_value' => $autoplay['interval'] ?? 5000,
      '#description' => $this->optionHelp($this->t('Delay between autoplay moves.'), 'interval'),
    ];
    $form['options']['autoplay']['pauseOnHover'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause on hover'),
      '#default_value' => $autoplay['pauseOnHover'] ?? TRUE,
      '#description' => $this->optionHelp($this->t('Pause autoplay when the pointer is over the carousel.'), 'pauseonhover'),
    ];
    $form['options']['autoplay']['pauseOnFocus'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause on focus'),
      '#default_value' => $autoplay['pauseOnFocus'] ?? TRUE,
      '#description' => $this->optionHelp($this->t('Pause autoplay when the carousel or controls receive focus.'), 'pauseonfocus'),
    ];
    $form['options']['autoplay']['resetProgress'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reset progress'),
      '#default_value' => $autoplay['resetProgress'] ?? TRUE,
      '#description' => $this->optionHelp($this->t('Reset autoplay progress after user interaction.'), 'resetprogress'),
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
      '#description' => $this->optionHelp($this->t('Lazy-load slide images.'), 'lazyload'),
    ];
    $form['options']['lazy']['preloadPages'] = [
      '#type' => 'number',
      '#title' => $this->t('Preload pages'),
      '#default_value' => $lazy['preloadPages'] ?? '',
      '#description' => $this->optionHelp($this->t('Number of pages to preload when lazy-loading.'), 'preloadpages'),
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
      '#description' => $this->optionHelp($this->t('Enable drag interaction.'), 'drag'),
    ];
    $form['options']['drag']['snap'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Snap'),
      '#default_value' => $drag['snap'] ?? TRUE,
      '#description' => $this->optionHelp($this->t('Snap to slides when dragging.'), 'snap'),
    ];
    $form['options']['drag']['noDrag'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No drag selectors'),
      '#default_value' => $drag['noDrag'] ?? '',
      '#description' => $this->optionHelp($this->t('CSS selectors that disable dragging.'), 'nodrag'),
    ];
    $form['options']['drag']['dragMinThreshold'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Drag min threshold'),
      '#default_value' => $drag['dragMinThreshold'] ?? '',
      '#description' => $this->optionHelp($this->t('Minimum distance before a drag is recognized.'), 'dragminthreshold'),
    ];
    $form['options']['drag']['flickPower'] = [
      '#type' => 'number',
      '#title' => $this->t('Flick power'),
      '#default_value' => $drag['flickPower'] ?? '',
      '#description' => $this->optionHelp($this->t('Flick velocity multiplier.'), 'flickpower'),
    ];
    $form['options']['drag']['flickMaxPages'] = [
      '#type' => 'number',
      '#title' => $this->t('Flick max pages'),
      '#default_value' => $drag['flickMaxPages'] ?? '',
      '#description' => $this->optionHelp($this->t('Maximum pages to move by flick.'), 'flickmaxpages'),
    ];
    $form['options']['drag']['waitForTransition'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Wait for transition'),
      '#default_value' => $drag['waitForTransition'] ?? TRUE,
      '#description' => $this->optionHelp($this->t('Block input while transition is running.'), 'waitfortransition'),
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
      '#description' => $this->optionHelp($this->t('Enable mouse wheel control.'), 'wheel'),
    ];
    $form['options']['drag']['wheelMinThreshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Wheel min threshold'),
      '#default_value' => $drag['wheelMinThreshold'] ?? '',
      '#description' => $this->optionHelp($this->t('Minimum wheel delta to trigger a move.'), 'wheelminthreshold'),
    ];
    $form['options']['drag']['wheelSleep'] = [
      '#type' => 'number',
      '#title' => $this->t('Wheel sleep (ms)'),
      '#default_value' => $drag['wheelSleep'] ?? '',
      '#description' => $this->optionHelp($this->t('Sleep time after a wheel interaction.'), 'wheelsleep'),
    ];
    $form['options']['drag']['releaseWheel'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Release wheel'),
      '#default_value' => $drag['releaseWheel'] ?? FALSE,
      '#description' => $this->optionHelp($this->t('Release wheel control when at the edges.'), 'releasewheel'),
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
      '#description' => $this->optionHelp($this->t('ARIA role for the root element.'), 'role'),
    ];
    $form['options']['accessibility']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $accessibility['label'] ?? '',
      '#description' => $this->optionHelp($this->t('ARIA label for the root element.'), 'label'),
    ];
    $form['options']['accessibility']['labelledby'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Labelledby'),
      '#default_value' => $accessibility['labelledby'] ?? '',
      '#description' => $this->optionHelp($this->t('ARIA labelledby for the root element.'), 'labelledby'),
    ];
    $form['options']['accessibility']['focusableNodes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Focusable nodes'),
      '#description' => $this->optionHelp($this->t('CSS selectors of focusable elements.'), 'focusablenodes'),
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
      '#description' => $this->optionHelp($this->t('Slide direction.'), 'direction'),
    ];
    $form['options']['behavior']['mediaQuery'] = [
      '#type' => 'select',
      '#title' => $this->t('Media query'),
      '#options' => [
        'min' => $this->t('min'),
        'max' => $this->t('max'),
      ],
      '#default_value' => $behavior['mediaQuery'] ?? 'max',
      '#description' => $this->optionHelp($this->t('How breakpoints are interpreted.'), 'mediaquery'),
    ];
    $form['options']['behavior']['updateOnMove'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Update on move'),
      '#default_value' => $behavior['updateOnMove'] ?? TRUE,
      '#description' => $this->optionHelp($this->t('Update components during move.'), 'updateonmove'),
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
      '#description' => $this->optionHelp($this->t('Enable keyboard navigation.'), 'keyboard'),
    ];
    $form['options']['behavior']['live'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Live'),
      '#default_value' => $behavior['live'] ?? TRUE,
      '#description' => $this->optionHelp($this->t('Enable aria-live updates.'), 'live'),
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
      '#description' => $this->optionHelp($this->t('Destroy the carousel under certain conditions.'), 'destroy'),
    ];

    $form['options']['breakpoints'] = [
      '#type' => 'details',
      '#title' => $this->t('Breakpoints'),
      '#open' => FALSE,
    ];
    $form['options']['breakpoints']['items'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Breakpoints JSON'),
      '#description' => $this->optionHelp($this->t('JSON object of breakpoint => options.'), 'breakpoints'),
      '#default_value' => $options['breakpoints']['items'] ?? '',
    ];

    $form['options']['reducedMotion'] = [
      '#type' => 'details',
      '#title' => $this->t('Reduced motion'),
      '#open' => FALSE,
      '#description' => $this->t('These options apply only when the operating system has “Reduce motion” enabled.'),
    ];
    $form['options']['reducedMotion']['speed'] = [
      '#type' => 'number',
      '#title' => $this->t('Speed (ms)'),
      '#default_value' => $options['reducedMotion']['speed'] ?? '',
      '#description' => $this->optionHelp($this->t('Transition speed when reduced motion is preferred.'), 'reducedmotion'),
    ];
    $form['options']['reducedMotion']['rewindSpeed'] = [
      '#type' => 'number',
      '#title' => $this->t('Rewind speed (ms)'),
      '#default_value' => $options['reducedMotion']['rewindSpeed'] ?? '',
      '#description' => $this->optionHelp($this->t('Rewind speed with reduced motion.'), 'reducedmotion'),
    ];
    $form['options']['reducedMotion']['autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay'),
      '#default_value' => $options['reducedMotion']['autoplay'] ?? FALSE,
      '#description' => $this->optionHelp($this->t('Disable autoplay when reduced motion is preferred.'), 'reducedmotion'),
    ];

    $form['options']['classes'] = [
      '#type' => 'details',
      '#title' => $this->t('Classes'),
      '#open' => FALSE,
      '#description' => $this->optionHelp($this->t('Add classes to append to Splide defaults. Default classes are always included. You can enter multiple classes separated by spaces.'), 'classes'),
    ];
    $form['options']['classes']['items'] = [
      '#type' => 'table',
      '#title' => $this->t('Custom classes'),
      '#header' => [
        $this->t('Key'),
        $this->t('Custom classes'),
        $this->t('Default classes'),
      ],
    ];
    $classes_defaults = $this->getSplideClassesDefaults();
    $classes_custom = $options['classes']['items'] ?? [];
    foreach ($classes_defaults as $key => $default) {
      $form['options']['classes']['items'][$key]['key'] = [
        '#type' => 'item',
        '#markup' => '<code>' . $key . '</code>',
      ];
      $form['options']['classes']['items'][$key]['custom'] = [
        '#type' => 'textfield',
        '#default_value' => $classes_custom[$key] ?? '',
        '#placeholder' => $this->t('e.g. my-custom-class'),
      ];
      $form['options']['classes']['items'][$key]['default'] = [
        '#type' => 'item',
        '#markup' => '<code>' . $default . '</code>',
      ];
    }

    $form['options']['i18n'] = [
      '#type' => 'details',
      '#title' => $this->t('i18n'),
      '#open' => FALSE,
      '#description' => $this->optionHelp($this->t('Override Splide’s default interface strings (defaults are in English). Leave blank to keep the defaults.'), 'i18n'),
    ];
    $form['options']['i18n']['items'] = [
      '#type' => 'table',
      '#title' => $this->t('i18n strings'),
      '#description' => $this->optionHelp($this->t('Override built-in i18n strings.'), 'i18n'),
      '#header' => [
        $this->t('Key'),
        $this->t('Text'),
        $this->t('Used for'),
      ],
    ];
    $i18n_defaults = $options['i18n']['items'] ?? [];
    $i18n_help = $this->getSplideI18nHelp();
    foreach ($i18n_help as $key => $meta) {
      $form['options']['i18n']['items'][$key]['key'] = [
        '#type' => 'item',
        '#markup' => '<code>' . $key . '</code>',
      ];
      $form['options']['i18n']['items'][$key]['text'] = [
        '#type' => 'textfield',
        '#default_value' => $i18n_defaults[$key] ?? '',
        '#placeholder' => $meta['default'] ?? '',
      ];
      $form['options']['i18n']['items'][$key]['usage'] = [
        '#type' => 'item',
        '#markup' => $meta['usage'] ?? '',
      ];
    }

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
    return $form['content']['source_group']['node']['items_wrapper'];
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
   * Builds a description with a link to Splide options docs.
   */
  protected function optionHelp($text, string $anchor): string {
    $url = 'https://splidejs.com/guides/options/#' . $anchor;
    return (string) $this->t('@text<br><a href=":url" target="_blank" rel="noopener noreferrer">Read docs</a> for further information.', [
      '@text' => $text,
      ':url' => $url,
    ]);
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
   * Returns Splide i18n keys with default text and usage notes.
   */
  protected function getSplideI18nHelp(): array {
    return [
      'prev' => [
        'default' => $this->t('Previous slide'),
        'usage' => $this->t('aria-label of the previous arrow'),
      ],
      'next' => [
        'default' => $this->t('Next slide'),
        'usage' => $this->t('aria-label of the next arrow'),
      ],
      'first' => [
        'default' => $this->t('Go to first slide'),
        'usage' => $this->t('aria-label of a navigation item'),
      ],
      'last' => [
        'default' => $this->t('Go to last slide'),
        'usage' => $this->t('aria-label of a navigation item'),
      ],
      'slideX' => [
        'default' => $this->t('Go to slide %s'),
        'usage' => $this->t('aria-label of pagination or each navigation item'),
      ],
      'pageX' => [
        'default' => $this->t('Go to page %s'),
        'usage' => $this->t('aria-label of pagination'),
      ],
      'play' => [
        'default' => $this->t('Start autoplay'),
        'usage' => $this->t('aria-label of the autoplay toggle button'),
      ],
      'pause' => [
        'default' => $this->t('Pause autoplay'),
        'usage' => $this->t('aria-label of the autoplay toggle button'),
      ],
      'carousel' => [
        'default' => $this->t('carousel'),
        'usage' => $this->t('aria-roledescription of the root element'),
      ],
      'select' => [
        'default' => $this->t('Select a slide to show'),
        'usage' => $this->t('aria-label of pagination'),
      ],
      'slide' => [
        'default' => $this->t('slide'),
        'usage' => $this->t('aria-roledescription of each slide'),
      ],
      'slideLabel' => [
        'default' => $this->t('%s of %s'),
        'usage' => $this->t('aria-label of each slide as {slide number} of {slide length}'),
      ],
    ];
  }

  /**
   * Returns Splide default classes for configurable elements.
   */
  protected function getSplideClassesDefaults(): array {
    return [
      'arrows' => 'splide__arrows',
      'arrow' => 'splide__arrow',
      'prev' => 'splide__arrow--prev',
      'next' => 'splide__arrow--next',
      'pagination' => 'splide__pagination',
      'page' => 'splide__pagination__page',
    ];
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

    if (!empty($options['i18n']['items']) && is_array($options['i18n']['items'])) {
      $i18n = [];
      foreach ($options['i18n']['items'] as $key => $row) {
        if (!is_array($row) || empty($row['text'])) {
          continue;
        }
        $i18n[$key] = $row['text'];
      }
      $options['i18n']['items'] = $i18n;
    }

    if (!empty($options['classes']['items']) && is_array($options['classes']['items'])) {
      $classes = [];
      foreach ($options['classes']['items'] as $key => $row) {
        if (!is_array($row)) {
          continue;
        }
        $custom = trim((string) ($row['custom'] ?? ''));
        if ($custom !== '') {
          $classes[$key] = $custom;
        }
      }
      $options['classes']['items'] = $classes;
    }

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
    $prefix_raw = $content_raw['prefix']['prefix_content'] ?? [];
    $suffix_raw = $content_raw['suffix']['suffix_content'] ?? [];
    $content = [
      'aria_label' => $content_raw['aria_label'] ?? '',
      'semantics' => $content_raw['semantics'] ?? '',
      'source' => $source,
      'prefix' => [
        'value' => $prefix_raw['value'] ?? '',
        'format' => $prefix_raw['format'] ?? NULL,
      ],
      'suffix' => [
        'value' => $suffix_raw['value'] ?? '',
        'format' => $suffix_raw['format'] ?? NULL,
      ],
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
