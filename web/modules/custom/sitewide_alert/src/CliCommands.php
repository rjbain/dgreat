<?php

declare(strict_types = 1);

namespace Drupal\sitewide_alert;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Service with shared code for CLI tools to perform common tasks.
 */
class CliCommands implements CliCommandsInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a new CliCommands service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function create(string $label, string $message, array $options): void {
    $this->validateCreateInput($label, $message, $options);

    // Set possible options.
    $start = $options['start'] ?? NULL;
    $end = $options['end'] ?? NULL;
    $status = $options['status'] ?? TRUE;
    $dismissible = $options['dismissible'] ?? NULL;
    $style = $this->normalizeStyle($options['style'] ?? 'primary');

    $storage = $this->entityTypeManager->getStorage('sitewide_alert');
    $entity_values = [
      'status' => $status,
      'name' => $label,
      'style' => $style,
      'dismissible' => $dismissible,
      'message' => $message,
    ];

    if (!empty($start) || !empty($end)) {
      $entity_values['scheduled_alert'] = TRUE;
      $entity_values['scheduled_date'] = [
        'value' => $start,
        'end_value' => $end,
      ];
    }

    $sitewideAlert = $storage->create($entity_values);
    $storage->save($sitewideAlert);
  }

  /**
   * {@inheritdoc}
   */
  public function validateCreateInput(string $label, string $message, array &$options): void {
    // Validate the label parameter.
    if (empty($label)) {
      throw new \InvalidArgumentException('A label is required.');
    }

    // Validate the message parameter.
    if (empty($message)) {
      throw new \InvalidArgumentException('A message is required.');
    }

    // Validate the 'start' and 'end' options.
    foreach (['start', 'end'] as $option) {
      if (!empty($options[$option])) {
        if (strtotime($options[$option]) === FALSE) {
          throw new \InvalidArgumentException(sprintf("Invalid date format for '%s' option.", $option));
        }
        $options[$option] = (new DrupalDateTime($options[$option], DateTimeItemInterface::STORAGE_TIMEZONE))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
      }
      // If only an end time is passed, use now as start date.
      if (isset($options['end']) && empty($options['start'])) {
        $options['start'] = (new DrupalDateTime('now', DateTimeItemInterface::STORAGE_TIMEZONE))->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
      }
    }

    // Validate the 'status' option.
    if (isset($options['status']) && !is_bool($options['status'])) {
      throw new \InvalidArgumentException("The 'status' option should be a boolean value.");
    }

    // Validate the 'style' option.
    if (!empty($options['style'])
      && !array_key_exists($options['style'], AlertStyleProvider::alertStyles())) {
      throw new \InvalidArgumentException(sprintf("The 'style' option should be one of %s.", implode(',', array_keys(AlertStyleProvider::alertStyles()))));
    }

    // Validate the 'dismissible' option.
    if (isset($options['dismissible']) && !is_bool($options['dismissible'])) {
      throw new \InvalidArgumentException("The 'dismissible' option should be a boolean value.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete(string $label): int {
    if (empty($label)) {
      throw new \InvalidArgumentException('A label is required.');
    }

    $sitewideAlerts = $this->getAlertsByLabel($label);
    $count = count($sitewideAlerts);

    $this->entityTypeManager->getStorage('sitewide_alert')->delete($sitewideAlerts);

    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function disable(?string $label = NULL): int {
    if (empty($label)) {
      $sitewideAlerts = $this->entityTypeManager->getStorage('sitewide_alert')->loadByProperties(['status' => 1]);
    }
    else {
      $sitewideAlerts = $this->getAlertsByLabel($label, TRUE);
      if (empty($sitewideAlerts)) {
        throw new \InvalidArgumentException(sprintf("No active sitewide alerts found with the label '%s'.", $label));
      }
    }

    $count = 0;
    foreach ($sitewideAlerts as $alert) {
      $alert->set('status', FALSE)->save();
      $count++;
    }
    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function enable(string $label): int {
    $sitewideAlerts = $this->getAlertsByLabel($label, FALSE);
    if (empty($sitewideAlerts)) {
      throw new \InvalidArgumentException(sprintf("No inactive sitewide alerts found with the label '%s'.", $label));
    }

    $count = 0;
    foreach ($sitewideAlerts as $alert) {
      $alert->set('status', TRUE)->save();
      $count++;
    }
    return $count;
  }

  /**
   * Returns all sitewide alerts that match the given label.
   *
   * @param string $label
   *   The label to match.
   * @param bool|null $status
   *   When TRUE or FALSE only active or inactive site alerts are returned. If
   *   NULL, both are returned.
   *
   * @return \Drupal\sitewide_alert\Entity\SitewideAlertInterface[]
   *   An array of sitewide alert entities that match the label.
   */
  protected function getAlertsByLabel(string $label, ?bool $status = NULL): array {
    $sitewideAlerts = [];
    if (!empty($label)) {
      $storage = $this->entityTypeManager->getStorage('sitewide_alert');
      $query = $storage->getQuery()
        ->condition('name', $label, '=')
        ->accessCheck(FALSE);

      if ($status !== NULL) {
        $query->condition('status', $status);
      }
      $result = $query->execute();

      if (!empty($result)) {
        $sitewideAlerts = $storage->loadMultiple($result);
      }
    }

    return $sitewideAlerts;
  }

  /**
   * Normalizes style to an allowed value.
   *
   * @param string $style
   *   One of the values defined in the sitewide_alert.settings for styles.
   *
   * @return string
   *   The normalized style.
   */
  protected function normalizeStyle(string $style = 'primary'): string {
    $style = trim($style);
    $style = strtolower($style);
    $allowed_styles = array_keys(AlertStyleProvider::alertStyles());

    if (!in_array($style, $allowed_styles)) {
      $style = 'primary';
    }

    return $style;
  }

}
