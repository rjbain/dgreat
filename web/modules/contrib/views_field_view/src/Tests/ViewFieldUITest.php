<?php

/**
 * @file
 * Contains \Drupal\views_field_view\Tests\ViewFieldUITest.
 */

namespace Drupal\views_field_view\Tests;

use Drupal\views\Tests\ViewTestData;
use Drupal\views_ui\Tests\UITestBase;

/**
 * Tests the UI of views_field_view
 *
 * @see \Drupal\views_field_view\Plugin\views\field\View
 * @group views_ui
 */
class ViewFieldUITest extends UITestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['views_field_view', 'views_field_view_test_config', 'user'];

  /**
   * Views to enable.
   *
   * @var array
   */
  public static $testViews = ['views_field_view_test_parent_normal', 'views_field_view_test_child_normal'];

  protected function setUp() {
    parent::setUp();

    ViewTestData::createTestViews(get_class($this), ['views_field_view_test_config']);
  }

  public function testViewsFieldUI() {
    $this->drupalGet('admin/structure/views/view/views_field_view_test_parent_normal/edit/default');
    $this->clickLink('Global: View (View)');

    $result = $this->cssSelect('details#edit-options-available-tokens div.item-list li');
    $this->assertEqual(8, count($result));

    $this->assertEqual('{{ raw_fields.id }} == Views test: ID (raw)', (string) $result[0]);
    $this->assertEqual('{{ fields.id }} == Views test: ID (rendered)', (string) $result[1]);
    $this->assertEqual('{{ raw_fields.name }} == Views test: Name (raw)', (string) $result[2]);
    $this->assertEqual('{{ fields.name }} == Views test: Name (rendered)', (string) $result[3]);
    $this->assertEqual('{{ raw_fields.view }} == Global: View (raw)', (string) $result[4]);
    $this->assertEqual('{{ fields.view }} == Global: View (rendered)', (string) $result[5]);
    $this->assertEqual('{{ arguments.null }} == Global: Null title', (string) $result[6]);
    $this->assertEqual('{{ raw_arguments.null }} == Global: Null input', (string) $result[7]);
  }

}
