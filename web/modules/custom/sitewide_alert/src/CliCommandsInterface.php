<?php

declare(strict_types = 1);

namespace Drupal\sitewide_alert;

/**
 * Interface for a service with shared code for CLI tools.
 */
interface CliCommandsInterface {

  /**
   * Creates a new sitewide alert.
   *
   * @param string $label
   *   The label of the sitewide alert.
   * @param string $message
   *   The message to put in the sitewide alert.
   * @param array $options
   *   Array of optional human-readable values to set on the sitewide alert,
   *   passed on the command line. The following values can be set:
   *   - start: The start date, in ISO 8601 format.
   *   - end: The end date, in ISO 8601 format.
   *   - status: Optional boolean value indicating if the created alert will be
   *     activated. If omitted or set to TRUE, the alert will be active. If set
   *     to FALSE it will be inactive.
   *   - style: The style of alert, from the list provided in config. If
   *     omitted or any other value this will default to 'primary'.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown when the sitewide alert entity definition is invalid.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown when the sitewide alert entity type is not defined.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when an error occurs during the saving of the sitewide alert.
   * @throws \InvalidArgumentException
   *   Thrown in case one of the passed in arguments or options is invalid.
   */
  public function create(string $label, string $message, array $options): void;

  /**
   * Validates the input for the 'create' command.
   *
   * @param string $label
   *   The label argument.
   * @param string $message
   *   The message argument.
   * @param array $options
   *   An array of options, passed by reference. The 'start' and 'end' options
   *   will be converted from a human-readable string to the standard datetime
   *   storage format.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a passed in argument or option is invalid.
   *
   * @see \Drupal\sitewide_alert\CliCommands::create()
   */
  public function validateCreateInput(string $label, string $message, array &$options): void;

  /**
   * Deletes sitewide alert(s) with a matching label.
   *
   * @param string $label
   *   The label to match for deletion.
   *
   * @return int
   *   The number of deleted sitewide alerts.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the label is missing or is not a string.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when an error occurs during the deletion of a sitewide alert.
   */
  public function delete(string $label): int;

  /**
   * Disables sitewide alert(s).
   *
   * @param string|null $label
   *   The label of a sitewide alert to disable. If omitted, all sitewide alerts
   *   will be disabled.
   *
   * @return int
   *   The number of sitewide alerts that were disabled.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when an error occurs while disabling a sitewide alert.
   */
  public function disable(?string $label = NULL): int;

  /**
   * Enables a sitewide alert.
   *
   * @param string $label
   *   The label of the sitewide alert to enable.
   *
   * @return int
   *   The number of sitewide alerts that were enabled.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when an error occurs while enabling a sitewide alert.
   */
  public function enable(string $label): int;

}
