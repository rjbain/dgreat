<?php

namespace Drupal\dgreat_group;

use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 *
 */
class DgreatGroup {

  /**
   * @var User
   */
  protected $entity;

  /**
   * DgreatGroup constructor.
   */
  public function __construct($entity) {
    $this->entity = $entity;
  }

  /**
   * Adds a node to a group specified by a ER field on the node.
   *
   * @param $field
   *   The field we are using as a reference for the group.
   *
   * @return bool
   */
  public function addNodeToGroup($field) {
    $plugin_id = 'group_node:' . $this->entity->bundle();
    $group_ids = $this->entity->get($field)->getValue();

    foreach ($group_ids as $group_id) {
      // If it is assigned, then lets do the magix.
      if (isset($group_id['target_id'])) {

        $group = Group::load($group_id['target_id']);

        // Unpublished groups were not migrated.
        // This prevents the failure due to this.
        if ($group === NULL) {
          continue;
        }

        // Lets skip if the node already exists in that group.
        $check = $group->getRelationshipsByEntity($this->entity, $plugin_id);
        if (!empty($check)) {
          continue;
        }

        // Add the content to the group.
        $group->addRelationship($this->entity, $plugin_id);
      }
    }

    // Fail safe return.
    return FALSE;
  }

  /**
   * Adds a user to a group specified by a ER field on the user profile.
   *
   * @param $field
   *   The field we are using as a reference for the group.
   *
   * @return $this
   */
  public function addUserToGroup($field) {
    $group_ids = $this->entity->get($field)->getValue();

    // Let's go through Each Group and add users.
    foreach ($group_ids as $gid) {
      if (isset($gid['target_id'])) {

        $group = Group::load($gid['target_id']);

        if ($group !== NULL) {
          $group->addMember($this->entity);
        }
      }
    }

    // Fail safe return.
    return $this;
  }

  /**
   * Prepares the groups for quick links before the initial save.
   *
   * @return bool
   */
  public function prepareQuickLinkGroups() {
    // Grab the quick link field.
    $quick_link = $this->entity->get('field_link_type')->getValue();

    if (isset($quick_link[0]['value']) && $quick_link[0]['value'] === 'quick') {
      // Grab our current user and their group ids.
      $uid = \Drupal::currentUser()->id();
      $user = User::load($uid);
      $groups = $user->get('field_user_group')->getValue();
      $gids = [];
      foreach ($groups as $gid) {
        $gids[] = $gid['target_id'];
      }

      if (empty($gids)) {
        return FALSE;
      }

      // Apply the groups.
      $this->entity->set('field_group_audience', $gids);

      return TRUE;
    }

    return FALSE;
  }

  /**
   * Completes quick link setup that requires the node to already exist.
   *
   * @return bool
   */
  public function finalizeQuickLinkGroups() {
    // Grab the quick link field.
    $quick_link = $this->entity->get('field_link_type')->getValue();

    if (isset($quick_link[0]['value']) && $quick_link[0]['value'] === 'quick') {
      $uid = \Drupal::currentUser()->id();
      $user = User::load($uid);

      if ($user === NULL || $this->entity->id() === NULL) {
        return FALSE;
      }

      // Flag the content.
      $flag_service = \Drupal::service('flag');
      $flag = $flag_service->getFlagById('favorite');
      $node = Node::load($this->entity->id());

      if ($flag === NULL || $node === NULL) {
        return FALSE;
      }

      $this->ensureQuickLinkMembershipRoles($user);

      $flag_service->flag($flag, $node, $user);

      // Add this to the user weights table.
      $db = \Drupal::database();
      $check = $db
        ->select('user_weights', 'u')
        ->fields('u', ['entity_id'])
        ->condition('uid', $uid)
        ->condition('entity_id', $this->entity->id())
        ->condition('view_name', 'quick_links')
        ->execute()
        ->fetchField();

      // Grab the new weight.
      $sql = "SELECT MAX(weight) FROM {user_weights} WHERE uid = :uid";
      $weight = $db
        ->query($sql, [':uid' => $uid])
        ->fetchField();

      if ($check === FALSE) {
        // Insert new item in weights table.
        $db->insert('user_weights')
          ->fields([
            'entity_id' => $this->entity->id(),
            'uid' => $uid,
            'view_name' => 'quick_links',
            'weight' => $weight + 1,
          ])
          ->execute();
      }
      else {
        // Update the weights table.
        $db->update('user_weights')
          ->condition('uid', $uid)
          ->condition('entity_id', $this->entity->id())
          ->condition('view_name', 'quick_links')
          ->fields([
            'entity_id' => $this->entity->id(),
            'uid' => $uid,
            'view_name' => 'quick_links',
            'weight' => $weight + 1,
          ])
          ->execute();
      }

      // Keep a persistent history row so removed quick links can still appear
      // on /quick-links/allapps and be added back later.
      $history_check = $db
        ->select('user_weights', 'u')
        ->fields('u', ['entity_id'])
        ->condition('uid', $uid)
        ->condition('entity_id', $this->entity->id())
        ->condition('view_name', 'quick_links_history')
        ->execute()
        ->fetchField();

      if ($history_check === FALSE) {
        $db->insert('user_weights')
          ->fields([
            'entity_id' => $this->entity->id(),
            'uid' => $uid,
            'view_name' => 'quick_links_history',
            'weight' => $weight + 1,
          ])
          ->execute();
      }

      return TRUE;
    }

    return FALSE;
  }

  /**
   * Ensures the quick link creator has default roles on audience groups.
   *
   * Some upgraded memberships are missing rows in
   * group_relationship__group_roles, which prevents the creator from viewing
   * their own quick links on the dashboard. When a quick link is created, we
   * self-heal any missing default member roles for its audience groups.
   *
   * @param \Drupal\user\Entity\User $user
   *   The quick link creator.
   */
  protected function ensureQuickLinkMembershipRoles(User $user) {
    $groups = $this->entity->get('field_group_audience')->getValue();

    foreach ($groups as $group_item) {
      if (empty($group_item['target_id'])) {
        continue;
      }

      $group = Group::load($group_item['target_id']);
      if ($group === NULL) {
        continue;
      }

      $membership = $group->getMember($user);
      if (!$membership) {
        continue;
      }

      $default_role_id = $group->bundle() . '-member';
      $role_ids = array_map(static fn($role) => $role->id(), $membership->getRoles());
      if (in_array($default_role_id, $role_ids, TRUE)) {
        continue;
      }

      $membership->addRole($default_role_id);
      $membership->getGroupRelationship()->save();
    }
  }

  /**
   * Flags the Defaults for content per user.
   *
   * @param User $user
   *
   * @return \Drupal\dgreat_group\DgreatGroup
   */
  public function flagUserDefaultContent(User $user) {
    
    $nids = $this->getUserDefaultFlags($user);

    // Let's go through Each Node and flag each node.
    if (!empty($nids)) {

      $db = \Drupal::database();
      $uid = $this->entity->id();

      // It is vastly more performant to do the insert clause this way.
      $insert = "INSERT INTO {user_weights} (entity_id, uid, view_name, weight) VALUES ";

      // Grab the new weight.
      $sql = "SELECT MAX(weight) FROM {user_weights} WHERE uid = :uid";
      $weight = $db
        ->query($sql, [':uid' => $uid])
        ->fetchField();

      // No user weights setup, add a default one.
      if ($weight == NULL) {
        $weight = 0;
      }

      $results = FALSE;

      foreach ($nids as $nid) {
        // Redo of flagging so we just call the ETM directly & query = way more performant.
        $isFlagged = $db
          ->select('flagging', 'f')
          ->fields('f', ['id'])
          ->condition('entity_type', 'node')
          ->condition('entity_id', $nid)
          ->condition('uid', $uid)
          ->execute()
          ->fetchField();
        $node = Node::load($nid);
        if ($node !== NULL && $isFlagged === FALSE) {
          $flagging = \Drupal::entityTypeManager()->getStorage('flagging')->create([
            'uid' => $this->entity->id(),
            'session_id' => NULL,
            'flag_id' => 'favorite',
            'entity_id' => $nid,
            'entity_type' => $node->getEntityTypeId(),
            'global' => 0,
          ]);

          $flagging->save();
        }

        // Add in any default links that are not in user_weights.
        $link = $node->get('field_link_type')->getValue();
        if (isset($link[0]['value'])) {
          $name = $link[0]['value'] . '_links';

          $check = $db
            ->select('user_weights', 'u')
            ->fields('u', ['entity_id'])
            ->condition('uid', $uid)
            ->condition('entity_id', $nid)
            ->condition('view_name', $name)
            ->execute()
            ->fetchField();

          if ($check === FALSE) {
            $results = TRUE;
            $weight++;
            $vals = array($nid, $uid, "'" . $name . "'", $weight);
            $insert .= '(' . implode(',', $vals) . '),';
          }
        }
      }

      // Insert new item in weights table.
      if ($results) {
        $insert = rtrim($insert, ',');
        $db->query($insert);
      }
    }

    return $this;
  }

  /**
   * @param User $user
   *
   * @return array
   */
  private function getUserDefaultFlags(User $user) {
    $groups = \Drupal::entityQuery('group')
      ->accessCheck(FALSE)
      ->exists('field_mapped_roles')
      ->execute();

    // First filter all the mapped groups to only ones this user has
    // Then run through those groups, grab default links and pull the node ids
    return collect($groups)->filter(function ($group) use ($user) {
      return \Drupal::service('dgreat_group.role_mapper')->userHasGroupRole($user, $group);
    })->flatMap(function ($gid) {
      $group = Group::load($gid);
      if (NULL !== $group &&
        $group->hasField('field_default_favorite_links')) {
        // Map over the default fav links and pull their targets.
        return collect(
          $group->get('field_default_favorite_links')->getValue()
        )->map(function ($default_link) {
          return $default_link['target_id'];
        })->toArray();
      }
      // fallback empty return
      return [];
    })->toArray();
  }

}
