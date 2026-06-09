<?php

namespace Drupal\sitewide_alert_domain;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\sitewide_alert\SitewideAlertManager;

/**
 * SitewideAlertManager for Domain integration decorating the original service.
 */
class SitewideAlertDomainManager extends SitewideAlertManager {

  /**
   * SitewideAlertManager original service object.
   *
   * @var \Drupal\sitewide_alert\SitewideAlertManager
   */
  protected $siteAlertManagerOriginalService;

  /**
   * The domain negotiator service.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * Constructs a new SitewideAlertDomainManager.
   *
   * @param \Drupal\sitewide_alert\SitewideAlertManager $sitewideAlertManager
   *   The original service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository.
   * @param \Drupal\domain\DomainNegotiatorInterface $domainNegotiator
   *   The domain negotiator service.
   */
  public function __construct(SitewideAlertManager $sitewideAlertManager,
                              EntityTypeManagerInterface $entityTypeManager,
                              TimeInterface $time,
                              LanguageManagerInterface $languageManager,
                              EntityRepositoryInterface $entityRepository,
                              DomainNegotiatorInterface $domainNegotiator) {
    parent::__construct($entityTypeManager, $time, $languageManager, $entityRepository);
    $this->siteAlertManagerOriginalService = $sitewideAlertManager;
    $this->domainNegotiator = $domainNegotiator;
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
    return $this->removeSitewideAlertsAccordingToDomainConfig(
      $this->siteAlertManagerOriginalService->activeVisibleSitewideAlerts()
    );
  }

  /**
   * Removes the sitewide alerts that are not enabled for the active domain.
   *
   * @param \Drupal\sitewide_alert\Entity\SitewideAlertInterface[] $sitewideAlerts
   *   Array of active sitewide alerts.
   *
   * @return \Drupal\sitewide_alert\Entity\SitewideAlertInterface[]
   *   Array of sitewide alerts that should be shown on the active domain.
   */
  private function removeSitewideAlertsAccordingToDomainConfig(array $sitewideAlerts): array {
    $domainAccessField = FieldStorageConfig::loadByName('sitewide_alert', 'domain_access');

    // If the domain_access field does not exist it means Domain Access
    // (domain_entity) has not been enabled for Sitewide Alert entity and
    // we can ignore.
    if (!$domainAccessField) {
      return $sitewideAlerts;
    }

    $activeDomain = $this->domainNegotiator->getActiveDomain()->id();

    // Remove any sitewide alerts that are not enabled in the current domain.
    foreach ($sitewideAlerts as $id => $sitewideAlert) {
      $sitewideAlertDomainAccess = $sitewideAlert->get('domain_access')->getValue();
      $sitewideAlertDomains = [];
      foreach ($sitewideAlertDomainAccess as $sitewideAlertDomain) {
        $sitewideAlertDomains[] = $sitewideAlertDomain['target_id'];
      }
      if (!in_array($activeDomain, $sitewideAlertDomains)) {
        unset($sitewideAlerts[$id]);
      }
    }

    return $sitewideAlerts;
  }

}
