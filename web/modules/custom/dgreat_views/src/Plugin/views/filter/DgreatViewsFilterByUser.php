<?php

namespace Drupal\dgreat_views\Plugin\views\filter;

use Drupal\Core\Database\Query\Condition;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Component\Utility\Html;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;


/**
 * Filters OneHub Documents via what groups the current user belongs to.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsFilter("dgreat_views_filter_by_user")
 */
class DgreatViewsFilterByUser extends FilterPluginBase implements ContainerFactoryPluginInterface {

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
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    // Remove the expose checkbox.
    unset($form["expose_button"]);
  }


  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $uid = $this->currentUser->id();
    $user = User::load($uid);
    $groups = $user->get('field_user_group')->getValue();
    $gids = [];
    foreach ($groups as $gid) {
      $gids[] = $gid['target_id'];
    }

    if (empty($gids)) {
      $this->query->addWhere(0, 0);
      return;
    }

    // Remove duplicates.
    $gids = array_unique($gids);

    $query = $this->db
      ->select('group__field_default_quick_links', 'g')
      ->fields('g', ['field_default_quick_links_target_id'])
      ->condition('entity_id', $gids, 'IN');

    // Loop through and grab our content ids.
    foreach ($query->execute()->fetchAll() as $result) {
      $nids[] = $result->field_default_quick_links_target_id;
    }

    // Remove duplicates.
    $nids = array_unique($nids);

    $query = $this->db
      ->select('node_field_data', 'n')
      ->fields('n', ['nid'])
      ->condition('type', 'favorite_link')
      ->condition('uid', $uid);

    // Loop through and grab our content ids.
    foreach ($query->execute()->fetchAll() as $result) {
      $nids[] = $result->nid;
    }

    if (!empty($nids)) {
      // Setup the conditions.
      $conditions = new Condition('AND');
      $conditions->condition('nid', $nids, 'IN');

      // Hook up the query.
      $this->query->addWhere(0, $conditions);
    }
    else {
      $this->query->addWhere(0, 0);
    }
  }
}