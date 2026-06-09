<?php

declare(strict_types=1);

namespace Drupal\sitewide_alert\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the sitewide alerts.
 *
 * @ingroup sitewide_alert
 */
class SitewideAlertSettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId(): string {
    return 'sitewidealert_settings';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Empty implementation of the abstract submit class.
  }

  /**
   * Defines the settings form for Sitewide Alert entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['sitewidealert_settings']['#markup'] = 'Settings form for Sitewide Alert entities. Manage field settings here.';
    return $form;
  }

}
