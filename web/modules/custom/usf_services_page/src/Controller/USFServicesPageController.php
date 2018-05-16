<?php
namespace Drupal\usf_services_page\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\Role;


/**
 * Provides route responses for the Example module.
 */
class USFServicesPageController extends ControllerBase {

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function showServices() {
    $role_objects[] = Role::loadMultiple();

    return [
      '#theme' => 'usf_services_page',
      '#test_var' => $role_objects,
      '#my_user' => $role_objects,
    ];
  }
}