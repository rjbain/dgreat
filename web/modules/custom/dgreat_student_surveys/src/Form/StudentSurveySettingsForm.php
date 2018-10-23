<?php

namespace Drupal\dgreat_student_surveys\Form;

use Drupal\Core\Database\Database;
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
        'file_validate_extensions' => ['xml'],
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
      $form_state->setErrorByName('current_student_xml_file',
        $this->t('File.'));
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fids = $form_state->getValue('current_student_xml_file');
    if (!empty($fids)) {
      // We only allow a single upload.
      $file = File::load($fids[0]);
      if (NULL !== $file) {
        $data = simplexml_load_string(file_get_contents($file->getFileUri()));

        $conn = Database::getConnection();

        // Truncate the table.
        $conn->truncate('current_survey_students')->execute();

        // Loop over XML nodes and add user names to database.
        foreach ($data->DATA_RECORD as $record) {
          try {
            $conn->insert('current_survey_students')->fields([
              'username' => $record->USERNAME,
            ])->execute();
          }
          catch (\Exception $e) {
            \Drupal::logger('dgreat_student_surveys')
              ->error('Error in update of current_survey_students table');
          }
        }
      }
    }
    \Drupal::service('messenger')->addMessage('Current Student File Updated');
  }

}
