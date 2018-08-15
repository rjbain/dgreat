<?php

namespace Drupal\dgreat_student_surveys\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\WebformRating;

/**
 * Provides a 'rating' element.
 *
 * @WebformElement(
 *   id = "student_survey_rating_element",
 *   label = @Translation("Student Survey Rating"),
 *   description = @Translation("Provides a form element to rate something using an attractive voting widget."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class StudentSurveyRatingElement extends WebformRating {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['element']['salesforce_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Salesforce ID'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
        'salesforce_id' => ''
      ];
  }

}
