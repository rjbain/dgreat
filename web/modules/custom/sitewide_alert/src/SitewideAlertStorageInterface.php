<?php

namespace Drupal\sitewide_alert;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\sitewide_alert\Entity\SitewideAlertInterface;

/**
 * Defines the storage handler class for Sitewide Alert entities.
 *
 * This extends the base storage class, adding required special handling for
 * Sitewide Alert entities.
 *
 * @ingroup sitewide_alert
 */
interface SitewideAlertStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Sitewide Alert revision IDs for a specific Sitewide Alert.
   *
   * @param \Drupal\sitewide_alert\Entity\SitewideAlertInterface $entity
   *   The Sitewide Alert entity.
   *
   * @return int[]
   *   Sitewide Alert revision IDs (in ascending order).
   */
  public function revisionIds(SitewideAlertInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Sitewide Alert author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Sitewide Alert revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\sitewide_alert\Entity\SitewideAlertInterface $entity
   *   The Sitewide Alert entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(SitewideAlertInterface $entity);

  /**
   * Unsets the language for all Sitewide Alert with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
