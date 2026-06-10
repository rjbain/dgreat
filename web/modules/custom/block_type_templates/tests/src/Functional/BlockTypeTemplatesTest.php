<?php

namespace Drupal\Tests\block_type_templates\Functional;

use Drupal\block\Entity\Block;
use Drupal\Tests\block_content\Functional\BlockContentTestBase;

/**
 * Class BlockTypeTemplatesTest. The base class for block templates.
 */
class BlockTypeTemplatesTest extends BlockContentTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block_content', 'block_type_templates'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test block templates.
   */
  public function testBlockSuggestions() {
    // Create a new block type.
    $this->createBlockContentType('suggestion');

    // Create block content of the bundle.
    $block_content = $this->createBlockContent(FALSE, 'suggestion');

    // Place the block on the page.
    $block = Block::create([
      'plugin' => "block_content:{$block_content->uuid()}",
      'region' => 'footer',
      'id' => 'suggestion',
    ]);

    /** @var \Drupal\Core\Block\BlockPluginInterface $plugin */
    $plugin = $block->getPlugin();

    // Prepare an array of required variables.
    $variables['elements']['#configuration'] = $plugin->getConfiguration();
    $variables['elements']['#plugin_id'] = $plugin->getPluginId();
    $variables['elements']['#id'] = $block->id();
    $variables['elements']['#base_plugin_id'] = $plugin->getBaseId();
    $variables['elements']['#derivative_plugin_id'] = $plugin->getDerivativeId();

    $variables['elements']['content'] = [];
    $variables['elements']['content']['#block_content'] = $block_content;
    $variables['elements']['content']['#view_mode'] = 'full';

    // Get all possible theme suggestions.
    $suggestions = \Drupal::moduleHandler()->invokeAll('theme_suggestions_block', [$variables]);
    \Drupal::moduleHandler()->alter(['theme_suggestions_block'], $suggestions, $variables);

    // Check if the list contains correct values.
    $this->assertContains('block__block_content_suggestion', $suggestions);
    $this->assertContains('block__block_content_suggestion__full', $suggestions);
  }

}
