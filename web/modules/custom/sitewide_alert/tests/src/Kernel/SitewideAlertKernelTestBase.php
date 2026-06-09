<?php

declare(strict_types=1);

namespace Drupal\Tests\sitewide_alert\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\sitewide_alert\Traits\SitewideAlertTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Defines a base kernel test for sitewide alert tests.
 */
abstract class SitewideAlertKernelTestBase extends KernelTestBase {

  use UserCreationTrait;
  use SitewideAlertTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'sitewide_alert',
    'system',
    'user',
    'datetime_range',
    'datetime',
    'options',
    'filter',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'sitewide_alert', 'filter']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('sitewide_alert');
    $this->setUpCurrentUser();
  }

}
