<?php

namespace Drupal\dgreat_views\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;

/**
 * Filter by User + Default.
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
      $this->query->addWhereExpression(0, '1 = 0');
      return;
    }

    // Restrict results to content that belongs to the user's groups.
    // group_relationship_field_data is the base table of this view; its gid
    // column links each piece of group content to its parent group. Filtering
    // on that column is the correct way to scope results to the current user's
    // groups — querying by node uid (previous approach) only returned nodes
    // authored by the user, which is always empty for most end users.
    $gids = array_unique(array_map('intval', $gids));
    $this->query->addWhere(0, 'group_relationship_field_data.gid', $gids, 'IN');
  }

}
