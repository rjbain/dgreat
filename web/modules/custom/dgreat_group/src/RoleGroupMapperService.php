<?php

namespace Drupal\dgreat_group;


use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Group;
use Drupal\user\Entity\User;

class RoleGroupMapperService {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Ensure that the user's group access matches their roles.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return array|false
   */
  public function reconcileGroupAccess(AccountInterface $account) {
    // Transmogrify the Account to a full on user.
    try {
      $user = $this->entityTypeManager->getStorage('user')
                                      ->load($account->id());
      // Get Groups that have a mapping.
      $groups = $this->getMappedGroups();
      // Map over the groups, if the user doesn't have the right role,
      // Remove them, if they do have the right role, open up the gates.
      $results = collect($groups)->map(function ($group) use ($user) {
        if ($this->userHasGroupRole($user, $group)) {
          $this->grantGroupAccess($user, $group);
          $result = 'added';
        } else {
          $this->revokeGroupAccess($user, $group);
          $result = 'removed';
        }
        return ['group' => $group, 'result' => $result];
      });
      return $results->toArray();
    } catch (\Exception $exception) {
      \Drupal::logger('dgreat_group')->error(
        'Failed to reconcile group access for user id %u: Error %e',
        [
          '%u' => $user->id(),
          '%e' => $exception->getMessage()
        ]
      );
      return false;
    }
  }

  /**
   * Grant a user access to a group based on their role.
   *
   * @param \Drupal\user\Entity\User $user
   * @param string $group_id
   *
   * @return bool Whether we were able to grant group access.
   */
  public function grantGroupAccess(User $user, string $group_id = ""): bool {
    // Ensure we don't duplicate the membership
    try {
      $this->ensureUserHasGroupField($user, $group_id);
      // Add the user to the group.
      /** @var \Drupal\group\Entity\Group $group */
      $group = $this->entityTypeManager
        ->getStorage('group')
        ->load($group_id);
      $group
        ->addMember($user);
      $group->save();
      if (!$this->userIsMemberOfGroup($user, $group_id)) {
        // Check and apply default content since we are not saving the user.
        (new DgreatGroup($user))->flagUserDefaultContent($user);
      }
      return TRUE;
    } catch (\Exception $exception) {
      \Drupal::logger('dgreat_group')->error(
        'Unable to grant user id %u to group id %g: Error %e',
        [
          '%u' => $user->id(),
          '%g' => $group_id,
          '%e' => $exception->getMessage(),
        ]
      );
      return FALSE;
    }
  }

  /**
   * Revoke access to groups that the user no longer has roles for.
   *
   * @param \Drupal\user\Entity\User $user
   * @param $group_id
   *
   * @return bool whether we successfully removed the user from the
   *   group.
   */
  public function revokeGroupAccess(User $user, $group_id): bool {
    try {
      $group = $this->entityTypeManager
        ->getStorage('group')
        ->load($group_id);
      $group->removeMember($user);
      $group->save();
      $this->removeGroupFieldFromUser($user, $group_id);
      return TRUE;
    } catch (\Exception $exception) {
      \Drupal::logger('dgreat_group')->error(
        'Unable to remove user id %u from group id %g',
        ['%u' => $user->id(), '%g' => $group_id]
      );
      return FALSE;
    }
  }


  /**
   * Check if the user has a role that is mapped to the given group.
   *
   * @param \Drupal\user\Entity\User $user
   * @param $group_id
   *
   * @return bool
   */
  public function userHasGroupRole(User $user, $group_id): bool {
    $group = Group::load($group_id);
    $mapped_roles = $group->get('field_mapped_roles')->getValue();

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
  private function userIsMemberOfGroup(User $user, $group_id): bool {
    return $this->entityTypeManager
                ->getStorage('group')
                ->load($group_id)
                ->getMember($user) !== FALSE;
  }

  /**
   * @param \Drupal\user\Entity\User $user
   * @param $group_id
   *
   * @return bool
   */
  private function userHasGroupField(User $user, $group_id): bool {
    return collect($user->get('field_user_group')->getValue())
        ->filter(function ($group) use ($group_id) {
          return $group['target_id'] == $group_id;
        })->count() >= 1;
  }

  /**
   * @param \Drupal\user\Entity\User $user
   * @param string $group_id
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function ensureUserHasGroupField(User $user, string $group_id): void {
    if (!$this->userHasGroupField($user, $group_id)) {
      $user->field_user_group[] = ['target_id' => $group_id];
      $user->save();
    }
  }

  /**
   * Remove the group field from the user.
   * 
   * @param \Drupal\user\Entity\User $user
   * @param string $group_id
   * 
   * @return bool
   */
  public function removeGroupFieldFromUser(User $user, string $group_id) {
    $field = $user->get('field_user_group');
    $values = $field->getValue();
    $index_to_remove = array_search($group_id, array_column($values, 'target_id'));
    $field->removeItem($index_to_remove);
    $user->save();
    return TRUE;
  }

  /**
   * @return array|int
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getMappedGroups() {
    $groups = $this->entityTypeManager
                   ->getStorage('group')
                   ->getQuery()
                   ->exists('field_mapped_roles')
                   // @see https://www.drupal.org/project/group/issues/3063776#comment-13737442
                   ->accessCheck(FALSE)
                   ->execute();
    return $groups;
  }

}
