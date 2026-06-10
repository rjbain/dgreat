<?php

namespace Drupal\Tests\rate\Functional;

use Drupal\node\Entity\NodeType;
use Drupal\rate\Entity\RateWidget;
use Drupal\Tests\rate\Traits\RateWidgetCreateTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Holds set of tools for the rate widget testing.
 */
abstract class RateWidgetTestBase extends BrowserTestBase {

  use RateWidgetCreateTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'comment',
    'rate',
    'views',
    'datetime',
  ];

  /**
   * The node access controller.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessController;

  /**
   * An array of nodes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    $this->nodes['article'][1] = $this->drupalCreateNode([
      'type' => 'article',
      'nid' => 1,
    ])->save();

    $this->nodes['article'][2] = $this->drupalCreateNode([
      'type' => 'article',
      'nid' => 2,
    ])->save();
  }

  /**
   * Load a rate widget easily.
   *
   * @param string $id
   *   The id of the rate widget.
   *
   * @return \Drupal\rate\Entity\RateWidget
   *   The rate widget Object.
   */
  protected function loadRateWidget($id) {
    return RateWidget::load($id);
  }

}
