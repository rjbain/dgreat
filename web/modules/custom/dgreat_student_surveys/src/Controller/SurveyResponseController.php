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
   * Fetch all the responses for a given salesforce id.
   *
   * @param $salesforce_id
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function index() {
    if (\Drupal::request()->headers->get('x-api-key') !== getenv('SURVEY_API_KEY')) {
      return new JsonResponse('Not Authorized', 403);
    }
    $from_date = strtotime(\Drupal::request()->query->get('FromDate'));
    if (FALSE === $from_date) {
      return new JsonResponse('Please provide a valid FromDate', 400);
    }

    $surveys = \Drupal::entityQuery('webform')
      ->condition('category', 'student_survey', '=')
      ->execute();
    $responses = \Drupal::entityQuery('webform_submission')
      ->condition('webform_id', $surveys, 'IN')
      ->condition('created', $from_date, '>')
      ->execute();
    return new JsonResponse($this->buildResponse($surveys, $responses), 200);
  }

  /**
   * Format a survey question response.
   *
   * @param array $surveys A collection of surveys to build results for.
   *
   * @param array $responses A collection of responses to build results for.
   *
   * @return array The formatted results.
   */
  private function buildResponse(array $surveys = [], array $responses = []) {
    return collect($surveys)->flatMap(function ($survey) use ($responses) {
      $webform = Webform::load($survey);
      $questions = collect($webform->getElementsDecoded())->map(function ($data, $name) {
        $obj = new \stdClass();
        $obj->salesforce_id = $data['#salesforce_id'];
        $obj->question = $name;
        return $obj;
      })->flatten();
      return collect($responses)->flatMap(function ($response) use ($questions) {
        $submission = WebformSubmission::load($response);
        return $questions->map(function ($question) use ($submission) {
          $obj = new \stdClass();
          $obj->user = $submission->getOwner()->getUsername();
          $obj->answer = $submission->getElementData($question->question);
          $obj->campaignID = $question->salesforce_id;
          $obj->dateTaken = date("m-d-Y-H.i.s", $submission->getCreatedTime());
          return $obj;
        });
      });
    })->filter(function ($row) {
      return !empty($row->answer);
    })->unique()->flatten();
  }

  /**
   * Fetch all the surveys that contain a question by it's Salesforce ID.
   *
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
   * Flatten the question elements and pluck out a question name by id.
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
   *
   * @param $row
   *
   * @return array
   */
  private function buildDataForRow($row) {
    $question = $row['question'];
    return collect($row['submissions'])->map(function ($id) use ($question) {
      /** @var \Drupal\webform\Entity\WebformSubmission $submission */
      $submission = WebformSubmission::load($id);
      return [
        'user' => $submission->getOwner()->getAccountName(),
        'answer' => $submission->getElementData($question['name']),
      ];
    })->all();
  }

}
