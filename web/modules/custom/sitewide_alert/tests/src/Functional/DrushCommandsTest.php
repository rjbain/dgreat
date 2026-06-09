<?php

declare(strict_types = 1);

namespace Drupal\Tests\sitewide_alert\Functional;

use Drupal\sitewide_alert\SitewideAlertManager;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\sitewide_alert\Traits\SitewideAlertTestTrait;
use Drush\TestTraits\DrushTestTrait;

/**
 * Test sitewide alert drush commands.
 *
 * @group sitewide_alert
 */
class DrushCommandsTest extends BrowserTestBase {

  use DrushTestTrait;
  use SitewideAlertTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The sitewide alert entity storage handler.
   *
   * @var \Drupal\sitewide_alert\SitewideAlertStorageInterface
   */
  protected $sitewideAlertStorage;

  /**
   * The sitewide alert manager.
   *
   * @var \Drupal\sitewide_alert\SitewideAlertManager
   */
  private SitewideAlertManager $sitewideAlertManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'sitewide_alert',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entity_type_manager = $this->container->get('entity_type.manager');
    $this->sitewideAlertStorage = $entity_type_manager->getStorage('sitewide_alert');
    $this->sitewideAlertManager = $this->container->get('sitewide_alert.sitewide_alert_manager');
  }

  /**
   * Tests sitewide-alert:create minimal.
   */
  public function testCreateMinimalDelete(): void {
    $label = 'automated-test-alert';
    $message = 'A sitewide alert test.';
    $this->drush('sitewide-alert:create', [$label, $message]);
    $this->assertErrorOutputEquals("[success] Created sitewide alert 'automated-test-alert'.");
    $this->assertAlertCount(1);

    $this->drush('sitewide-alert:delete', [$label]);
    $this->assertErrorOutputEquals("[success] Deleted 1 sitewide alerts labelled 'automated-test-alert'.");
    $this->assertAlertCount(0);
  }

  /**
   * Tests sitewide-alert:delete non-existent alert.
   */
  public function testDeleteNone(): void {
    $this->drush('sitewide-alert:delete', ['crazy8342111hash65923label']);
    $this->assertErrorOutputEquals("[notice] Found no sitewide alerts with label 'crazy8342111hash65923label' to delete.");
  }

  /**
   * Tests sitewide-alert:create with an end, but no start.
   *
   * When the start date is omitted it should default to now.
   */
  public function testCreateEndNoStart(): void {
    $label = 'automated-test-alert-no-start';
    $message = 'A sitewide alert test.';
    // Set the end date comfortably in the future.
    $next_year = date('Y') + 1;
    $end_time = $next_year . '-10-15T15:00:00';
    $scheduling_options = ['end' => $end_time];
    $this->drush(
      'sitewide-alert:create',
      [$label, $message],
      $scheduling_options
    );
    $this->assertErrorOutputEquals("[success] Created sitewide alert 'automated-test-alert-no-start'.");
    $this->assertAlertCount(1);

    $this->drush('sitewide-alert:delete', [$label]);
    $this->assertErrorOutputEquals("[success] Deleted 1 sitewide alerts labelled 'automated-test-alert-no-start'.");
    $this->assertAlertCount(0);
  }

  /**
   * Tests sitewide-alert:disable [label].
   */
  public function testDisableWithLabel(): void {
    $this->drush(
      'sitewide-alert:create',
      ['automated-test-alert', 'A sitewide alert test.'],
      []
    );
    $this->assertActiveAlertCount(1);
    $this->drush('sitewide-alert:disable', ['automated-test-alert']);
    $this->assertErrorOutputEquals("[success] Disabled sitewide alert 'automated-test-alert'.");
    $this->assertAlertCount(1);
    $this->assertActiveAlertCount(0);
  }

  /**
   * Tests sitewide-alert:disable --all.
   */
  public function testDisableAll(): void {
    $this->drush(
      'sitewide-alert:create',
      ['automated-test-alert', 'A test sitewide alert.'],
      []
    );
    $this->drush(
      'sitewide-alert:create',
      ['automated-test-alert-2', 'Another test sitewide alert.'],
      []
    );
    $this->assertActiveAlertCount(2);
    $this->drush('sitewide-alert:disable', [], []);
    $this->assertErrorOutputEquals("[success] All active sitewide alerts have been disabled.");
    $this->assertAlertCount(2);
    $this->assertActiveAlertCount(0);
  }

  /**
   * Tests sitewide-alert:disable with invalid input.
   */
  public function testDisableInput(): void {
    $this->drush('sitewide-alert:disable', ['automated-test-alert'], [], NULL, NULL, 1);
    $this->assertErrorOutputEquals("[error] No active sitewide alerts found with the label 'automated-test-alert'.");
    $this->drush('sitewide-alert:disable', [], [], NULL, NULL, 0);
    $this->assertErrorOutputEquals('[notice] There were no sitewide alerts to disable.');
  }

  /**
   * Assertion for number of alerts.
   *
   * @param int $count
   *   The number of alerts that should exist.
   */
  protected function assertAlertCount(int $count): void {
    $this->assertCount($count, $this->sitewideAlertStorage->loadMultiple(NULL));
  }

  /**
   * Assertion for number of active alerts.
   *
   * @param int $count
   *   The number of active alerts that should exist.
   */
  protected function assertActiveAlertCount(int $count): void {
    $this->assertCount($count, $this->sitewideAlertManager->activeSitewideAlerts());
  }

}
