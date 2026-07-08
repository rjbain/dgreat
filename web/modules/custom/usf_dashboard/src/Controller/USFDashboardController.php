<?php

namespace Drupal\usf_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Html;

/**
 * Returns the dashboard page.
 */
class USFDashboardController extends ControllerBase {

  /**
   * Builds the dashboard page render array.
   */
  public function dashboard(): array {
    // Default settings.
    $config = \Drupal::config('usf_dashboard.settings');
    // Page title and source text.
    $page_title = $config->get('usf_dashboard.page_title');

    $element['#title'] = Html::escape($page_title);
    $element['#theme'] = 'usf_dashboard';

    return $element;
  }

}
