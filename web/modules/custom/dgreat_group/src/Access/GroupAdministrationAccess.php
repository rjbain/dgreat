<?php

namespace Drupal\dgreat_group\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Provides route access for group administration pages.
 */
class GroupAdministrationAccess {

  /**
   * Grants site administrators access to the all entities page for any group.
   */
  public static function accessAllEntities(GroupInterface $group, AccountInterface $account) {
    $allowed = in_array('administrator', $account->getRoles(), TRUE) || $group->hasPermission('access content overview', $account);

    return AccessResult::allowedIf($allowed)
      ->cachePerUser()
      ->addCacheableDependency($group);
  }

}
