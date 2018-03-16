<?php

namespace Drupal\dgreat_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;

/**
 * Convert a taxonomy tid into a group gid.
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

    var_dump($value);
    // If the tid is not in this array, it doesn't get migrated.
    if (!array_key_exists($value, $this->match())) {
      throw new MigrateSkipRowException('', TRUE);
    }

    // Return the matched array.
    return $this->match()[$value];
  }

  /**
   * Matches an array of tids and gids.
   * This was matched up in the discovery docs.
   *
   * @return array
   *   A tid to gid array to chose from.
   */
  protected function match() {
    // Format is tid => gid.
    return [
      '7' => '3',
      '116' => '4',
      '111' => '5',
      '8' => '6',
    ];
  }
}