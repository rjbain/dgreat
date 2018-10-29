<?php

/**
 * @file
 * Contains \Drupal\Tests\cas_attributes\Unit\Subscriber\TestCasAttributeSubscriber.
 */

namespace Drupal\Tests\cas_attributes\Unit\Subscriber;

use Drupal\cas_attributes\Subscriber\CasAttributeSubscriber;

/**
 * Tests CasAttributeSubscriber.
 */
class TestCasAttributeSubscriber extends CasAttributeSubscriber {

  /**
   * Override calls to Unicode::strtolower to just use strtolower.
   */
  protected function strtolower($string) {
    return strtolower($string);
  }

}
