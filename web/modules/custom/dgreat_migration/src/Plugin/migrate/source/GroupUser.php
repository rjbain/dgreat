<?php

namespace Drupal\dgreat_migration\Plugin\migrate\source;

use Drupal\user\Plugin\migrate\source\d7\User;
use Drupal\migrate\Row;

/**
 * Extends the D7 Node source plugin so we can grab OG info.
 *
 * @MigrateSource(
 *   id = "d7_group_user",
 *   source_module = "user"
 * )
 */
class GroupUser extends User {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Grab our nid and grab the Group ID from the D7 OG table.
    $uid = $row->getSourceProperty('uid');
    $query = $this->select('og_membership', 'og')
      ->fields('og', ['gid'])
      ->condition('etid', $uid)
      ->condition('entity_type', 'user')
      ->execute()
      ->fetchAll();

    // Set our array of values.
    $gids = [];
    foreach ($query as $gid) {
      $gids[] = $gid['gid'];
    }

    // Set the property to use as source in the yaml.
    $row->setDestinationProperty('gids', $gids);
  }

}