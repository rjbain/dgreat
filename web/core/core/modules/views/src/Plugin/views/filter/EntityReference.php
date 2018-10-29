<?php

namespace Drupal\views\Plugin\views\filter;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\FieldAPIHandlerTrait;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter handler which allows to search on multiple fields.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("entity_reference")
 */
class EntityReference extends ManyToOne {

  use FieldAPIHandlerTrait;

  /**
   * Type for the auto complete filter format.
   */
  const AUTO_COMPLETE_TYPE = 'auto-complete';

  /**
   * Type for the select list filter format.
   */
  const SELECT_LIST_TYPE = 'select-list';

  /**
   * Type for using bundle selection for filter values.
   */
  const BUNDLE_SELECTION_TYPE = 0;

  /**
   * Type for using view selection for filter value.
   */
  const VIEW_SELECTION_TYPE = 1;

  /**
   * Validated exposed input that will be set as value in case.
   *
   * @var array
   */
  protected $validatedExposedInput;

  /**
   * The selection plugin manager service.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  protected $selectionPluginManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManger;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  protected $entityBundleInfo;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SelectionPluginManagerInterface $selection_plugin_manager, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->selectionPluginManager = $selection_plugin_manager;
    $this->entityTypeManger = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.entity_reference_selection'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {
    $form['value_selection_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Possible selection values from'),
      '#required' => TRUE,
      '#default_value' => $this->options['value_selection_type'],
      '#options' => [
        static::BUNDLE_SELECTION_TYPE => $this->t('Bundles'),
        static::VIEW_SELECTION_TYPE => $this->t('View result'),
      ],
    ];

    $handler = $this->collectFieldsReferenceHandlers();
    $entity_type = $this->getReferencedEntityType();

    // We add the option to configure the ones possible for filtering only if
    // there is an entity bundle type of the referenced entity.
    if ($bundle_type = $entity_type->getBundleEntityType()) {
      $bundles = $this->entityBundleInfo->getBundleInfo($entity_type->id());

      $bundle_options = [];
      foreach ($handler['bundles'] as $bundle) {
        $bundle_options[$bundle] = $bundles[$bundle]['label'];
      }

      $form['target_bundles'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('@bundle targets', ['@bundle' => $this->entityTypeManger->getDefinition($bundle_type)->getLabel()]),
        '#options' => $bundle_options,
        '#default_value' => $this->options['target_bundles'],
      ];

      $form['target_bundles']['#states'] = [
        'visible' => [
          ':input[name="options[value_selection_type]"]' => ['value' => static::BUNDLE_SELECTION_TYPE],
        ],
      ];
    }
    else {
      unset($form['value_selection_type']['#options'][static::BUNDLE_SELECTION_TYPE]);
    }

    $displays = Views::getApplicableViews('entity_reference_display');
    $view_storage = $this->entityTypeManger->getStorage('view');

    $view_selection_options = [];
    foreach ($displays as $data) {
      list($view_id, $display_id) = $data;
      $view = $view_storage->load($view_id);
      if (in_array($view->get('base_table'), [$entity_type->getBaseTable(), $entity_type->getDataTable()])) {
        $display = $view->get('display');
        $view_selection_options[$view_id . ':' . $display_id] = $view_id . ' - ' . $display[$display_id]['display_title'];
      }
    }

    $form['handler_view'] = [
      '#type' => 'select',
      '#title' => $this->t('View selection'),
      '#default_value' => $this->options['handler_view'],
      '#options' => $view_selection_options,
    ];

    $form['handler_view']['#states'] = [
      'visible' => [
        ':input[name="options[value_selection_type]"]' => ['value' => static::VIEW_SELECTION_TYPE],
      ],
    ];

    $form['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Selection type'),
      '#default_value' => $this->options['type'],
      '#options' => [
        self::SELECT_LIST_TYPE => $this->t('Dropdown'),
        self::AUTO_COMPLETE_TYPE => $this->t('Autocomplete'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateExtraOptionsForm($form, FormStateInterface $form_state) {
    $value_from = $form_state->getValue(['options', 'value_selection_type']);

    if (
      $value_from == static::BUNDLE_SELECTION_TYPE &&
      empty(array_filter($form_state->getValue(['options', 'target_bundles'])))
    ) {
      $form_state->setError($form['target_bundles'], $this->t('You must choose at least one target bundle.'));
    }
    elseif (
      $value_from == static::VIEW_SELECTION_TYPE &&
      empty($form_state->getValue(['options', 'handler_view']))
    ) {
      $form_state->setError($form['handler_view'], $this->t('You must choose a view handler.'));
    }

    parent::validateExtraOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $referenced_type = $this->getReferencedEntityType();

    if ($this->options['type'] == self::AUTO_COMPLETE_TYPE) {
      $form['value'] = [
        '#title' => $this->t('Select %entity_types', ['%entity_types' => $referenced_type->getPluralLabel()]),
        '#type' => 'entity_autocomplete',
        '#default_value' => $this->getDefaultSelectedEntityLabels(),
        '#tags' => TRUE,
        '#process_default_value' => FALSE,
      ] + $this->getHandlerOptions();
    }
    else {
      $options = $this->buildReferenceEntityOptions();
      $default_value = (array) $this->value;

      if ($exposed = $form_state->get('exposed')) {
        $identifier = $this->options['expose']['identifier'];

        if (!empty($this->options['expose']['reduce'])) {
          $options = $this->reduceValueOptions($options);

          if (!empty($this->options['expose']['multiple']) && empty($this->options['expose']['required'])) {
            $default_value = [];
          }
        }

        if (empty($this->options['expose']['multiple'])) {
          if (empty($this->options['expose']['required']) && (empty($default_value) || !empty($this->options['expose']['reduce']))) {
            $default_value = 'All';
          }
          elseif (empty($default_value)) {
            $keys = array_keys($options);
            $default_value = array_shift($keys);
          }
          // Due to https://www.drupal.org/node/1464174 there is a chance that
          // [''] was saved in the admin ui. Let's choose a safe default value.
          elseif ($default_value == ['']) {
            $default_value = 'All';
          }
          else {
            $copy = $default_value;
            $default_value = array_shift($copy);
          }
        }
      }

      $form['value'] = [
        '#type' => 'select',
        '#title' => $this->t('Select @entity_types', ['@entity_types' => $referenced_type->getPluralLabel()]),
        '#multiple' => TRUE,
        '#options' => $options,
        '#size' => min(9, count($options)),
        '#default_value' => $default_value,
      ];

      $user_input = $form_state->getUserInput();
      if ($exposed && isset($identifier) && !isset($user_input[$identifier])) {
        $user_input[$identifier] = $default_value;
        $form_state->setUserInput($user_input);
      }
    }

    if (!$form_state->get('exposed')) {
      // Retain the helper option.
      $this->helper->buildOptionsForm($form, $form_state);

      // Show help text if not exposed to end users.
      $form['value']['#description'] = $this->t('Leave blank for all. Otherwise, the first selected item will be the default instead of "Any".');
    }
  }

  /**
   * Gets the default value for auto-complete field.
   *
   * @return string
   *   The auto-complete value.
   */
  protected function getDefaultSelectedEntityLabels() {
    $referenced_type_id = $this->getReferencedEntityType()->id();
    /** @var \Drupal\Core\Entity\EntityStorageInterface $entity_storage */
    $entity_storage = $this->entityTypeManger->getStorage($referenced_type_id);

    if ($this->value && !isset($this->value['all'])) {
      $entities = $entity_storage->loadMultiple($this->value);
    }
    else {
      $entities = [];
    }

    return EntityAutocomplete::getEntityLabels($entities);
  }

  /**
   * Builds the options for select filter.
   *
   * @return array
   *   The options.
   */
  protected function buildReferenceEntityOptions() {
    $entities = $this->selectionPluginManager->getInstance($this->getHandlerOptions())->getReferenceableEntities();
    $options = [];

    foreach ($entities as $bundle) {
      foreach ($bundle as $id => $entityLabel) {
        $options[$id] = $entityLabel;
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueValidate($form, FormStateInterface $form_state) {
    // We only validate if they've chosen the text field style.
    if ($this->options['type'] != self::AUTO_COMPLETE_TYPE) {
      return;
    }

    $ids = [];
    if ($values = $form_state->getValue(['options', 'value'])) {
      foreach ($values as $value) {
        $ids[] = $value['target_id'];
      }
    }

    $form_state->setValue(['options', 'value'], $ids);
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    if (empty($this->options['exposed'])) {
      return TRUE;
    }
    // We need to know the operator, which is normally set in
    // \Drupal\views\Plugin\views\filter\FilterPluginBase::acceptExposedInput(),
    // before we actually call the parent version of ourselves.
    if (!empty($this->options['expose']['use_operator']) && !empty($this->options['expose']['operator_id']) && isset($input[$this->options['expose']['operator_id']])) {
      $this->operator = $input[$this->options['expose']['operator_id']];
    }

    // If view is an attachment and is inheriting exposed filters, then assume
    // exposed input has already been validated.
    if (!empty($this->view->is_attachment) && $this->view->display_handler->usesExposed()) {
      $this->validatedExposedInput = (array) $this->view->exposed_raw_input[$this->options['expose']['identifier']];
    }

    // If we're checking for EMPTY or NOT, we don't need any input, and we can
    // say that our input conditions are met by just having the right operator.
    if ($this->operator == 'empty' || $this->operator == 'not empty') {
      return TRUE;
    }

    // If it's non-required and there's no value don't bother filtering.
    if (!$this->options['expose']['required'] && empty($this->validatedExposedInput)) {
      return FALSE;
    }

    $rc = parent::acceptExposedInput($input);
    if ($rc) {
      // If we have previously validated input, override.
      if (isset($this->validatedExposedInput)) {
        $this->value = $this->validatedExposedInput;
      }
    }

    return $rc;
  }

  /**
   * {@inheritdoc}
   */
  public function validateExposed(&$form, FormStateInterface $form_state) {
    if (empty($this->options['exposed'])) {
      return;
    }

    $identifier = $this->options['expose']['identifier'];

    // We only validate if they've chosen the select field style.
    if ($this->options['type'] != self::AUTO_COMPLETE_TYPE) {

      if ($form_state->getValue($identifier) != 'All') {
        $this->validatedExposedInput = (array) $form_state->getValue($identifier);
      }
      return;
    }

    if (empty($this->options['expose']['identifier'])) {
      return;
    }

    if ($values = $form_state->getValue($identifier)) {
      foreach ($values as $value) {
        $this->validatedExposedInput[] = $value['target_id'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['type'] = ['default' => self::AUTO_COMPLETE_TYPE];
    $options['sort'] = ['default' => []];

    $handler = $this->collectFieldsReferenceHandlers();
    $options['value_selection_type'] = ['default' => empty($handler['bundles']) ? static::VIEW_SELECTION_TYPE : static::BUNDLE_SELECTION_TYPE];

    $options['target_bundles'] = ['default' => array_combine($handler['bundles'], $handler['bundles'])];
    $options['handler_view'] = ['default' => 0];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function hasExtraOptions() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    if (empty($this->definition['field_name'])) {
      $this->definition['field_name'] = $options['field'];
    }

    parent::init($view, $display, $options);

    if (!empty($this->definition['target_bundles'])) {
      $this->options['target_bundles'] = $this->definition['target_bundles'];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function valueSubmit($form, FormStateInterface $form_state) {
    // Prevent array_filter from messing up our arrays in parent submit.
  }

  /**
   * Helper method to get handler options.
   *
   * @return array
   *   Array of options matching SelectionBase or EntityAutocomplete options,
   *   depending on the type of field setting.
   */
  protected function getHandlerOptions() {
    $prefix = '';
    $settings_prefix = 'handler';

    if ($this->options['type'] == self::AUTO_COMPLETE_TYPE) {
      $prefix = '#';
      $settings_prefix = 'selection';
    }

    $settings_key = $prefix . $settings_prefix . '_settings';

    $options = [
      $prefix . 'target_type' => $this->getReferencedEntityType()->id(),
      $settings_key => ['sort' => $this->options['sort']],
    ];

    $handler = $this->collectFieldsReferenceHandlers();

    // Some entities don't have bundles. Appending an empty array to the
    // target_bundles would in that case prevent any results for the
    // auto-complete field.
    if (
      $this->options['value_selection_type'] == static::BUNDLE_SELECTION_TYPE &&
      $referenced_bundles = $handler['bundles']
    ) {
      // Add only the configured bundles.
      $possible_bundles = array_intersect($this->options['target_bundles'], $referenced_bundles);
      $options[$settings_key]['target_bundles'] = $possible_bundles;
    }
    elseif ($this->options['value_selection_type'] == static::VIEW_SELECTION_TYPE) {
      // For view selection handler we just use the settings from the one.
      $handler_key = $prefix ? $prefix . 'selection_handler' : 'handler';
      $options[$handler_key] = 'views';
      $options[$settings_key]['view'] = $handler['views'][$this->options['handler_view']];
    }

    return $options;
  }

  /**
   * Gets the target referenced entity type by this field.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   Entity type.
   */
  protected function getReferencedEntityType() {
    $field_def = $this->getFieldDefinition();
    $entity_type_id = $field_def->getItemDefinition()->getSetting('target_type');
    return $this->entityTypeManger->getDefinition($entity_type_id);
  }

  /**
   * Collects from entity bundle fields all the reference handlers.
   *
   * These can be either bundles or view selections.
   *
   * @return array
   *   Containing two lists for each type:
   *     - bundles: Contains all bundles that can be referenced.
   *     - views: Contains all view selection handler settings that can be used.
   */
  protected function collectFieldsReferenceHandlers() {
    $referenced_bundles = [];
    $view_selection_handlers = [];

    // For entity types that do not offer bundles, don't try to look them up.
    $entity_type = $this->getReferencedEntityType();
    if ($bundle_type = $entity_type->getBundleEntityType()) {
      $field_definitions = $this->getBundleFieldDefinitions();

      // For each entity bundle check if this field is set and if yes collect
      // the configured target entity bundles.
      foreach ($field_definitions as $field) {
        $handler_settings = $field->getSetting('handler_settings');

        // Get the configured reference-able bundles.
        if (isset($handler_settings['target_bundles']) && is_array($handler_settings['target_bundles'])) {
          $target_bundles = array_keys($handler_settings['target_bundles']);
          $referenced_bundles = array_merge($referenced_bundles, $target_bundles);
        }
      }
    }

    // Check any entity_reference View that might be relevant.
    $view_selection_options = [];
    $displays = Views::getApplicableViews('entity_reference_display');
    foreach ($displays as $data) {
      list($view_id, $display_id) = $data;
      if (($executable = Views::getView($view_id)) && $executable->getBaseEntityType() == $entity_type) {
        $view_settings = [
          'view_name' => $view_id,
          'display_name' => $display_id,
          'arguments' => [],
        ];

        $id = implode(':', [$view_settings['view_name'], $view_settings['display_name']]);
        $view_selection_handlers[$id] = $view_settings;
      }
    }

    return [
      // Remove duplicates and reset the array keying.
      'bundles' => array_values(array_unique($referenced_bundles)),
      'views' => $view_selection_handlers,
    ];
  }

  /**
   * Gets this fields definitions from all bundles of the entity type.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   Field definitions.
   */
  protected function getBundleFieldDefinitions() {
    $field_definitions = [];
    $field_map = $this->entityFieldManager->getFieldMap();

    // Get the bundles on which this field is present.
    $field_def = $this->getFieldDefinition();
    $target_type_id = $field_def->getTargetEntityTypeId();
    $field_name = $field_def->getName();
    $bundles = $field_map[$target_type_id][$field_name]['bundles'];

    foreach ($bundles as $bundle) {
      // Get the field definition on the entity type bundle.
      $bundle_field_definitions = $this->entityFieldManager->getFieldDefinitions($target_type_id, $bundle);
      $field_definitions[] = $bundle_field_definitions[$field_name];
    }

    return $field_definitions;
  }

}
