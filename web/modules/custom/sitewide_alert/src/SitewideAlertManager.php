<?php

declare(strict_types=1);

namespace Drupal\sitewide_alert;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Manager for working with sitewide alert entities.
 */
class SitewideAlertManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * Time of current request.
   *
   * @var \DateTimeInterface
   */
  private $requestDateTime;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, TimeInterface $time, LanguageManagerInterface $languageManager, EntityRepositoryInterface $entityRepository) {
    $this->entityTypeManager = $entityTypeManager;
    $this->time = $time;
    $this->languageManager = $languageManager;
    $this->entityRepository = $entityRepository;
  }

  /**
   * Returns all active sitewide alerts.
   *
   * @return \Drupal\sitewide_alert\Entity\SitewideAlertInterface[]
   *   Array of active sitewide alerts indexed by their ids.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function activeSitewideAlerts(): array {
    $sitewideAlertStorage = $this->entityTypeManager
      ->getStorage('sitewide_alert');
    $langcode = $this->languageManager->getCurrentLanguage()->getId();

    $sitewideAlertIds = $sitewideAlertStorage
      ->getQuery()
      ->condition('status', 1)
      ->condition('langcode', $sitewideAlertStorage->getEntityType()->isTranslatable() ? $langcode : LanguageInterface::LANGCODE_DEFAULT)
      ->accessCheck(TRUE)
      ->execute();

    /** @var \Drupal\sitewide_alert\Entity\SitewideAlertInterface[] $sitewideAlerts */
    $sitewideAlerts = $sitewideAlertStorage->loadMultiple($sitewideAlertIds);
    foreach ($sitewideAlerts as $key => $alert) {
      $sitewideAlerts[$key] = $this->entityRepository->getTranslationFromContext($alert, $langcode);
    }

    return $sitewideAlerts;
  }

  /**
   * Returns all active and currently visible sitewide alerts.
   *
   * @return \Drupal\sitewide_alert\Entity\SitewideAlertInterface[]
   *   Array of active sitewide alerts indexed by their ids.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function activeVisibleSitewideAlerts(): array {
    /** @var \Drupal\sitewide_alert\Entity\SitewideAlertInterface[] $activeVisibleSitewideAlerts */
    $activeVisibleSitewideAlerts = $this->activeSitewideAlerts();

    // Remove any alerts that are scheduled and it is not time to show them.
    foreach ($activeVisibleSitewideAlerts as $id => $sitewideAlert) {
      if ($sitewideAlert->isScheduled() &&
        !$sitewideAlert->isScheduledToShowAt($this->requestDateTime())) {
        unset($activeVisibleSitewideAlerts[$id]);
      }
    }

    return $activeVisibleSitewideAlerts;
  }

  /**
   * The time of the next scheduled change of alerts.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   Time of next scheduled change of alerts; null if nothing is scheduled to
   *   change.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function nextScheduledChange(): ?DrupalDateTime {
    $nextExpiring = $this->soonestExpiringVisibleScheduledAlertDateTime();
    $nextShowing = $this->soonestAppearingScheduledAlertDateTime();

    if ($nextExpiring && $nextShowing) {
      return $nextShowing > $nextExpiring ? $nextExpiring : $nextShowing;
    }

    if ($nextShowing) {
      return $nextShowing;
    }

    if ($nextExpiring) {
      return $nextExpiring;
    }

    return NULL;
  }

  /**
   * Determines the datetime of the soonest expiring visible scheduled alert.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   The datetime of the soonest expiring scheduled alert; null if none of the
   *   alerts are scheduled to expire.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function soonestExpiringVisibleScheduledAlertDateTime(): ?DrupalDateTime {
    /** @var \Drupal\Core\Datetime\DrupalDateTime|null $soonestScheduledEndDate */
    $soonestScheduledEndDate = NULL;

    foreach ($this->activeVisibleSitewideAlerts() as $sitewideAlert) {
      if (!$sitewideAlert->isScheduled()) {
        continue;
      }

      if (!$endDateTime = $sitewideAlert->getScheduledEndDateTime()) {
        continue;
      }

      if ($soonestScheduledEndDate === NULL) {
        $soonestScheduledEndDate = $endDateTime;
        continue;
      }

      if ($soonestScheduledEndDate > $endDateTime) {
        $soonestScheduledEndDate = $endDateTime;
      }
    }

    return $soonestScheduledEndDate;
  }

  /**
   * Determines the datetime of the soonest expiring scheduled alert.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   The datetime of the soonest expiring scheduled alert; null if none of the
   *   alerts are scheduled to expire.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function soonestAppearingScheduledAlertDateTime(): ?DrupalDateTime {
    /** @var \Drupal\Core\Datetime\DrupalDateTime|null $soonestScheduledEndDate */
    $soonestScheduledStartDate = NULL;

    foreach ($this->activeSitewideAlerts() as $sitewideAlert) {
      if (!$sitewideAlert->isScheduled()) {
        continue;
      }

      if (!$startDateTime = $sitewideAlert->getScheduledStartDateTime()) {
        continue;
      }

      if ($startDateTime->getPhpDateTime() < $this->requestDateTime()) {
        continue;
      }

      if ($soonestScheduledStartDate === NULL) {
        $soonestScheduledStartDate = $startDateTime;
        continue;
      }

      if ($soonestScheduledStartDate > $startDateTime) {
        $soonestScheduledStartDate = $startDateTime;
      }
    }

    return $soonestScheduledStartDate;
  }

  /**
   * The datetime of the current request.
   *
   * @return \DateTime
   *   The DateTime of the current request.
   */
  protected function requestDateTime(): \DateTime {
    if (!$this->requestDateTime) {
      $this->requestDateTime = new \DateTime();
      $this->requestDateTime->setTimestamp($this->time->getRequestTime());
    }

    return $this->requestDateTime;
  }

}
