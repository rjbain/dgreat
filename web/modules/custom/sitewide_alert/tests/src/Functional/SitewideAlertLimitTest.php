<?php

declare(strict_types=1);

namespace Drupal\Tests\sitewide_alert\Functional;

use Drupal\Core\Url;
use Drupal\sitewide_alert\Entity\SitewideAlert;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\sitewide_alert\Traits\SitewideAlertTestTrait;

/**
 * Defines a class for testing sitewide alerts.
 *
 * @group sitewide_alert
 */
final class SitewideAlertLimitTest extends BrowserTestBase {

  use SitewideAlertTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'sitewide_alert',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the limit pages functionality.
   */
  public function testAlertLimitPageVisibilityForm(): void {
    $random = $this->getRandomGenerator();
    $sentences = $random->sentences(10);
    $user = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($user);
    // Create the initial alert.
    $alert = $this->createSiteWideAlert([
      'message' => [
        'value' => $sentences,
      ],
      'limit_to_pages' => '/user/*',
    ]);
    $this->drupalLogin($user);
    // Untick the limit field.
    $url = Url::fromRoute('entity.sitewide_alert.edit_form', ['sitewide_alert' => $alert->id()])->toString();
    $this->drupalGet($url);
    $this->submitForm(['limit_alert_by_pages' => FALSE], 'Save');
    $alert = SitewideAlert::load($alert->id());
    $this->assertEmpty($alert->get('limit_to_pages')->getValue());
  }

}
