<?php

namespace Drupal\dgreat_student_surveys\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Entity;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class SurveyResponseController.
 */
class SurveyResponseController extends ControllerBase {

  /**
   * Index.
   *
   * @return string
   *   Return Hello string.
   */
  public function index() {
    if (\Drupal::request()->query->get('api-key') !== getenv('SURVEY_API_KEY')) {
      return new JsonResponse('Not Authorized', 403);
    }
    $surveys = \Drupal::entityQuery('webform')
           ->condition('category', 'student_survey', '=')
           ->execute();

    $data = $this->buildResponse($surveys);

    return new JsonResponse($data);
  }

  private function buildResponse(array $surveys) {
      return array_map(function ($id) {
        $survey = Webform::load($id);
        return [
          'name' => $survey->id(),
          'fields' => $this->prepareFields($survey->getElementsDecoded()),
          'responses' => $this->loadResponses($survey)
        ];
      },$surveys);
  }

  private function prepareFields($getElementsDecoded) {
    return $getElementsDecoded;
  }

  private function loadResponses($survey) {
    $ids = \Drupal::entityQuery('webform_submission')
      ->condition('webform_id', $survey->id())
      ->execute();

    $webforms = WebformSubmission::loadMultiple(array_keys($ids));
    return array_map(function($webform) {
      return $webform->getData();
    }, $webforms);
  }
}
