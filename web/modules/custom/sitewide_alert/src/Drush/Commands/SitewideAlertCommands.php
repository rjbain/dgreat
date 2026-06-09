<?php

declare(strict_types = 1);

namespace Drupal\sitewide_alert\Drush\Commands;

use Drupal\sitewide_alert\CliCommandsInterface;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush command file for the sitewide alert module.
 */
class SitewideAlertCommands extends DrushCommands {

  /**
   * The CLI service for doing CLI operations on sitewide_alert.
   *
   * @var \Drupal\sitewide_alert\CliCommandsInterface
   */
  protected CliCommandsInterface $sitewideAlertCliCommands;

  /**
   * Construct a new Drush command object.
   *
   * @param \Drupal\sitewide_alert\CliCommandsInterface $sitewideAlertCliCommands
   *   The shared service for CLI commands.
   */
  public function __construct(CliCommandsInterface $sitewideAlertCliCommands) {
    parent::__construct();
    $this->sitewideAlertCliCommands = $sitewideAlertCliCommands;
  }

  /**
   * Creates a new SitewideAlertCommands.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   *
   * @return self
   *   A SitewideAlertCommands.
   */
  public static function create(ContainerInterface $container): self {
    return new static($container->get('sitewide_alert.cli_commands'));
  }

  /**
   * Create a sitewide alert.
   *
   * @param string $label
   *   The label of the sitewide alert. This is an internal identifier which
   *   will not be shown to end users.
   * @param string $message
   *   The text content of the sitewide alert.
   * @param array $options
   *   Optional array of options keyed by option [start, end, severity].
   *
   * @command sitewide-alert:create
   *
   * @option start
   *   Optional time when the sitewide alert should appear. Use ISO 8601
   *   format ("2020-10-22T14:30:00-05:00") or in a human-readable format like
   *   "October 22, 2020" or "Saturday 12:30".
   * @option end
   *   Optional time when the sitewide alert should disappear. Use ISO 8601
   *   format ("2020-10-22T14:30:00-05:00") or in a human-readable format like
   *   "+6 hours" or "midnight".
   * @option severity
   *   Optional severity of the sitewide alert [low, medium (default), high].
   * @option active
   *   Marks the sitewide alert as active.
   *
   * @usage drush sitewide-alert:create "label" "Message"
   *   Create a sitewide-alert with the label and message with medium severity.
   *   The alert will be immediately visible and will remain so until manually
   *   disabled or deleted.
   * @usage drush sitewide-alert:create "label name" "message" --severity=high --no-active.
   *   Create a sitewide-alert with the label and message with high severity.
   *   The alert is inactive and will not be visible until activated.
   * @usage drush sitewide-alert:create "label name" "message" --start=2022-10-15T15:00:00 --end=2022-10-15T17:00:00
   *   Create a sitewide alert with the label and message that will be displayed
   *   between the start and end dates provided.
   * @usage drush sitewide-alert:create "label name" "message" --start=13:45 --end="tomorrow 13:45"
   *   Create a sitewide alert with the label and message that will be displayed
   *   this afternoon at 13:45 and will end tomorrow at the same time.
   * @usage drush sitewide-alert:create "label name" "message" --start="2 hours 30 minutes"
   *   Create a sitewide alert with the label and message that will be displayed
   *   150 minutes from now and will remain visible until manually disabled or
   *   deleted.
   * @usage drush sitewide-alert:create "label name" "message" --end="15 minutes"
   *   Create a sitewide alert with the label and message that will be displayed
   *   immediately and will disappear after 15 minutes.
   */
  public function createSitewideAlert(string $label, string $message, array $options = [
    'start' => NULL,
    'end' => NULL,
    'style' => NULL,
    'status' => TRUE,
    'dismissible' => FALSE,
  ]): int {
    try {
      $this->sitewideAlertCliCommands->create($label, $message, $options);
    }
    catch (\Exception $e) {
      $this->logger()->error($e->getMessage());
      return self::EXIT_FAILURE;
    }

    $this->logger()->success(dt("Created @name '@label'.", [
      '@name' => 'sitewide alert',
      '@label' => $label,
    ]));
    return self::EXIT_SUCCESS;
  }

  /**
   * Delete sitewide alert(s) matching the label.
   *
   * @param string $label
   *   The label of the sitewide alert(s) to delete.
   *
   * @command sitewide-alert:delete
   *
   * @usage drush sitewide-alert:delete "label"
   *   Delete any sitewide alerts that are active and have the label of "label".
   */
  public function deleteSitewideAlert(string $label): int {
    if (!$this->io()->confirm(dt("Are you sure you want to delete the sitewide alert labeled '@label'?", [
      '@label' => $label,
    ]))) {
      $this->logger()->warning('Operation cancelled by user');
      return self::EXIT_FAILURE;
    }

    try {
      $count = $this->sitewideAlertCliCommands->delete($label);
    }
    catch (\Exception $e) {
      $this->logger()->error($e->getMessage());
      return self::EXIT_FAILURE;
    }

    $vars = [
      '@name' => 'sitewide alerts',
      '@count' => $count,
      '@label' => $label,
    ];

    if ($count >= 1) {
      $this->logger()->success(dt("Deleted @count @name labelled '@label'.", $vars));
    }
    else {
      $this->logger()->notice(dt("Found no @name with label '@label' to delete.", $vars));
    }
    return self::EXIT_SUCCESS;
  }

  /**
   * Disable sitewide alert(s).
   *
   * @param string|null $label
   *   The label of sitewide alert to disable. If no label is passed all
   *   sitewide alerts will be disabled.
   *
   * @command sitewide-alert:disable
   *
   * @usage drush sitewide-alert:disable
   *   Disable all sitewide alerts.
   * @usage drush sitewide-alert:disable "my-alert"
   *   Disable the sitewide alert with the label "my-alert".
   */
  public function disableSitewideAlert(?string $label = NULL): int {
    try {
      $count = $this->sitewideAlertCliCommands->disable($label);
    }
    catch (\Exception $e) {
      $this->logger()->error($e->getMessage());
      return self::EXIT_FAILURE;
    }

    if ($count === 0) {
      // If a specific alert was given, and it could not be disabled, then the
      // user has given invalid input. Alert the user by returning an error.
      if (!empty($label)) {
        $this->logger()->error(dt("No active sitewide alerts found with the label '@label'.", ['@label' => $label]));
        return self::EXIT_FAILURE;
      }
      else {
        $this->logger()->notice('There were no sitewide alerts to disable.');
      }
    }
    elseif (empty($label)) {
      $this->logger()->success('All active sitewide alerts have been disabled.');
    }
    else {
      $this->logger()->success(dt("Disabled sitewide alert '@label'.", ['@label' => $label]));
    }
    return self::EXIT_SUCCESS;
  }

  /**
   * Enable a sitewide alert.
   *
   * @param string $label
   *   The label of sitewide alert to enable.
   *
   * @command sitewide-alert:enable
   *
   * @usage drush sitewide-alert:enable my-alert
   *   Enable the sitewide alert with the label "my-alert".
   */
  public function enableSitewideAlert(string $label): int {
    try {
      $count = $this->sitewideAlertCliCommands->enable($label);
    }
    catch (\Exception $e) {
      $this->logger()->error($e->getMessage());
      return self::EXIT_FAILURE;
    }

    if ($count === 0) {
      $this->logger()->error(dt("No inactive sitewide alerts found with the label '@label'.", ['@label' => $label]));
      return self::EXIT_FAILURE;
    }

    $this->logger()->success((string) dt("Enabled sitewide alert '@label'.", ['@label' => $label]));
    return self::EXIT_SUCCESS;
  }

}
