<?php

namespace Drupal\dgreat_group;

use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;


class DgreatGroup {

  /**
   * @var object
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

        // Lets remove the existing content to prevent errors.
        $check = $group->getContentByEntityId($plugin_id, $this->entity->id());
        if (!empty($check)) {
          foreach ($check as $g) {
            $g->delete();
          }
        }

        // Add the content to the group.
        $group->addContent($this->entity, $plugin_id);
      }
    }


    // Fail safe return
    return FALSE;
  }

  /**
   * Adds a user to a group specified by a ER field on the user profile.
   *
   * @param $field
   *   The field we are using as a reference for the group.
   *
   * @return bool
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

    // Fail safe return
    return FALSE;
  }

  /**
   * Flags the Defaults for content per user.
   *
   * @param $field
   *   The field we are using as a reference for the group.
   *
   * @return bool
   */
  public function flagUserDefaultContent($field) {
    $ids = $this->entity->get($field)->getValue();
    $flag_service = \Drupal::service('flag');
    $flag = $flag_service->getFlagById('favorite');

    // Grabs each groups' default links ids.
    foreach ($ids as $gid) {
      if (isset($gid['target_id'])) {

        $group = Group::load($gid['target_id']);

        if ($group !== NULL) {
          if ($group->hasField('field_default_favorite_links')) {
            $gidz = $group->get('field_default_favorite_links')->getValue();
            foreach ($gidz as $gidd) {
              $nids[] = $gidd['target_id'];
            }
          }
          if ($group->hasField('field_default_quick_links')) {
            $gidz = $group->get('field_default_quick_links')->getValue();
            foreach ($gidz as $gidd) {
              $nids[] = $gidd['target_id'];
            }
          }
        }
      }
    }

    // Let's go through Each Node and flag each node.
    if (!empty($nids)) {
      foreach ($nids as $nid) {
        $node = Node::load($nid);
        $check = $flag_service->getFlagging($flag, $node, $this->entity);
        $is_flagged = $flag->isFlagged($node, $this->entity);

        // Check to remove flags when resaving users.
        if ($is_flagged && $check !== NULL) {
          $flag_service->unflag($flag, $node, $this->entity);
        }
        if (!$is_flagged) {
          $flag_service->flag($flag, $node, $this->entity);
        }
      }
    }

    // Fail safe return
    return FALSE;
  }
}