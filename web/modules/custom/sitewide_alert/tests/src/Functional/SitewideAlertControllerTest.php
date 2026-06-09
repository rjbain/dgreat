<?php

declare(strict_types=1);

namespace Drupal\Tests\sitewide_alert\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\sitewide_alert\Traits\SitewideAlertTestTrait;

/**
 * Defines a class for testing the revision controller.
 *
 * @group sitewide_alert
 */
final class SitewideAlertControllerTest extends BrowserTestBase {

  use SitewideAlertTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'sitewide_alert',
  ];

  /**
   * Tests revision overview.
   */
  public function testAlertRevisions(): void {
    $random = $this->getRandomGenerator();
    $message1 = $random->sentences(10);
    $message2 = $random->sentences(10);
    $alert = $this->createSiteWideAlert();
    $alert->setRevisionLogMessage($message1);
    $alert->message->value = $random->sentences(10);
    $alert->setNewRevision(TRUE);
    $alert->save();

    $alert->setRevisionLogMessage($message2);
    $alert->message->value = $random->sentences(10);
    $alert->setNewRevision(TRUE);
    $alert->save();

    $this->drupalLogin($this->createUser([
      'administer sitewide alert entities',
      'access content',
      'view all sitewide alert revisions',
    ]));
    $this->drupalGet(Url::fromRoute('entity.sitewide_alert.version_history', [
      'sitewide_alert' => $alert->id(),
    ]));
    $assert = $this->assertSession();
    $assert->pageTextContains($message1);
    $assert->pageTextContains($message2);
  }

}
