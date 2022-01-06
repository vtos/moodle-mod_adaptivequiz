@mod @mod_adaptivequiz
Feature: Attempt an adaptive quiz
  In order to demonstrate what I know using the adaptive quiz strategy
  As a student
  I need to be able to attempt an adaptive quiz

  Background:
    Given the following "users" exist:
      | username | firstname | lastname    | email                       |
      | teacher1 | John      | The Teacher | johntheteacher@example.com  |
      | student1 | Peter     | The Student | peterthestudent@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "question categories" exist:
      | contextlevel | reference | name                    |
      | Course       | C1        | Adaptive Quiz Questions |
    And the following "questions" exist:
      | questioncategory        | qtype     | name | questiontext    | tags   |
      | Adaptive Quiz Questions | truefalse | TF1  | First question  | adpq_1 |
      | Adaptive Quiz Questions | truefalse | TF2  | Second question | adpq_2 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Adaptive Quiz" to section "1"
    And I set the following fields to these values:
      | Name                         | Adaptive Quiz              |
      | Description                  | Adaptive quiz description. |
      | Question pool                | Adaptive Quiz Questions    |
      | Starting level of difficulty | 3                          |
      | Lowest level of difficulty   | 1                          |
      | Highest level of difficulty  | 10                         |
      | Minimum number of questions  | 2                          |
      | Maximum number of questions  | 20                         |
      | Standard Error to stop       | 5                          |
    And I click on "Save and return to course" "button"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank > Questions" in current page administration
    And I set the field "Select a category" to "Adaptive Quiz Questions (2)"
    And I choose "Manage tags" action for "TF1" in the question bank
    And I set the field "Tags" to "adpq_1"
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Attempt an adaptive quiz
    When I am on the "Adaptive Quiz" "adaptivequiz activity" page logged in as "student1"
    And I press "Start attempt"
    Then I should see "First question"
