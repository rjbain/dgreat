<?php

namespace Drupal\dgreat_migration\Plugin\migrate\source;

use Drupal\node\Plugin\migrate\source\d7\Node;
use Drupal\migrate\Row;

/**
 * Extends the D7 Node source plugin so we can grab OG info.
 *
 * @MigrateSource(
 *   id = "d7_node_service",
 *   source_module = "node"
 * )
 */
class ServiceNode extends Node {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Grab our nid and grab the Group ID from the D7 OG table.
    $nid = $row->getSourceProperty('nid');
    $query = $this->select('og_membership', 'og')
      ->fields('og', ['gid'])
      ->condition('etid', $nid)
      ->condition('entity_type', 'node')
      ->execute()
      ->fetchAll();

    // Set our array of values.
    $gids = [];
    foreach ($query as $gid) {
      $gids[] = $gid['gid'];
    }

    // Grab the field on the node.
    $field = $row->getSourceProperty('og_group_ref');
    if ($field !== NULL) {
      $gids = array_merge($gids, $field);
    }

    // Set the property to use as source in the yaml.
    $row->setSourceProperty('gids', $gids);

    return parent::prepareRow($row);
  }

}