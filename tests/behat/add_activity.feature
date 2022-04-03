@mod @mod_adaptivequiz
Feature: Add an adaptive quiz
  In order to evaluate students using an adaptive questions strategy
  As a teacher
  I need to add an adaptive quiz activity to a course

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
      | questioncategory        | qtype     | name | questiontext     |
      | Adaptive Quiz Questions | truefalse | TF1  | First question.  |
      | Adaptive Quiz Questions | truefalse | TF2  | Second question. |
      | Adaptive Quiz Questions | truefalse | TF3  | Third question.  |
      | Adaptive Quiz Questions | truefalse | TF4  | Fourth question. |

  @javascript
  Scenario: Add an adaptive quiz to a course to be visible to a student
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I navigate to "Question bank > Questions" in current page administration
    And I set the field "Select a category" to "Adaptive Quiz Questions (4)"
    And I choose "Edit question" action for "TF1" in the question bank
    And I expand all fieldsets
    And I set the field "Tags" to "adpq_1"
    And I press "id_submitbutton"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Adaptive Quiz" to section "1"
    And I set the following fields to these values:
      | Name                         | Adaptive Quiz               |
      | Description                  | Adaptive quiz description.  |
      | Question pool                | Adaptive Quiz Questions (4) |
      | Starting level of difficulty | 1                           |
      | Lowest level of difficulty   | 1                           |
      | Highest level of difficulty  | 2                           |
      | Minimum number of questions  | 1                           |
      | Maximum number of questions  | 2                           |
      | Standard Error to stop       | 25                          |
      | ID number                    | adaptivequiz1               |
    And I click on "Save and return to course" "button"
    And I log out
    And I am on the "adaptivequiz1" "Activity" page logged in as "student1"
    Then "Start attempt" "button" should exist

  @javascript
  Scenario: It is impossible to create an adaptive quiz without a properly tagged question for the starting level of difficulty
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I navigate to "Question bank > Questions" in current page administration
    And I set the field "Select a category" to "Adaptive Quiz Questions (4)"
    And I choose "Edit question" action for "TF1" in the question bank
    And I expand all fieldsets
    And I set the field "Tags" to "adpq_001"
    And I press "id_submitbutton"
    And I wait until the page is ready
    And I choose "Edit question" action for "TF2" in the question bank
    And I expand all fieldsets
    And I set the field "Tags" to "adpq_2"
    And I press "id_submitbutton"
    And I wait until the page is ready
    And I choose "Edit question" action for "TF3" in the question bank
    And I expand all fieldsets
    And I set the following fields to these values:
      | Tags | truefalse_1, TF |
    And I press "id_submitbutton"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Adaptive Quiz" to section "1"
    And I set the following fields to these values:
      | Name                         | Adaptive Quiz               |
      | Description                  | Adaptive quiz description.  |
      | Question pool                | Adaptive Quiz Questions (4) |
      | Starting level of difficulty | 1                           |
      | Lowest level of difficulty   | 1                           |
      | Highest level of difficulty  | 2                           |
      | Minimum number of questions  | 1                           |
      | Maximum number of questions  | 2                           |
      | Standard Error to stop       | 25                          |
    And I click on "Save and return to course" "button"
    Then I should see "The selected questions categories do not contain questions which are properly tagged to match the selected starting level of difficulty."
