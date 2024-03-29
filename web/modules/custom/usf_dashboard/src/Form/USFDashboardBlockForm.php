<?php

namespace Drupal\usf_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * USF Dashboard block form.
 */
class USFDashboardBlockForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'usf_dashboard_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // How many paragraphs?
    // $options = new array();
    $options = array_combine(range(1, 10), range(1, 10));
    $form['paragraphs'] = [
      '#type' => 'select',
      '#title' => $this->t('Paragraphs'),
      '#options' => $options,
      '#default_value' => 4,
      '#description' => $this->t('How many?'),
    ];

    // How many phrases?
    $form['phrases'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phrases'),
      '#default_value' => '20',
      '#description' => $this->t('Maximum per paragraph'),
    ];

    // Submit.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $phrases = $form_state->getValue('phrases');
    if (!is_numeric($phrases)) {
      $form_state->setErrorByName('phrases', $this->t('Please use a number.'));
    }

    if (floor($phrases) != $phrases) {
      $form_state->setErrorByName('phrases', $this->t('No decimals, please.'));
    }

    if ($phrases < 1) {
      $form_state->setErrorByName('phrases', $this->t('Please use a number greater than zero.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect(
      'usf_dashboard.generate',
      [
        'paragraphs' => $form_state->getValue('paragraphs'),
        'phrases' => $form_state->getValue('phrases'),
      ]
    );
  }

}
