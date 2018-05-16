<?php

namespace Drupal\config_import_de\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Configuration Import - Delete Entities module.
 */
class ConfigImportDeSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_import_de_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'config_import_de.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('config_import_de.config');

    $form['delete_detected_entities'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete detected entities'),
      '#default_value' => $config->get('delete_detected_entities'),
    ];

    $form['debug_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('turn on debug mode'),
      '#description' => $this->t("This will output the ID's and types of the entities detected"),
      '#default_value' => $config->get('debug_mode'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('config_import_de.config');

    $config->set('delete_detected_entities', $form_state->getValue('delete_detected_entities'));
    $config->set('debug_mode', $form_state->getValue('debug_mode'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
