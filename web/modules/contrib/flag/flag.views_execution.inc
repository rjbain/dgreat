<?php

/**
 * @file
 * Contains views API hooks in the views_execution group for Flag module.
 */

use \Drupal\views\ViewExecutable;

/**
 * Implements hook_views_query_substitutions().
 */
function flag_views_query_substitutions(ViewExecutable $view) {
  // Allow replacement of current user's session id so we can cache these
  // queries.
  $session_manager = \Drupal::service('session_manager');
  return [
    '***FLAG_CURRENT_USER_SID***' => $session_manager->getId(),
  ];
}
