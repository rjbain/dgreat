Feature: Quick Links
  As a USF User
  I can save quick links
  so that I can have a set of links that I can use at any time to handle common tasks

  @api
  Scenario: Student see's Add link
    Given I am logged in as a user with the 'student' role
    And I am on "/dashboard"
    Then I should see the link "Add Link"
    And I should see the link "Manage Links"

  @api
  Scenario: Student can create new Quicklink
    Given I am logged in as a user with the 'student' role
    And I am on "/dashboard"
    And I click "Add Link"
    And I fill in "Title" with "Magic"
    And I fill in "URL" with "https://thinktandem.io"
    And I press the "Save" button
    Then I should be on "/dashboard"
    And I should see the link "Magic"

  @api
  Scenario: Quick Links persist after logout/in:
    Given users:
      | name      | status | mail             | roles |
      | QL Test user |      1 | quicklinkstest@example.com | student |
    And I am logged in as "QL Test user"
    And I am on "/dashboard"
    And I click "Add Link"
    And I fill in "Title" with "Magic"
    And I fill in "URL" with "https://thinktandem.io"
    And I press the "Save" button
    Then I should be on "/dashboard"
    And I should see the link "Magic"
    And I click "Logout"
    And I am logged in as "QL Test user"
    And I am on "/dashboard"
    Then I should see the link "Magic"