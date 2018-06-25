<?php

namespace Drupal\dgreat_views\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Just sorts stuff alpha if there are no user weights.
 *
 * @ViewsFilter("user_weight_filter_sort")
 */
class UserWeightsSortFilter extends FilterPluginBase implements ContainerFactoryPluginInterface {

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
    $name = $this->view->id();

    $results = $this->db->select('user_weights', 'u')
      ->fields('u', ['entity_id'])
      ->condition('uid', $uid)
      ->condition('view_name', $name)
      ->orderBy('weight', 'ASC')
      ->execute();

    $results->allowRowCount = TRUE;
    $count = $results->rowCount();

    // Sort by our user weights.
    if (!$count) {
      // Just sort by title if there are no user weights yet.
      $this->query->addOrderBy($this->tableAlias, 'title', 'ASC');
    }
  }

}
