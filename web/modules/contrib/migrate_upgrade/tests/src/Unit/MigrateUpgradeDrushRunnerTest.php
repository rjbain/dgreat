<?php

namespace Drupal\Tests\migrate_upgrade\Unit;

use Drupal\migrate_upgrade\MigrateUpgradeDrushRunner;
use Drupal\Tests\migrate\Unit\MigrateTestCase;

/**
 * Tests for the  MigrateUpgradeDrushRunner class.
 *
 * @group migrate_upgrade
 * @coversDefaultClass \Drupal\migrate_upgrade\MigrateUpgradeDrushRunner
 */
class MigrateUpgradeDrushRunnerTest extends MigrateTestCase {

  /**
   * Test the id substitution functions.
   *
   * @param array $source
   *   The source data.
   * @param array $expected
   *   The expected results.
   *
   * @covers ::substituteIds
   * @covers ::substituteMigrationIds
   *
   * @dataProvider getData
   */
  public function testIdSubstitution(array $source, array $expected) {
    $runner = new TestMigrateUpgradeDrushRunner();
    $results = $runner->substituteIds($source);
    $this->assertArrayEquals($expected, $results);
  }

  /**
   * Returns test data for the test.
   *
   * @return array
   *   The test data.
   */
  public function getData() {
    return [
      'Single Migration Lookup' => [
        'source_data' => [
          'id' => 'my_migration',
          'process' => [
            'element' => [
              'plugin' => 'migration_lookup',
              'migration' => 'my_previous_migration',
              'source' => 'value',
            ],
          ],
          'migration_dependencies' => [
            'required' => [
              'my_previous_migration',
              'required_dependency',
            ],
            'optional' => ['optional_dependency'],
          ],
        ],
        'expected_result' => [
          'id' => 'upgrade_my_migration',
          'process' => [
            'element' => [
              'plugin' => 'migration_lookup',
              'migration' => 'upgrade_my_previous_migration',
              'source' => 'value',
            ],
          ],
          'migration_dependencies' => [
            'required' => [
              'upgrade_my_previous_migration',
              'upgrade_required_dependency',
            ],
            'optional' => ['upgrade_optional_dependency'],
          ],
        ],
      ],
      'Dual Migration Lookup' => [
        'source_data' => [
          'id' => 'my_migration',
          'process' => [
            'element' => [
              'plugin' => 'migration_lookup',
              'migration' => [
                'my_previous_migration_1',
                'my_previous_migration_2',
              ],
              'source_ids' => [
                'my_previous_migration_1' => ['source_1'],
                'my_previous_migration_2' => ['source_2'],
              ],
              'source' => 'value',
            ],
          ],
          'migration_dependencies' => [
            'required' => [
              'my_previous_migration_1',
              'required_dependency',
            ],
            'optional' => [
              'my_previous_migration_2',
              'optional_dependency',
            ],
          ],
        ],
        'expected_result' => [
          'id' => 'upgrade_my_migration',
          'process' => [
            'element' => [
              'plugin' => 'migration_lookup',
              'migration' => [
                'upgrade_my_previous_migration_1',
                'upgrade_my_previous_migration_2',
              ],
              'source_ids' => [
                'upgrade_my_previous_migration_1' => ['source_1'],
                'upgrade_my_previous_migration_2' => ['source_2'],
              ],
              'source' => 'value',
            ],
          ],
          'migration_dependencies' => [
            'required' => [
              'upgrade_my_previous_migration_1',
              'upgrade_required_dependency',
            ],
            'optional' => [
              'upgrade_my_previous_migration_2',
              'upgrade_optional_dependency',
            ],
          ],
        ],
      ],
    ];
  }

}

/**
 * Test class to expose protected methods.
 */
class TestMigrateUpgradeDrushRunner extends MigrateUpgradeDrushRunner {

  /**
   * {@inheritdoc}
   */
  public function substituteIds($entity_array) {
    return parent::substituteIds($entity_array);
  }

}

namespace Drupal\migrate_upgrade;

if (!function_exists('drush_get_option')) {

  /**
   * Override for called function.
   *
   * @param mixed $option
   *   An option.
   * @param mixed $default
   *   The default.
   *
   * @return mixed
   *   The default, for this override.
   */
  function drush_get_option($option, $default) {
    return $default;
  }

}
