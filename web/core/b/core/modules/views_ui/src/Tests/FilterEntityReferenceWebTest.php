<?php

namespace Drupal\views_ui\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\node\NodeInterface;
use Drupal\views\Entity\View;
use Drupal\views_ui\Tests\UITestBase;

/**
 * Test the entity reference filter UI.
 *
 * @group views_ui
 * @see \Drupal\views\Plugin\views\filter\EntityReference
 */
class FilterEntityReferenceWebTest extends UITestBase {

  /**
   * Entity type and referencable type.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $entityType, $referencableType;

  /**
   * Referencable content.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes, $referencableNodes;

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_filter_entity_reference'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create an entity type, and a referencable type. Since these are coded
    // into the test view, they are not randomly named.
    $this->entityType = $this->drupalCreateContentType(['type' => 'page']);
    $this->referencableType = $this->drupalCreateContentType(['type' => 'article']);

    $field_storage = FieldStorageConfig::create(array(
      'entity_type' => 'node',
      'field_name' => 'field_test',
      'type' => 'entity_reference',
      'settings' => array(
        'target_type' => 'node',
        'additional_behaviors' => array(
          'views_select_list' => TRUE,
        ),
      ),
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ));
    $field_storage->save();

    $field = FieldConfig::create(array(
      'entity_type' => 'node',
      'field_name' => 'field_test',
      'bundle' => $this->entityType->id(),
      'settings' => array(
        'handler' => 'default',
        'handler_settings' => array(
          // Note, this has no impact on Views at this time.
          'target_bundles' => array(
            $this->referencableType->id() => $this->referencableType->label(),
          ),
        ),
      ),
    ));
    $field->save();

    // Create 10 referencable nodes.
    for ($i = 0; $i < 10; $i++) {
      $node = $this->drupalCreateNode(['type' => $this->referencableType->id()]);
      $this->referencableNodes[$node->id()] = $node;
    }
  }

  /**
   * Tests the filter UI.
   */
  public function testFilterUi() {
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_entity_reference/default/filter/field_test_target_id');

    $options = $this->getUiOptions();
    // Should be sorted by title ASC.
    uasort($this->referencableNodes, function (NodeInterface $a, NodeInterface $b) {
      return strnatcasecmp($a->getTitle(), $b->getTitle());
    });
    $found_all = TRUE;
    $i = 0;
    foreach ($this->referencableNodes as $nid => $node) {
      $option = $options[$i];
      $label = $option['label'];
      $found_all = $found_all && $label == $node->label() && $nid == $option['nid'];
      $this->assertEqual($label, $node->label(), new FormattableMarkup('Expected referencable label found for option :option', [':option' => $i]));
      $i++;
    }
    $this->assertTrue($found_all, 'All referencable nodes were available as a select list properly ordered.');

    // Change the sort field and direction.
    $view = View::load('test_filter_entity_reference');
    $display = & $view->getDisplay('default');
    $display['display_options']['filters']['field_test_target_id']['sort']['field'] = 'nid';
    $display['display_options']['filters']['field_test_target_id']['sort']['direction'] = 'DESC';
    $view->save();

    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_entity_reference/default/filter/field_test_target_id');
    // Items should now be in reverse nid order.
    krsort($this->referencableNodes);
    $options = $this->getUiOptions();
    $found_all = TRUE;
    $i = 0;
    foreach ($this->referencableNodes as $nid => $node) {
      $option = $options[$i];
      $label = $option['label'];
      $found_all = $found_all && $label == $node->label() && $nid == $option['nid'];
      $this->assertEqual($label, $node->label(), new FormattableMarkup('Expected referencable label found for option :option', [':option' => $i]));
      $i++;
    }
    $this->assertTrue($found_all, 'All referencable nodes were available as a select list properly ordered.');
  }

  /**
   * Helper method to parse options from the UI.
   *
   * @return array
   *   Array of keyed arrays containing `nid` and `label` of each option.
   */
  protected function getUiOptions() {
    /** @var SimpleXMLElement[] $result */
    $result = $this->xpath('//select[@id="edit-options-value"]/option');
    $first = array_shift($result);
    $this->assertEqual($first->attributes()->value, 'all', 'First option is properly set to "all".');

    $options = [];
    foreach ($result as $option) {
      $nid = (int) $option->attributes()['value'];
      $options[] = ['nid' => $nid, 'label' => (string) $option];
    }

    return $options;
  }

}
