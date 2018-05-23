<?php
namespace Drupal\usf_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
// Change following https://www.drupal.org/node/2457593
// See https://www.drupal.org/node/2549395 for deprecate methods information
// use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Html;
// use Html instead SAfeMarkup




class USFDashboardController extends ControllerBase {

  public function dashboard() {
    // Default settings.
    $config = \Drupal::config('usf_dashboard.settings');
    // Page title and source text.
    $page_title = $config->get('usf_dashboard.page_title');
    $block_text = $config->get('usf_dashboard.block_text');

      //$element['#title'] = SafeMarkup::checkPlain($page_title);
      $element['#title'] = Html::escape($page_title);

      // Theme function.
      $element['#theme'] = 'usf_dashboard';

      return $element;
    }

}
