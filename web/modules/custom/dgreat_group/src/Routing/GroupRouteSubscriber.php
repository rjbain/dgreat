<?php

namespace Drupal\dgreat_group\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alters group routes for site administrators.
 */
class GroupRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.group_relationship.collection')) {
      $route->setRequirement('_custom_access', '\\Drupal\\dgreat_group\\Access\\GroupAdministrationAccess::accessAllEntities');
      $requirements = $route->getRequirements();
      unset($requirements['_group_permission']);
      $route->setRequirements($requirements);
    }

    if ($route = $collection->get('view.group_members.page_1')) {
      $route->setOption('_admin_route', TRUE);
    }

    if ($route = $collection->get('entity.group_relationship.canonical')) {
      $route->setOption('_admin_route', TRUE);
    }
  }

}
