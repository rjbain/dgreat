<?php

namespace Drupal\dgreat_student_surveys\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Class StudentSurveySettingsForm.
 */
class StudentSurveySettingsForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'student_survey_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['current_student_xml_file'] = [
      '#type' => 'managed_file',
      '#name' => 'current_student_xml_file',
      '#title' => $this->t('Current Student XML File'),
      '#description' => $this->t('An XML File from Banner that indicates which students should see forms'),
      '#upload_validators' => [
        'file_validate_extensions' => ['xml']
      ],
      '#upload_location' => 'private://student_surveys/',
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('current_student_xml_file') == NULL) {
      $form_state->setErrorByName('current_student_xml_file', $this->t('File.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fid = $form_state->getValue('current_student_xml_file');
    if (!empty($fid)) {
      $file = File::load($fid);
      if (null !== $file) {
        $file->setPermanent();
        $file->save();
      }
    }

    \Drupal::service('messenger')->addMessage('Current Student File Updated');

  }

}
