<?php

/**
 * @file
 * Provide Views data for weight fields.
 */

/**
 * Implements hook_field_views_data().
 */
function weight_field_views_data(\Drupal\field\FieldStorageConfigInterface $field_storage) {
  $data = views_field_default_views_data($field_storage);
  $field_name = $field_storage->getName();

  foreach ($data as $table_name => &$table_data) {
    if (isset($table_data[$field_name])) {
      $table_data[$field_name . '_value']['field'] = $table_data[$field_name]['field'];
      $title = t('@title Selector (@field)', array(
        '@title' => $table_data[$field_name]['title'],
        '@field' => $field_name,
      ));
      $short_title = t('@title Selector', array('@title' => $table_data[$field_name]['title']));
      $table_data[$field_name]['title'] = $title;
      $table_data[$field_name]['title short'] = $short_title;
      $table_data[$field_name]['field']['id'] = 'weight_selector';
    }
  }

  return $data;
}
