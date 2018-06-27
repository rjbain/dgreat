Feature: Student Surveys
  As a USF Admin,
  I want an easy interface for entering multiple choice questions (typically 1-5) associated to specific Salesforce campaigns,
  so I can easily make new surveys and have that data linked to our Salesforce CRM for my easy browsing and analysis.

  @api
  Scenario: Administrator Creates a Survey
    Given I am logged in as a user with the "administrator" role
    And I create a webform called "test_student_survey" from the "student_survey" template
    And I set the "test_student_survey" survey salesforce_id to "12345"
    And I add a student survey rating to the "test_student_survey" survey with a salesforce_id of "12345"
    Then I should see "How awesome is Dustin?"
