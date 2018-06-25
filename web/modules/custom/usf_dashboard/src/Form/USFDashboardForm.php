<?php

namespace Drupal\usf_dashboard\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 */
class USFDashboardForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'usf_dashboard_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('usf_dashboard.settings');

    // Page title field.
    $form['page_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('USF Dashboard page title:'),
      '#default_value' => $config->get('usf_dashboard.page_title'),
      '#description' => $this->t('Give it a title.'),
    ];
    // Source text field.
    $form['block_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Block text for the dashboard block:'),
      '#default_value' => $config->get('usf_dashboard.block_text'),
      '#description' => $this->t('Enter something.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('usf_dashboard.settings');
    $config->set('usf_dashboard.block_text', $form_state->getValue('block_text'));
    $config->set('usf_dashboard.page_title', $form_state->getValue('page_title'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'usf_dashboard.settings',
    ];
  }

}
