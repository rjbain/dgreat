<?php

namespace Drupal\dgreat_views\Plugin\views\field;

use Drupal\node\Entity\Node;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\weight\Plugin\Field\FieldWidget\WeightSelectorWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;

/**
 * Field handler to present a weight selector element.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("custom_weight_selector")
 */
class CustomWeightSelector extends FieldPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The database connection to which to dump route information.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new LatestRevision.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $db
   *   The database connection to be used.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $db, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->db = $db;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('current_user')
    );
  }

  /**
   *
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['range'] = ['default' => 20];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['range'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Range'),
      '#description' => $this->t('The range of weights available to select. For
        example, a range of 20 will allow you to select a weight between -20
        and 20.'),
      '#default_value' => $this->options['range'],
      '#size' => 5,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return ViewsRenderPipelineMarkup::create('<!--form-item-' . $this->options['id'] . '--' . $this->view->row_index . '-->');
  }

  /**
   *
   */
  public function viewsForm(array &$form, FormStateInterface $form_state) {
    // The view is empty, abort.
    if (empty($this->view->result)) {
      return;
    }

    $form[$this->options['id']] = [
      '#tree' => TRUE,
    ];

    $options = WeightSelectorWidget::rangeOptions($this->options['range']);

    $results = $this->db->select('user_weights', 'u')
      ->fields('u', ['weight', 'entity_id'])
      ->condition('uid', $this->currentUser->id())
      ->condition('view_name', $this->view->id())
      ->execute()
      ->fetchAll();

    $result = $values = [];
    foreach ($results as $r) {
      $result[$r->entity_id] = $r->weight;
    }


    // At this point, the query has already been run, so we can access the results.
    foreach ($this->view->result as $row_index => $row) {
      $entity = $row->_entity;

      if (!empty($result)) {

        $nid = $entity->get('entity_id')->getValue();
        $weight = (isset($nid[0]["target_id"]))
          ? $result[$nid[0]["target_id"]] : 0;

        $form[$this->options['id']][$row_index]['weight'] = [
          '#type' => 'select',
          '#options' => $options,
          '#default_value' => $weight,
          '#attributes' => ['class' => ['weight-selector']],
        ];

        $values[$row_index] = $nid[0]["target_id"];

      }
      else {
        $form[$this->options['id']][$row_index]['weight'] = [
          '#type' => 'select',
          '#options' => $options,
          '#default_value' => $this->getValue($row),
          '#attributes' => ['class' => ['weight-selector']],
        ];
      }



      $form[$this->options['id']][$row_index]['entity'] = [
        '#type' => 'value',
        '#value' => $entity,
      ];
    }

    $form['views_field'] = [
      '#type' => 'value',
      '#value' => $this->field,
    ];

    $form['#cache'] = ['max-age' => 0];

    $form['#action'] = \Drupal::request()->getRequestUri();

    // Set our cookie to be used to grab values.
    if (!empty($values)) {
      setcookie('STYXKEY_ids', json_encode($values), time()+60, '/');
    }
  }

  /**
   *
   */
  public function viewsFormSubmit(array &$form, FormStateInterface $form_state) {
    $field_name = $form_state->getValue('views_field');
    $rows = $form_state->getValue($field_name);

    $uid = $this->currentUser->id();

    // If we don't have any rows
    if (!isset($rows[0])) {
      return;
    }

    foreach ($rows as $row) {
      $entity = $row['entity'];

      $nid = $entity->get('entity_id')->getValue();

      if (isset($nid[0]["target_id"])) {

        // If this is flagged, use the cookie's weight.
        // This is due to the flagging JS borking the form_state.
        if (\Drupal::state()->get('flagged_fav', FALSE)) {
          $ids = json_decode($_COOKIE['STYXKEY_ids'], TRUE);
          $key = array_keys($ids, $nid[0]["target_id"]);
          // Match up our Cookie to the POST array keys (which is the correct order).
          $weight = isset($_POST["field_weight"][$key[0]]["weight"]) ?
            $_POST["field_weight"][$key[0]]["weight"] : $row['weight'];
        }
        else {
          $weight = $row['weight'];
        }

        $check = $this->db->select('user_weights', 'u')
          ->fields('u', ['weight'])
          ->condition('entity_id', $nid[0]["target_id"])
          ->condition('uid', $uid)
          ->condition('view_name', $this->view->id())
          ->execute()
          ->fetchField();


        if ($check === FALSE) {
          // Insert new item in weights table.
          $this->db->insert('user_weights')
            ->fields([
              'entity_id' => $nid[0]["target_id"],
              'uid' => $uid,
              'weight' =>  $weight,
              'view_name' => $this->view->id(),
            ])
            ->execute();
        }
        else {
          // Update the weights table.
          $this->db->update('user_weights')
            ->condition('entity_id', $nid[0]["target_id"])
            ->condition('uid', $uid)
            ->condition('view_name', $this->view->id())
            ->fields([
              'entity_id' => $nid[0]["target_id"],
              'uid' => $uid,
              'weight' => $weight,
              'view_name' => $this->view->id(),
            ])
            ->execute();
        }

      }
    }

    // Reset or Drupal state and cookie.
    \Drupal::state()->set('flagged_fav', FALSE);
    if (isset($_COOKIE['ids'])) {
      setcookie('STYXKEY_ids', NULL, -1, '/');
    }
  }

}
