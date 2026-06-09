<?php

declare(strict_types=1);

namespace Drupal\sitewide_alert\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Sitewide Alert entities.
 *
 * @ingroup sitewide_alert
 */
interface SitewideAlertInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Sitewide Alert name.
   *
   * @return string
   *   Name of the Sitewide Alert.
   */
  public function getName(): string;

  /**
   * Sets the Sitewide Alert name.
   *
   * @param string $name
   *   The Sitewide Alert name.
   *
   * @return \Drupal\sitewide_alert\Entity\SitewideAlertInterface
   *   The called Sitewide Alert entity.
   */
  public function setName(string $name): SitewideAlertInterface;

  /**
   * Gets the Sitewide Alert creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Sitewide Alert.
   */
  public function getCreatedTime();

  /**
   * Sets the Sitewide Alert creation timestamp.
   *
   * @param int $timestamp
   *   The Sitewide Alert creation timestamp.
   *
   * @return \Drupal\sitewide_alert\Entity\SitewideAlertInterface
   *   The called Sitewide Alert entity.
   */
  public function setCreatedTime(int $timestamp): SitewideAlertInterface;

  /**
   * Returns weather or not the Sitewide Alert is scheduled.
   *
   * @return bool
   *   TRUE if the sitewide alert is scheduled, FALSE otherwise.
   */
  public function isScheduled(): bool;

  /**
   * Determines if this SitewideAlert should be shown at the given time.
   *
   * @param \DateTime $dateTime
   *   The time to compare.
   *
   * @return bool
   *   TRUE if this Alert should show at the given time, FALSE otherwise.
   */
  public function isScheduledToShowAt(\DateTime $dateTime): bool;

  /**
   * Gets the start date of this Sitewide Alert.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   The date when this alert is scheduled to start, null otherwise;
   */
  public function getScheduledStartDateTime(): ?DrupalDateTime;

  /**
   * Gets the end date of this Sitewide Alert.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   The date when this alert is scheduled to end, null otherwise;
   */
  public function getScheduledEndDateTime(): ?DrupalDateTime;

  /**
   * Returns weather or not the Sitewide Alert is dismissible.
   *
   * @return bool
   *   TRUE if the sitewide alert is dismissible, FALSE otherwise.
   */
  public function isDismissible();

  /**
   * Gets the timestamp of when any dismissals should be ignored before.
   *
   * @return int
   *   The UNIX timestamp of when any dismissals should be ignored before.
   */
  public function getDismissibleIgnoreBeforeTime(): int;

  /**
   * Sets the timestamp of when any dismissals should be ignored before.
   *
   * @param int $timestamp
   *   The UNIX timestamp oof when any dismissals should be ignored before.
   *
   * @return $this
   */
  public function setDismissibleIgnoreBeforeTime($timestamp): self;

  /**
   * Gets the style of the alert.
   *
   * @return string
   *   The style.
   */
  public function getStyle(): string;

  /**
   * Gets the style class to use for the alert.
   *
   * @return string
   *   The style class to use.
   */
  public function getStyleClass(): string;

  /**
   * Gets the pages to show on.
   *
   * @return array
   *   The patterns of pages to show on.
   */
  public function getPagesToShowOn(): array;

  /**
   * Should we negate the pages we show on.
   *
   * @return bool
   *   TRUE if we should negate the page patterns, FALSE otherwise.
   */
  public function shouldNegatePagesToShowOn(): bool;

}
