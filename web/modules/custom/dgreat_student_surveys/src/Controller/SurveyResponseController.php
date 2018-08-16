<?php

namespace Drupal\dgreat_student_surveys\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class SurveyResponseController.
 */
class SurveyResponseController extends ControllerBase {

  /**
   * Fetch all the responses for a given salesforce id
   *
   * @param $salesforce_id
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function show($salesforce_id) {
    if (\Drupal::request()->query->get('api-key') !== getenv('SURVEY_API_KEY')) {
      return new JsonResponse('Not Authorized', 403);
    }
    $surveys = \Drupal::entityQuery('webform')
                      ->condition('category', 'student_survey', '=')
                      ->execute();
    return new JsonResponse($this->buildResponse($surveys, $salesforce_id));
  }

  /**
   * Format a survey question response
   *
   * @param array $surveys
   * @param $salesforce_id
   *
   * @return array
   */
  private function buildResponse(array $surveys, $salesforce_id) {
    return $this->filterSurveysByQuestion($surveys, $salesforce_id)
                ->map(function ($survey, $key) use ($salesforce_id) {
                  $submissions = $this->getSubmissionsBySurvey($key);
                  $question = $this->getQuestion($survey, $salesforce_id);
                  return [
                    'question' => $question,
                    'submissions' => $submissions,
                  ];
                })
                ->flatMap(function ($row) {
                  return $this->buildDataForRow($row);
                })->all();


  }

  /**
   * Fetch all the surveys that contain a question by it's Salesforce ID.
   * @param array $surveys
   * @param $salesforce_id
   *
   * @return \Illuminate\Support\Collection|\Tightenco\Collect\Support\Collection
   */
  private function filterSurveysByQuestion(array $surveys, $salesforce_id) {
    return collect($surveys)->map(function ($id) {
      return Webform::load($id);
    })->filter(
      function ($webform) use ($salesforce_id) {
        return collect($webform->getElementsDecoded())->filter(
            function ($element) use ($salesforce_id) {
              return isset($element['#salesforce_id']) &&
                (string) $element['#salesforce_id'] === (string) $salesforce_id;
            })->count() >= 1;
      });
  }

  /**
   * Fetch the submissions for a survey.
   *
   * @param $relevant_survey
   *
   * @return array|int
   */
  private function getSubmissionsBySurvey($relevant_survey) {
    return \Drupal::entityQuery('webform_submission')
                  ->condition('webform_id', $relevant_survey)
                  ->execute();
  }

  /**
   * Flatten the question elements and pluck out a question name by id
   *
   * @param $relevant_survey
   * @param $salesforce_id
   *
   * @return array
   */
  private function getQuestion($relevant_survey, $salesforce_id) {
    return collect($relevant_survey->getElementsDecoded())->map(function (
      $element,
      $key
    ) {
      return ['name' => $key] + $element;
    })->firstWhere('#salesforce_id', $salesforce_id);
  }

  /**
   * Do some data formatting to return a nice terse result.
   * @param $row
   *
   * @return array
   */
  private function buildDataForRow($row) {
    $question = $row['question'];
    return collect($row['submissions'])->map(function ($id) use ($question) {
      /** @var WebformSubmission $submission */
      $submission = WebformSubmission::load($id);
      return [
        'user' => $submission->getOwner()->getAccountName(),
        'answer' => $submission->getElementData($question['name']),
      ];
    })->all();
  }

}
