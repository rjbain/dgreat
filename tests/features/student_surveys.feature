Feature: Student Surveys
  As a USF Admin,
  I want an easy interface for entering multiple choice questions (typically 1-5) associated to specific Salesforce campaigns,
  so I can easily make new surveys and have that data linked to our Salesforce CRM for my easy browsing and analysis.

  @api @javascript @survey
  Scenario: Administrator Creates a Survey
    Given I am logged in as a user with the "administrator" role
    And I create a webform called "test_student_survey" from the "student_survey" template
    And I set the "test_student_survey" survey salesforce_id to "12345"
    And I add a student survey rating to the "test_student_survey" survey with question of "How Awesome is Dustin?" and a salesforce_id of "12345"
    Then I should see "How Awesome is Dustin?"

  @api @javascript
  Scenario: Student skips a survey
    Given users:
      | name         | status | mail               | roles   | cas_username |
      | SS Test User |      1 | sstest@example.com | student | sstest       |
    And I am logged in as "SS Test User"
    And "SS Test User" is selected for the current survey
    And there is a "test_student_survey" student survey block
    And I am on "/"
    And I click "Skip"
    And I am on "/dashboard"
    Then I should not see "Test Student Survey"

  @api @javascript
  Scenario: Student Completes a Survey
    Given users:
      | name         | status | mail               | roles   | cas_username |
      | SS Test User |      1 | sstest@example.com | student | sstest       |
    And I am logged in as "SS Test User"
    And "SS Test User" is selected for the current survey
    And there is a "test_student_survey" student survey block
    And I am on "/"
    Then I should see a modal titled "Test Student Survey"
    Then I select the radio button "Strongly Agree"
    And I press the "Submit" submit button
    And I wait for AJAX to finish
    Then I should see "Thanks for participating in the survey!"
