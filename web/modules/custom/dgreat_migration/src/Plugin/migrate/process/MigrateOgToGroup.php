<?php

namespace Drupal\dgreat_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;

/**
 * Convert a OG gid into a Group gid.
 *
 * @MigrateProcessPlugin(
 *   id = "og_to_group",
 * )
 */
class MigrateOgToGroup extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If the tid is not in this array, it doesn't get migrated.
    if (!array_key_exists($value, $this->match())) {
      throw new MigrateSkipRowException('', TRUE);
    }

    // Return the matched array.
    return $this->match()[$value];
  }

  /**
   * Matches an array of og gids and group gids.
   *
   * @return array
   *   A og gid to group gid array to chose from.
   */
  protected function match() {
    // Format is og gid => group gid.
    return [
      '4522' => '1',
      '137671' => '2',
      '4523' => '3',
      '3908' => '4',
      '8949' => '5',
      '136583' => '6',
      '136584' => '7',
      '136586' => '8',
      '136587' => '9',
      '136585' => '10',
      '136582' => '11',
      '136581' => '12',
      '136580' => '13',
      '135672' => '14',
      '135540' => '15',
      '52539' => '16',
      '4524' => '17',
      '4525' => '18',
      '14790' => '19',
      '14789' => '20',
      '8947' => '21',
      '24976' => '22',
      '3736' => '23',
      '4205' => '14',
    ];
  }
}