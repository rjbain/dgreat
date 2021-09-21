<?php

namespace Drupal\dgreat_group;


use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Group;
use Drupal\user\Entity\User;

class RoleGroupMapper {

  /**
   * Ensure that the user's group access matches their roles.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return User The user.
   */
  public static function reconcileGroupAccess(AccountInterface $account) {
    // Transmogrify the Account to a full on user.
    $user = User::load($account->id());
    // Get Groups that have a mapping.
    $groups = \Drupal::entityQuery('group')
                     ->exists('field_mapped_roles')
                     ->execute();

    // Map over the groups, if the user doesn't have the right role,
    // Remove them, if they do have the right role, open up the gates.
    return collect($groups)->map(function ($group) use ($user) {
      if (self::userHasGroupRole($user, $group)) {
        return self::grantGroupAccess($user, $group);
      }
      return self::revokeGroupAccess($user, $group);
    });

  }

  /**
   * Grant a user access to a group based on their role.
   *
   * @param \Drupal\user\Entity\User $user
   * @param $group_id
   *
   * @return User The user.
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function grantGroupAccess(User $user, $group_id) {
    // Ensure we don't duplicate the membership
//    if (!self::userIsMemberOfGroup($user, $group_id)) {
      $user->field_user_group[] = ['target_id' => $group_id];
      $user->save();
      // Add the user to the group.
      Group::load($group_id)->addMember($user);
//    }
//    else {
//      // Check and apply default content since we are not saving the user.
//      (new DgreatGroup($user))->flagUserDefaultContent($user);
//    }
    return $user;
  }

  /**
   * Revoke access to groups that the user no longer has roles for.
   *
   * @param \Drupal\user\Entity\User $user
   * @param $group_id
   *
   * @return User The user.
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function revokeGroupAccess(User $user, $group_id) {
    Group::load($group_id)->removeMember($user);
    return $user;
  }



  /**
   * Check if the user has a role that is mapped to the given group.
   *
   * @param \Drupal\user\Entity\User $user
   * @param $group_id
   *
   * @return bool
   */
  public static function userHasGroupRole(User $user, $group_id) {
//      \Drupal::logger('dgreat_group')->error('userHasGroupRole is called.');
    $group  = Group::load($group_id);
    $mapped_roles = $group->get('field_mapped_roles')->getValue();
//      \Drupal::logger('dgreat_group')->notice('Mapped Roles: @details.', ['@details' => print_r($mapped_roles, TRUE)]);

    return collect($mapped_roles)->filter(function ($role) use ($user) {
      return in_array($role['target_id'], $user->getRoles());
    })->count() >= 1;
  }

  /**
   * Check if the user is a member of a given group ID. This is a bit fuzzy
   * because of our group implementation.
   *
   * @param \Drupal\user\Entity\User $user
   * @param $group_id
   *
   * @return bool
   */
  private static function userIsMemberOfGroup(User $user, $group_id) {

    $hasGroupField = collect($user->get('field_user_group')->getValue())
        ->filter(function ($group) use ($group_id) {
          return $group['target_id'] == $group_id;
        })->count() >= 1;

    $hasGroupMembership = Group::load($group_id)->getMember($user);

//      \Drupal::logger('dgreat_group')->notice('hasGroupField: @details.', ['@details' => print_r($hasGroupField, TRUE)]);
//      \Drupal::logger('dgreat_group')->notice('hasGroupMembership: @details.', ['@details' => print_r($hasGroupMembership, TRUE)]);

    return $hasGroupField || $hasGroupMembership;

  }
}
