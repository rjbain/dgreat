<?php

namespace Drupal\dgreat_migration\Plugin\migrate\source;

use Drupal\user\Plugin\migrate\source\d7\User;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;

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
    // Filter out users who already exist in the new database. This avoids collusions.
    if ($row->getSourceProperty('name') && $this->userExists($row->getSourceProperty('name'))) {
      return FALSE;
    }

    // For some reason I could not get the joins to work on this.
    // I don't like the fact I need to run two sql queries.
    // However it works for now @todo make this happier.
    $query = $this->select('og_membership', 'og')
      ->fields('og', ['gid'])
      ->condition('etid', $uid)
      ->condition('entity_type', 'user')
      ->execute()
      ->fetchAll();

    $query2 = $this->select('og_users_roles', 'our')
      ->fields('our', ['gid'])
      ->condition('uid', $uid)
      ->execute()
      ->fetchAll();

    // Set our array of values.
    $gids = [];
    foreach ($query as $gid) {
      $gids[] = $gid['gid'];
    }

    foreach ($query2 as $gid) {
      $gids[] = $gid['gid'];
    }

    // Set the property to use for the user yaml ER field.
    $row->setSourceProperty('gids', $gids);

    // Set the property to use in the custom_user destination.
    $row->setDestinationProperty('gids', $gids);

    return parent::prepareRow($row);
  }

  /**
   * Determine if this row's name matches an existing user.
   *
   * @param string $name
   *
   * @return bool
   */
  private function userExists($name) {
    $result = \Drupal::database()->select('users_field_data', 'ufd')
      ->fields('ufd', ['name'])
      ->condition('name', $name, '=')
      ->execute()
      ->fetch()
      ->expression >= 1;

  }

}
