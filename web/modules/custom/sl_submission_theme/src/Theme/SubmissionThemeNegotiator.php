<?php

namespace Drupal\sl_submission_theme\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Forces the site theme on Submission add/edit forms.
 */
class SubmissionThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * Machine name of the target content type.
   * Update this if your bundle machine name is different.
   */
  const BUNDLE = 'submission';

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) : bool {
    $route_name = $route_match->getRouteName();

    // Edit form: reliable, we have the node.
    if ($route_name === 'entity.node.edit_form') {
      $node = $route_match->getParameter('node');
      return $node instanceof NodeInterface && $node->bundle() === self::BUNDLE;
    }

    // Add form: handle both core route names and flaky params.
    if ($route_name === 'node.add' || $route_name === 'entity.node.add_form') {
      $bundle = $route_match->getParameter('node_type');

      // node_type may be a string or a NodeTypeInterface.
      if ($bundle instanceof NodeTypeInterface) {
        $bundle = $bundle->id();
      }

      // If bundle couldn't be read from parameters, fall back to the path.
      if (empty($bundle)) {
        $current_path = \Drupal::service('path.current')->getPath(); // e.g. /node/add/submission
        if (preg_match('@^/node/add/([^/]+)@', $current_path, $m)) {
          $bundle = $m[1];
        }
      }

      return $bundle === self::BUNDLE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) : string {
    // Your custom (front) theme machine name:
    return 'myusf';
  }

}
