<?php
namespace Drupal\usfb_address;

class UsfbAddressSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'usfb_address_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('usfb_address.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['usfb_address.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $format = 'Y-m-d';
    $default = format_date(REQUEST_TIME, 'custom', $format);

    // Toggle checkbox to see if the service should be enabled.
    $form['usfb_address_enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enabled'),
      '#default_value' => variable_get('usfb_address_enabled', TRUE),
      '#description' => t('Toggle the USFB Address service. When enabled, users will be notified on login to update their addresses.'),
    ];
    // Provide a Date Select widget, from the Date API module.
    $form['usfb_address_date_start'] = [
      '#type' => 'date_select',
      '#title' => t('Start Date'),
      '#default_value' => variable_get('usfb_address_date_start', $default),
      '#date_format' => $format,
      '#date_year_range' => '-5:+5',
    ];
    // Provide a Date Select widget, from the Date API module.
    $form['usfb_address_date_end'] = [
      '#type' => 'date_select',
      '#title' => t('End Date'),
      '#default_value' => variable_get('usfb_address_date_end', $default),
      '#date_format' => $format,
      '#date_year_range' => '-5:+5',
    ];
    return parent::buildForm($form, $form_state);
  }

}
