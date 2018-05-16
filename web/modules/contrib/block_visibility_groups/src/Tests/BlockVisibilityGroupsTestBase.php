<?php

/*
 * @file
 * Contains \Drupal\block_visibility_groups\Tests\BlockVisibilityGroupsTestBase
 */

namespace Drupal\block_visibility_groups\Tests;

use Drupal\simpletest\WebTestBase;

/**
 *
 */
abstract class BlockVisibilityGroupsTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * Var array.
   */
  public static $modules = ['block', 'block_visibility_groups'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create and login with user who can administer blocks.
    $this->drupalLogin($this->drupalCreateUser([
      'administer blocks',
    ]));
  }

  /**
   * @param $plugin_id
   * @param $group_id
   * @param array $settings
   *
   * @return \Drupal\block\Entity\Block
   */
  protected function placeBlockInGroup($plugin_id, $group_id, $settings = []) {
    $settings['label_display'] = 'visible';
    $settings['label'] = $this->randomMachineName();
    $settings['visibility']['condition_group']['block_visibility_group'] = $group_id;
    $block = $this->drupalPlaceBlock($plugin_id, $settings);
    return $block;
  }

  /**
   * Places a block in a block visibility group through the UI.
   *
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $group_id
   *   The group id.
   * @param string $title
   *   The title for the block.
   *
   * @return string
   *   The block ID.
   */
  protected function placeBlockInGroupUI($plugin_id, $group_id, $title) {

    // Enable a standard block.
    $default_theme = $this->config('system.theme')->get('default');
    $edit = [
      'id' => strtolower($this->randomMachineName(8)),
      'region' => 'sidebar_first',
      'settings[label]' => $title,
      'settings[label_display]' => 1,
    ];
    $block_id = $edit['id'];
    if ($group_id) {
      $edit['visibility[condition_group][block_visibility_group]'] = $group_id;
    }

    $this->drupalGet('admin/structure/block/add/' . $plugin_id . '/' . $default_theme);

    $this->drupalPostForm(NULL, $edit, t('Save block'));
    $this->assertText('The block configuration has been saved.', 'Block was saved');

    // Just for Debug message.
    $this->drupalGet('admin/structure/block/manage/' . $block_id);
    $this->drupalGet('admin/structure/block/block-visibility-group/' . $group_id);

    return $block_id;
  }

  /**
   * Update a block which is already placed in a block visibility group.
   *
   * @param string $block_id
   *   The block ID.
   * @param string $group_id
   *   The group id.
   * @param array $settings
   *   The array of settings.
   */
  protected function updateBlockInGroupUI($block_id, $group_id, array $settings = []) {
    $this->drupalPostForm('admin/structure/block/manage/' . $block_id, $settings, 'Save block', [
      'query' => [
        'block_visibility_group' => $group_id,
      ]
    ]);
  }

}
