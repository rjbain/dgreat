<?php

/**
 * Implements hook_views_data_alter().
 */
function rate_vote_summary_views_data_alter(array &$data) {
  // Attach our custom field to node base table (adjust if your View uses another base).
  if (isset($data['node_field_data'])) {
    $data['node_field_data']['rate_vote_summary'] = [
      'title' => t('Rate vote summary'),
      'help' => t('Shows up, down, and current user vote for the node.'),
      'field' => [
        'id' => 'rate_vote_summary_field', // matches @ViewsField plugin ID below
      ],
    ];
  }
}
