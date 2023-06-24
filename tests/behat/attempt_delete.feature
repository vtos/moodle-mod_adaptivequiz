@mod @mod_adaptivequiz
Feature: Delete an attempt on adaptive quiz
  In order to keep the results of adaptive quiz relevant
  As a teacher
  I need to be able to delete students' attempts

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
      | questioncategory        | qtype     | name | questiontext    |
      | Adaptive Quiz Questions | truefalse | TF1  | First question  |
      | Adaptive Quiz Questions | truefalse | TF2  | Second question |
    And the following "core_question > Tags" exist:
      | question | tag    |
      | TF1      | adpq_2 |
      | TF2      | adpq_3 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Adaptive Quiz" to section "1"
    And I set the following fields to these values:
      | Name                         | Adaptive Quiz               |
      | Description                  | Adaptive quiz description.  |
      | Question pool                | Adaptive Quiz Questions (2) |
      | Starting level of difficulty | 2                           |
      | Lowest level of difficulty   | 1                           |
      | Highest level of difficulty  | 10                          |
      | Minimum number of questions  | 2                           |
      | Maximum number of questions  | 20                          |
      | Standard Error to stop       | 5                           |
      | ID number                    | adaptivequiz1               |
    And I click on "Save and return to course" "button"
    And I log out
    And I log in as "student1"
    And I am on the "adaptivequiz1" "Activity" page
    And I press "Start attempt"
    And I click on "True" "radio" in the "First question" "question"
    And I press "Submit answer"
    And I click on "True" "radio" in the "Second question" "question"
    And I press "Submit answer"
    And I press "Continue"
    And I log out

  @javascript
  Scenario: Delete an individual attempt
    When I am on the "adaptivequiz1" "Activity" page logged in as "teacher1"
    And I click on "Attempts: 1" "link"
    And I click on "1" "link" in the "Peter The Student" "table_row"
    And I click on "Delete attempt" "link" in the "Completed" "table_row"
    And I press "Continue"
    And I should see "Nothing to display"
