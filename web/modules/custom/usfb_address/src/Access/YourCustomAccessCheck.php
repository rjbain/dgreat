<?php
namespace Drupal\usfb_address\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Checks access for displaying configuration translation page.
 */
class YourCustomAccessCheck implements AccessInterface{

    /**
     * A custom access check.
     *
     * @param \Drupal\Core\Session\AccountInterface $account
     *   Run access checks for this account.
     *
     * @return \Drupal\Core\Access\AccessResultInterface
     *   The access result.
     */
    public function access(AccountInterface $account, $user) {

        // Check if admin has "Administer users" permission
        return AccessResult::allowedIfHasPermission($account, 'administer users')
            // Check if current user id = visited user id
            ->orIf(AccessResult::allowedIf($user == $account->id()));
    }

}