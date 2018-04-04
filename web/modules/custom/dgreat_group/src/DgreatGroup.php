<?php

namespace Drupal\dgreat_group;

use Drupal\group\Entity\Group;

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

}