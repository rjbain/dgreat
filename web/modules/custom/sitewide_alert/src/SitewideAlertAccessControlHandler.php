<?php

declare(strict_types=1);

namespace Drupal\sitewide_alert;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Sitewide Alert entity.
 *
 * @see \Drupal\sitewide_alert\Entity\SitewideAlert.
 */
class SitewideAlertAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\sitewide_alert\Entity\SitewideAlertInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished sitewide alert entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published sitewide alert entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit sitewide alert entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete sitewide alert entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add sitewide alert entities');
  }

}
