<?php

declare(strict_types=1);

namespace Drupal\Tests\sitewide_alert\Traits;

use Drupal\Component\Utility\Random;
use Drupal\sitewide_alert\Entity\SitewideAlert;
use Drupal\sitewide_alert\Entity\SitewideAlertInterface;

/**
 * Defines a trait for site wide alert tests.
 */
trait SitewideAlertTestTrait {

  /**
   * Creates a site wide alert.
   *
   * @param array $values
   *   Field values.
   *
   * @return \Drupal\sitewide_alert\Entity\SitewideAlertInterface
   *   Created alert.
   */
  protected function createSiteWideAlert(array $values = []): SitewideAlertInterface {
    $random = new Random();
    $alert = SitewideAlert::create($values + [
      'status' => 1,
      'user_id' => 1,
      'name' => $random->name(),
      'style' => 'primary',
      'dismissable' => TRUE,
      'message' => [
        'value' => $random->sentences(10),
        'format' => 'plain_text',
      ],
    ]);
    // Support Drupal Testing traits users.
    if (method_exists($this, 'markEntityForCleanup')) {
      $this->markEntityForCleanup($alert);
    }
    $alert->save();
    assert($alert instanceof SitewideAlertInterface);
    return $alert;
  }

}
