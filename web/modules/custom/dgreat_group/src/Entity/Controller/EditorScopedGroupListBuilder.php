<?php

namespace Drupal\dgreat_group\Entity\Controller;

use Drupal\group\Entity\Controller\GroupListBuilder;
use Drupal\group\Entity\GroupMembership;

/**
 * Limits the admin group listing for editors to groups they administer.
 */
class EditorScopedGroupListBuilder extends GroupListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    if (!in_array('editor', $this->currentUser->getRoles(), TRUE) || in_array('administrator', $this->currentUser->getRoles(), TRUE)) {
      return parent::getEntityIds();
    }

    $group_ids = [];
    foreach (GroupMembership::loadByUser($this->currentUser) as $membership) {
      $group_ids[] = (int) $membership->getGroup()->id();
    }

    if (!$group_ids) {
      return [];
    }

    $query = $this->getStorage()->getQuery();
    $query->condition('id', array_values(array_unique($group_ids)), 'IN');

    $header = $this->buildHeader();
    $query->tableSort($header);

    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->accessCheck()->execute();
  }

}
