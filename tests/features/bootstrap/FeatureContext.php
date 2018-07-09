<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\DrupalExtension\Context\MinkContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Hook\Scope\AfterStepScope;

use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Define application features from the specific context.
 */
class FeatureContext extends RawDrupalContext implements Context, SnippetAcceptingContext {

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
  }

  /**
   * @Given I set the :arg1 survey salesforce_id to :arg2
   */
  public function iSetTheSurveySalesforceIdTo2($arg1, $arg2)
  {
    $this->visitPath("/admin/structure/webform/manage/$arg1/settings");
    $this->minkContext->pressButton('Expand all');
    $this->minkContext->iWaitForAjaxToFinish(10000);

    $this->minkContext->fillField(
      'The campaign id for this survey in Salesforce',
      $arg2
    );
    $this->minkContext->pressButton('Save');
    $this->minkContext->iWaitForAjaxToFinish(10000);
  }

  /**
   * @Given I add a student survey rating to the :arg1 survey with a salesforce_id of :arg2
   */
  public function iAddAStudentSurveyRatingToTheSurveyWithASalesforceIdOf($arg1, $arg2)
  {
    $this->visitPath("/admin/structure/webform/manage/$arg1");
    $this->minkContext->clickLink('Add element');
    $this->minkContext->iWaitForAjaxToFinish(10000);
    $this->clickDrupalData('edit-elements-student-survey-rating-element-operation');
    $this->minkContext->iWaitForAjaxToFinish(10000);
    $this->minkContext->fillField('title', 'How Awesome is Dustin?');
    $this->minkContext->fillField('Salesforce ID', $arg2);
    $this->minkContext->pressButton('Save');
    $this->minkContext->iWaitForAjaxToFinish(10000);
    $this->minkContext->fillField('key', 'how_awesome_dustin');
    $this->minkContext->pressButton('Save');
    $this->minkContext->iWaitForAjaxToFinish(10000);
    $this->minkContext->pressButton('Save elements');
    $this->minkContext->iWaitForAjaxToFinish(10000);
  }

    /**
     * @Given I follow meta refresh
     *
     * https://www.drupal.org/node/2011390
     */
    public function iFollowMetaRefresh() {
      while ($refresh = $this->getSession()->getPage()->find('css', 'meta[http-equiv="Refresh"]')) {
        $content = $refresh->getAttribute('content');
        $url = str_replace('0; URL=', '', $content);
        $this->getSession()->visit($url);
      }
    }

  public function iWaitForAjaxToFinish($time = 5000) {
    $condition = <<<JS
    (function() {
      function isAjaxing(instance) {
        return instance && instance.ajaxing === true;
      }
      var d7_not_ajaxing = true;
      if (typeof Drupal !== 'undefined' && typeof Drupal.ajax !== 'undefined' && typeof Drupal.ajax.instances === 'undefined') {
        for(var i in Drupal.ajax) { if (isAjaxing(Drupal.ajax[i])) { d7_not_ajaxing = false; } }
      }
      var d8_not_ajaxing = (typeof Drupal === 'undefined' || typeof Drupal.ajax === 'undefined' || typeof Drupal.ajax.instances === 'undefined' || !Drupal.ajax.instances.some(isAjaxing))
      return (
        // Assert no AJAX request is running (via jQuery or Drupal) and no
        // animation is running.
        (typeof jQuery === 'undefined' || (jQuery.active === 0 && jQuery(':animated').length === 0)) &&
        d7_not_ajaxing && d8_not_ajaxing
      );
    }());
JS;
    $result = $this->getSession()->wait($time, $condition);
    if (!$result) {
      throw new \RuntimeException('Unable to complete AJAX request.');
    }
  }

  /**
   * @Given there is a :arg1 student survey block
   */
  public function thereIsAStudentSurveyBlock($arg1)
  {
    $block_data = [
      'id' => 'test_student_survey_' . mb_strtolower($this->getRandom()->name()),
      'plugin' => 'dgreat_modal_student_survey',
      'region' => 'content',
      'settings' => [
        'survey' => $arg1
      ],
      'theme' => 'myusf',
      'visibility' => [],
      'weight' => 100,
    ];
    $block = \Drupal\block\Entity\Block::create($block_data);
    $block->configuration['survey'] = $arg1;
    $block->save();
  }

  /**
   * @Then I should see a modal titled :arg1
   */
  public function iShouldSeeAModalTitled($arg1)
  {
    $this->getSession()->wait(200000, '(0 === jQuery.active && 0 === jQuery(\':animated\').length)');
    $this->minkContext->assertElementContainsText('#studentSurveyLabel', $arg1);
  }

  /**
   * @Then I rate :arg1 a :arg2
   */
  public function iRateA($arg1, $arg2)
  {
    $this->minkContext->fillField($arg1, $arg2);
  }
}
