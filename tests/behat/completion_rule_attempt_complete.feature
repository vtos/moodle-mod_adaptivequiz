@mod @mod_adaptivequiz
Feature: Set activity as completed when at least one attempt is completed
  In order to control whether the activity has been complete by students
  As a teacher
  I need the activity to be marked as completed for a student when they have made at least one attempt on the adaptive quiz

  Background:
    Given the following "users" exist:
      | username | firstname | lastname    | email                       |
      | teacher1 | John      | The Teacher | johntheteacher@example.com  |
      | student1 | Peter     | The Student | peterthestudent@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "question categories" exist:
      | contextlevel | reference | name                    |
      | Course       | C1        | Adaptive Quiz Questions |
    And the following "questions" exist:
      | questioncategory        | qtype     | name | questiontext    | answer |
      | Adaptive Quiz Questions | truefalse | TF1  | First question  | True   |
      | Adaptive Quiz Questions | truefalse | TF2  | Second question | True   |
    And the following "core_question > Tags" exist:
      | question | tag    |
      | TF1      | adpq_2 |
      | TF2      | adpq_3 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Adaptive Quiz" to section "1"
    And I expand all fieldsets
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
      | Completion tracking          | Show activity as complete when conditions are met |
    And I wait "2" seconds
    And I click on "completionattemptcompleted" "checkbox"
    And I click on "Save and return to course" "button"
    And I log out

  @javascript
  Scenario: Student completes an attempt
    When I log in as "student1"
    And I am on the "adaptivequiz1" "Activity" page
    And I press "Start attempt"
    And I click on "True" "radio" in the "First question" "question"
    And I press "Submit answer"
    And I click on "True" "radio" in the "Second question" "question"
    And I press "Submit answer"
    And I press "Continue"
    And I log out
    And I log in as "teacher1"
    And I am on the "adaptivequiz1" "Activity" page
    Then "Adaptive Quiz" should have the "Complete an attempt" completion condition
    And I am on "Course 1" course homepage
    And I navigate to "Reports" in current page administration
    And I click on "Activity completion" "link"
    And "Completed" "icon" should exist in the "Peter The Student" "table_row"
