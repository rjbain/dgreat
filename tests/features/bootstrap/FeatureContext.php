<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Testwork\Tester\Result\TestResult;
use Drupal\DrupalExtension\Context\MinkContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Hook\Scope\AfterStepScope;

use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Define application features from the specific context.
 */
class FeatureContext extends RawDrupalContext implements Context, SnippetAcceptingContext {

  /**
   * Initializes context.
   * Every scenario gets its own context object.
   *
   * @param array $parameters
   *   Context parameters (set them in behat.yml)
   */
  public function __construct(array $parameters = []) {
    // Initialize your context here
  }


  /** @var \Drupal\DrupalExtension\Context\MinkContext */
  private $minkContext;
  /** @BeforeScenario */
  public function gatherContexts(BeforeScenarioScope $scope)
  {
      $environment = $scope->getEnvironment();
      $this->minkContext = $environment->getContext('Drupal\DrupalExtension\Context\MinkContext');
  }

  /**
   * @Given I create a webform called :arg1 from the :arg2 template
   */
  public function iCreateAWebformCalledFromTheTemplate($arg1, $arg2)
  {
    $this->visitPath("/admin/structure/webform/manage/template_{$arg2}/duplicate");
    $this->minkContext->fillField('title', $arg1);
    $this->minkContext->selectOption('category[select]', 'student_survey');
    $this->minkContext->assertSelectRadioById('Open', 'edit-status-open');
    $this->minkContext->pressButton('Save');
    $this->getSession()->getScreenshot();
  }

  /**
   * @Given I set the :arg1 survey salesforce_id to :arg2
   */
  public function iSetTheSurveySalesforceIdTo2($arg1, $arg2)
  {
    $this->visitPath("/admin/structure/webform/manage/$arg1/settings");
    $this->minkContext->pressButton('Expand all');
    $this->minkContext->iWaitForAjaxToFinish();

    $this->minkContext->fillField(
      'The campaign id for this survey in Salesforce',
      $arg2
    );
    $this->minkContext->assertButton('Save');
  }

  /**
   * @Given I add a student survey rating to the :arg1 survey with a salesforce_id of :arg2
   */
  public function iAddAStudentSurveyRatingToTheSurveyWithASalesforceIdOf($arg1, $arg2)
  {
    $this->visitPath("/admin/structure/webform/manage/$arg1");
    $this->minkContext->assertClick('Add element');
    $this->minkContext->iWaitForAjaxToFinish();
    $this->clickDrupalData('edit-elements-student-survey-rating-element-operation');
    $this->minkContext->iWaitForAjaxToFinish();
    $this->minkContext->fillField('title', 'How Awesome is Dustin?');
    $this->minkContext->fillField('Salesforce ID', '12345');
    $this->minkContext->assertButton('Save');
    $this->minkContext->iWaitForAjaxToFinish();
    $this->minkContext->assertButton('Save elements');
  }

  /**
   * @When /^(?:|I )click drupal data selector "(?P<selector>\w+)"
   */
  public function clickDrupalData($drupal_selector) {
    $page          = $this->getSession()->getPage();
    $element = $page->find('xpath', '//*[@data-drupal-selector="' . $drupal_selector . '"]');

    $element->click();
  }
}
