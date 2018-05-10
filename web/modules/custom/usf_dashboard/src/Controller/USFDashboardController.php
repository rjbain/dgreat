<?php
namespace Drupal\usf_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;





class USFDashboardController extends ControllerBase {


  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function dashboard() {

    return [
      '#theme' => 'usf_dashboard',
    ];

  }
}