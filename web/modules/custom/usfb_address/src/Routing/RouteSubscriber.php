<?php

namespace Drupal\usfb_address\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

    /**
     * {@inheritdoc}
     */
    protected function alterRoutes(RouteCollection $collection) {
        // Define custom access for '/user/{user}'
        if ($route = $collection->get('entity.user.canonical')) {
            $route->setRequirement('_custom_access', '\Drupal\usfb_address\Access\YourCustomAccessCheck::access');
        }
        if ($route = $collection->get('usfb_address.address_form')) {
            $route->setRequirement('_custom_access', '\Drupal\usfb_address\Access\YourCustomAccessCheck::access');
        }
    }

}