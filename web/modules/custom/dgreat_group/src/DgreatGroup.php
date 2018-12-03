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
        $check = $group->getContentByEntityId($plugin_id, $this->entity->id());
        if (!empty($check)) {
          continue;
        }

        // Add the content to the group.
        $group->addContent($this->entity, $plugin_id);
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
   * Adds the groups to the quick links on creation.
   *
   * @return bool
   */
  public function addQuickLinkGroups() {
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
      $this->entity->save();

      // Flag the content.
      $flag_service = \Drupal::service('flag');
      $flag = $flag_service->getFlagById('favorite');
      $node = Node::load($this->entity->id());
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

      return TRUE;
    }

    return FALSE;
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
      collect($nids)->map(function($nid) {
//        $startTime = microtime(true);
//
//        $flag_service = \Drupal::service('flag');
//        $flag = $flag_service->getFlagById('favorite');
//
//        $endTime = microtime(true);
//        $elapsed = $endTime - $startTime;
//        $msg = "Execution time : $elapsed seconds";
//        \Drupal::logger('1 - Flag Service')->notice($msg);
//
//        $startTime = $endTime = 0;
//
//        $startTime = microtime(true);
//
//        $node = Node::load($nid);
//        if ($node !== NULL && !$flag->isFlagged($node, $this->entity)) {
//          $flag_service->flag($flag, $node, $this->entity);
//        }
//
//        $endTime = microtime(true);
//        $elapsed = $endTime - $startTime;
//        $msg = "Execution time : $elapsed seconds";
//        \Drupal::logger('2 - Flagging')->notice($msg);
//
//        $startTime = $endTime = 0;

        $startTime = microtime(true);

        $node = Node::load($nid);
        if ($node !== NULL) {
          $flagging = \Drupal::entityTypeManager()->getStorage('flagging')->create([
            'uid' => $this->entity->id(),
            'session_id' => NULL,
            'flag_id' => 'favorite',
            'entity_id' => $node->id(),
            'entity_type' => $node->getEntityTypeId(),
            'global' => 0,
          ]);

          $flagging->save();
        }
        
        $endTime = microtime(true);
        $elapsed = $endTime - $startTime;
        $msg = "Execution time : $elapsed seconds";
        \Drupal::logger('2.5 - Flagging')->notice($msg);

        $startTime = $endTime = 0;

        $startTime = microtime(true);

        // Add in any default links that are not in user_weights.
        $link = $node->get('field_link_type')->getValue();
        if (isset($link[0]['value'])) {
          $db = \Drupal::database();
          $name = $link[0]['value'] . '_links';
          $uid = $this->entity->id();
          $nid = $node->id();

          $check = $db
            ->select('user_weights', 'u')
            ->fields('u', ['entity_id'])
            ->condition('uid', $uid)
            ->condition('entity_id', $nid)
            ->condition('view_name', $name)
            ->execute()
            ->fetchField();

          if ($check === FALSE) {
            // Grab the new weight.
            $sql = "SELECT MAX(weight) FROM {user_weights} WHERE uid = :uid";
            $weight = $db
              ->query($sql, [':uid' => $uid])
              ->fetchField();

            // No user weights setup, add a default one.
            if ($weight == NULL) {
              $weight = 0;
            }

            // Insert new item in weights table.
            $db->insert('user_weights')
              ->fields([
                'entity_id' => $nid,
                'uid' => $uid,
                'view_name' => $name,
                'weight' => $weight + 1,
              ])
              ->execute();

          }
        }
        $endTime = microtime(true);
        $elapsed = $endTime - $startTime;
        $msg = "Execution time : $elapsed seconds";
        \Drupal::logger('3 - Weights')->notice($msg);

      });
    }
    return $this;
  }

  /**
   * @param \Drupal\user\Entity\User $user
   *
   * @return array
   */
  private function getUserDefaultFlags(User $user) {
    $groups = \Drupal::entityQuery('group')
      ->exists('field_mapped_roles')
      ->execute();

    // First filter all the mapped groups to only ones this user has
    // Then run through those groups, grab default links and pull the node ids
    return collect($groups)->filter(function ($group) use ($user) {
      return RoleGroupMapper::userHasGroupRole($user, $group);
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
