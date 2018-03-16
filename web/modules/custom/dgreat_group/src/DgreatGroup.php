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
    $group_id = $entity->get($field)->getValue();

    // If it is assigned, then lets do the magix.
    if (isset($group_id[0]['target_id'])) {
      $group = Group::load($group_id[0]['target_id']);

      $check = $group->getContentByEntityId($plugin_id, $entity->id());
      // Lets remove the existing content to prevent errors.
      if (!empty($check)) {
        $check->delete();
      }
      // Add the content to the group.
      $group->addContent($entity, $plugin_id);

      return TRUE;
    }

    // Fail safe return
    return FALSE;
  }

}