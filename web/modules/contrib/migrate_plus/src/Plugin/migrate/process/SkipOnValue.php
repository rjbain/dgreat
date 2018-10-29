<?php

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * If the source evaluates to a configured value, skip processing or whole row.
 *
 * @MigrateProcessPlugin(
 *   id = "skip_on_value"
 * )
 *
 * Available configuration keys:
 * - value: An single value or array of values against which the source value
 *   should be compared.
 * - not_equals: (optional) If set, skipping occurs when values are not equal.
 * - method: What to do if the input value is empty. Possible values:
 *   - row: Skips the entire row when an empty value is encountered.
 *   - process: Prevents further processing of the input property when the value
 *     is empty.
 *
 * Examples:
 *
 * Example usage with minimal configuration:
 * @code
 *   type:
 *     plugin: skip_on_value
 *     source: content_type
 *     method: row
 *     value: blog
 * @endcode
 *
 * The above example will skip processing the input property if the content_type
 * source field equals "blog".
 *
 * Example usage with full configuration:
 * @code
 *   type:
 *     plugin: skip_on_value
 *     not_equals: true
 *     source: content_type
 *     method: row
 *     value:
 *       - article
 *       - testimonial
 * @endcode
 *
 * The above example will skip processing any row for which the source row's
 * content type field is not "article" or "testimonial".
 */
class SkipOnValue extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function row($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($this->configuration['value']) && !array_key_exists('value', $this->configuration)) {
      throw new MigrateException('Skip on value plugin is missing value configuration.');
    }

    if (is_array($this->configuration['value'])) {
      foreach ($this->configuration['value'] as $skipValue) {
        if ($this->compareValue($value, $skipValue, !isset($this->configuration['not_equals']))) {
          throw new MigrateSkipRowException();
        }
      }
    }
    elseif ($this->compareValue($value, $this->configuration['value'], !isset($this->configuration['not_equals']))) {
      throw new MigrateSkipRowException();
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function process($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($this->configuration['value']) && !array_key_exists('value', $this->configuration)) {
      throw new MigrateException('Skip on value plugin is missing value configuration.');
    }

    if (is_array($this->configuration['value'])) {
      foreach ($this->configuration['value'] as $skipValue) {
        if ($this->compareValue($value, $skipValue, !isset($this->configuration['not_equals']))) {
          throw new MigrateSkipProcessException();
        }
      }
    }
    elseif ($this->compareValue($value, $this->configuration['value'], !isset($this->configuration['not_equals']))) {
      throw new MigrateSkipProcessException();
    }

    return $value;
  }

  /**
   * Compare values to see if they are equal.
   *
   * @param mixed $value
   *   Actual value.
   * @param mixed $skipValue
   *   Value to compare against.
   * @param bool $equal
   *   Compare as equal or not equal.
   *
   * @return bool
   *   True if the compare successfully, FALSE otherwise.
   */
  protected function compareValue($value, $skipValue, $equal = TRUE) {
    if ($equal) {
      return (string) $value == (string) $skipValue;
    }

    return (string) $value != (string) $skipValue;

  }

}
