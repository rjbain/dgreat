<?php

namespace Drupal\Tests\cas\Unit\Controller;

use Drupal\cas\Controller\ServiceController;

/**
 * Class TestServiceController.
 */
class TestServiceController extends ServiceController {

  /**
   * We need to override this function to do nothing for testing purposes.
   *
   * @param string $message
   *   The message text to set.
   * @param string $type
   *   The message type.
   * @param bool $repeat
   *   Whether identical messages should all be shown.
   */
  public function setMessage($message, $type = 'status', $repeat = FALSE) {
  }

  /**
   * Override the t() function provided by StringTranslationTrait.
   *
   * @param string $string
   *   The string to translate.
   * @param array $args
   *   Array of post-translation replacments.
   * @param array $options
   *   Additional options.
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $string;
  }

}
