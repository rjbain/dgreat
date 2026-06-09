<?php

declare(strict_types=1);

namespace Drupal\sitewide_alert;

use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides information on the alert styles.
 */
class AlertStyleProvider {

  /**
   * Gets the available alert styles.
   *
   * @return array
   *   Array of all alert style options.
   */
  public static function alertStyles(): array {
    $styles = [];
    $config = \Drupal::config('sitewide_alert.settings');
    if ($alertStylesString = $config->get('alert_styles')) {
      foreach (explode("\n", strip_tags($alertStylesString)) as $value) {
        if (strpos($value, '|') !== FALSE) {
          [$key, $title] = array_pad(
            array_map('trim', explode('|', $value, 2)),
            2,
            NULL
          );
          $styles[$key] = $title;
        }
        else {
          $styles[Html::cleanCssIdentifier($value)] = $value;
        }
      }
    }

    return $styles;
  }

  /**
   * Given a class get the alert style name.
   *
   * @param string $class
   *   Class name to look up.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   Renderable label for class.
   */
  public static function alertStyleName(string $class): TranslatableMarkup|string {
    $alertStyle = self::alertStyles();
    if (isset($alertStyle[$class])) {
      return $alertStyle[$class];
    }

    return new TranslatableMarkup('N/A');
  }

}
