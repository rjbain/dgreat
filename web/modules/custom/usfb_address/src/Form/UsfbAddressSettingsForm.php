<?php

/**
 * @file
 * Contains \Drupal\usfb_address\Form\UsfbAddressSettingsForm.
 */

namespace Drupal\usfb_address\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Datetime\Time;
use Drupal\Core\Datetime\DrupalDateTime;

class UsfbAddressSettingsForm extends ConfigFormBase {

  /**
   * The Drupal state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * Constructs a new UpdateManagerUpdate object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Component\Datetime\Time $time
   *   The time service.
   */
  public function __construct(StateInterface $state, Time $time) {
    $this->state = $state;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('datetime.time')
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'usfb_address_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['usfb_address.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Setup default vars, etc.
    $default = $this->time->getRequestTime();
    $get_start = $this->state->get('usfb_address_date_start');
    $start = $get_start !== NULL ? $get_start : $default;
    $end_start = $this->state->get('usfb_address_date_end');
    $end = $end_start !== NULL ? $end_start : $default;

    // Toggle checkbox to see if the service should be enabled.
    $form['usfb_address_enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enabled'),
      '#default_value' => $this->state->get('usfb_address_enabled', TRUE),
      '#description' => t('Toggle the USFB Address service. When enabled, users will be notified on login to update their addresses.'),
    ];
    // Provide a Date Select widget, from the Date API module.
    $form['usfb_address_date_start'] = [
      '#type' => 'datelist',
      '#title' => t('Start Date'),
      '#default_value' => DrupalDateTime::createFromTimestamp($start),
      '#date_part_order' => ['month', 'day', 'year'],
      '#date_year_range' => '-1:+2',
    ];

    // Provide a Date Select widget, from the Date API module.
    $form['usfb_address_date_end'] = [
      '#type' => 'datelist',
      '#title' => t('End Date'),
      '#default_value' => DrupalDateTime::createFromTimestamp($end),
      '#date_part_order' => ['month', 'day', 'year'],
      '#date_year_range' => '-1:+2',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the values to the state.
    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'usfb_address_') !== FALSE) {
        if (strpos($key, 'usfb_address_date_') !== FALSE) {
          $value = $value->getTimeStamp();
        }
        $this->state->set($key, $value);
      }
    }

    parent::submitForm($form, $form_state);
  }

}