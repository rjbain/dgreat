<?php

namespace Drupal\dgreat_group\Entity\Controller;

use Drupal;
use Drupal\group\Entity\Controller\GroupRelationshipListBuilder;

/**
 * Shows all related entities to site administrators on the group content page.
 */
class AdministratorGroupRelationshipListBuilder extends GroupRelationshipListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery();
    $query->sort($this->entityType->getKey('id'));
    $query->condition('gid', $this->group->id());

    if ($this->limit) {
      $query->pager($this->limit);
    }

    if (in_array('administrator', Drupal::currentUser()->getRoles(), TRUE)) {
      return $query->accessCheck(FALSE)->execute();
    }

    return $query->accessCheck()->execute();
  }

}
