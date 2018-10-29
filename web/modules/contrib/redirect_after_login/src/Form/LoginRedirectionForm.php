<?php

/**

 * @file

 * Contains \Drupal\redirect_after_login\Form\LoginRedirectionForm.

 */

namespace Drupal\redirect_after_login\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class LoginRedirectionForm extends ConfigFormBase {

  public $allUser = [];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'login_redirection_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $savedPathRoles = \Drupal::config('redirect_after_login.settings')->get('login_redirection');
    $this->allUser = user_role_names();
    $form['roles'] = array(
        '#type' => 'fieldset',
        '#title' => t('All roles'),
    );
    foreach ($this->allUser as $user => $name) {
      if ($user != "anonymous") {
        $form['roles'][$user] = [
            '#type' => 'textfield',
            '#title' => t($name),
            '#size' => 60,
            '#maxlength' => 128,
            '#description' => t('Add a valid url or &ltfront> for main page'),
            '#required' => TRUE,
            '#default_value' => $savedPathRoles[$user],
        ];
      }
    }
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#button_type' => 'primary',
    ];
    // Disable caching
    $form['#cache']['max-age'] = 0;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $path = '';
    foreach ($this->allUser as $user => $name) {
      if ($user == "anonymous") {
        continue;
      }
      if (!(preg_match('/^[#?\/]+/', $form_state->getValue($user)) || $form_state->getValue($user) == '<front>' )) {
        $form_state->setErrorByName($user, t('This URL %url is not valid for role %role.', array('%url' => $form_state->getValue($user), '%role' => $name)));
      }
      $path = $form_state->getValue($user);
      $is_valid = \Drupal::service('path.validator')->isValid($path);
      if ($is_valid == NULL) {
        $form_state->setErrorByName($user, t('Path does not exists.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $loginUrls = [];
    foreach ($this->allUser as $user => $name) {
      if ($form_state->getValue($user) == '<front>') {
        $loginUrls[$user] = '/';
      } else {
        $loginUrls[$user] = $form_state->getValue($user);
        $form_state->getValue($user);
      }
    }
    $this->config('redirect_after_login.settings')->set('login_redirection', $loginUrls)->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get Editable config names.
   *
   * @inheritDoc
   */
  protected function getEditableConfigNames() {
    return ['redirect_after_login.settings'];
  }

}
