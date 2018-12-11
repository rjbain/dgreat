<?php

/**
 * @file
 * Provides the administration interface for USFB_Address.
 */

/**
 * Form callback; Create a settings form for USF-B.
 */
function usfb_address_settings_form($form, &$form_state) {
  $format = 'Y-m-d';
  $default = format_date(REQUEST_TIME, 'custom', $format);

  // Toggle checkbox to see if the service should be enabled.
  $form['usfb_address_enabled'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enabled'),
    '#default_value' => \Drupal::config('usfb_address.settings')->get('usfb_address_enabled'),
    '#description' => t('Toggle the USFB Address service. When enabled, users will be notified on login to update their addresses.'),
  );
  // Provide a Date Select widget, from the Date API module.
  // @FIXME
// Could not extract the default value because it is either indeterminate, or
// not scalar. You'll need to provide a default value in
// config/install/usfb_address.settings.yml and config/schema/usfb_address.schema.yml.
$form['usfb_address_date_start'] = array(
    '#type' => 'date_select',
    '#title' => t('Start Date'),
    '#default_value' => \Drupal::config('usfb_address.settings')->get('usfb_address_date_start'),
    '#date_format' => $format,
    '#date_year_range' => '-5:+5',
  );
  // Provide a Date Select widget, from the Date API module.
  // @FIXME
// Could not extract the default value because it is either indeterminate, or
// not scalar. You'll need to provide a default value in
// config/install/usfb_address.settings.yml and config/schema/usfb_address.schema.yml.
$form['usfb_address_date_end'] = array(
    '#type' => 'date_select',
    '#title' => t('End Date'),
    '#default_value' => \Drupal::config('usfb_address.settings')->get('usfb_address_date_end'),
    '#date_format' => $format,
    '#date_year_range' => '-5:+5',
  );
  return system_settings_form($form);
}
