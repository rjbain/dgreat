<?php
/**
 * Created by PhpStorm.
 * User: john
 * Date: 3/21/18
 * Time: 11:55 AM
 */

namespace Drupal\dgreat_migration\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\Component\Utility\Html;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;


/**
 * Migrate OG Groups to Group.
 *
 * @MigrateSource(
 *   id = "og_groups_to_group"
 * )
 */
class OgGroupsToGroup extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('node', 'n')
      ->distinct()
      ->fields('n', ['nid', 'title'])
      ->condition('type', ['oa_group', 'oa_space'], 'IN')
      ->condition('status', 1);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nid' => $this->t('OG Node ID'),
      'title' => $this->t('OG Node Title'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {


    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'n',
      ],
    ];
  }

}