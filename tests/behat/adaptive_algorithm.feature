@mod @mod_adaptivequiz
Feature: Adaptive quiz content
  In order to take a quiz with the CAT (Computer Adaptive Testing) algorithm
  As a student
  I need the quiz to adjust the questions sequence with accordance to CAT

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
      | questioncategory        | qtype     | name | questiontext                | answer |
      | Adaptive Quiz Questions | truefalse | Q1   | Question 1 (difficulty 1).  | True   |
      | Adaptive Quiz Questions | truefalse | Q2   | Question 2 (difficulty 1).  | True   |
      | Adaptive Quiz Questions | truefalse | Q3   | Question 3 (difficulty 2).  | True   |
      | Adaptive Quiz Questions | truefalse | Q4   | Question 4 (difficulty 2).  | True   |
      | Adaptive Quiz Questions | truefalse | Q5   | Question 5 (difficulty 3).  | True   |
      | Adaptive Quiz Questions | truefalse | Q6   | Question 6 (difficulty 3).  | True   |
      | Adaptive Quiz Questions | truefalse | Q7   | Question 7 (difficulty 4).  | True   |
      | Adaptive Quiz Questions | truefalse | Q8   | Question 8 (difficulty 4).  | True   |
      | Adaptive Quiz Questions | truefalse | Q9   | Question 9 (difficulty 5).  | True   |
      | Adaptive Quiz Questions | truefalse | Q10  | Question 10 (difficulty 5). | True   |
    And the following "core_question > Tags" exist:
      | question  | tag    |
      | Q1        | adpq_1 |
      | Q2        | adpq_1 |
      | Q3        | adpq_2 |
      | Q4        | adpq_2 |
      | Q5        | adpq_3 |
      | Q6        | adpq_3 |
      | Q7        | adpq_4 |
      | Q8        | adpq_4 |
      | Q9        | adpq_5 |
      | Q10       | adpq_5 |

  @javascript
  Scenario: 20% standard error, user performs 1 level above the starting level
    Given I am on the "C1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I add a "Adaptive Quiz" to section "1"
    And I set the following fields to these values:
      | Name                         | Adaptive Quiz                |
      | Description                  | Adaptive quiz description.   |
      | Question pool                | Adaptive Quiz Questions (10) |
      | Starting level of difficulty | 2                            |
      | Lowest level of difficulty   | 1                            |
      | Highest level of difficulty  | 5                            |
      | Minimum number of questions  | 1                            |
      | Maximum number of questions  | 10                           |
      | Standard Error to stop       | 20                           |
      | ID number                    | adaptivequiz1                |
    And I click on "Save and return to course" "button"
    And I log out
    When I am on the "adaptivequiz1" "Activity" page logged in as "student1"
    And I press "Start attempt"
    Then I should see " (difficulty 2)."
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see " (difficulty 4)."
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see " (difficulty 3)."
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see " (difficulty 4)."
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see " (difficulty 3)."
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see " (difficulty 2)."
    And I click on "True" "radio"
    And I press "Submit answer"
    And "Continue" "button" should be visible

  @javascript
  Scenario: 20% standard error, user performs on the lowest level
    Given I am on the "C1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I add a "Adaptive Quiz" to section "1"
    And I set the following fields to these values:
      | Name                         | Adaptive Quiz                |
      | Description                  | Adaptive quiz description.   |
      | Question pool                | Adaptive Quiz Questions (10) |
      | Starting level of difficulty | 2                            |
      | Lowest level of difficulty   | 1                            |
      | Highest level of difficulty  | 5                            |
      | Minimum number of questions  | 1                            |
      | Maximum number of questions  | 10                           |
      | Standard Error to stop       | 20                           |
      | ID number                    | adaptivequiz1                |
    And I click on "Save and return to course" "button"
    And I log out
    When I am on the "adaptivequiz1" "Activity" page logged in as "student1"
    And I press "Start attempt"
    Then I should see " (difficulty 2)."
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see " (difficulty 1)."
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see " (difficulty 1)."
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see " (difficulty 2)."
    And I click on "False" "radio"
    And I press "Submit answer"
    And "Continue" "button" should be visible

  @javascript
  Scenario: 20% standard error, user performs on the highest level
    Given I am on the "C1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I add a "Adaptive Quiz" to section "1"
    And I set the following fields to these values:
      | Name                         | Adaptive Quiz                |
      | Description                  | Adaptive quiz description.   |
      | Question pool                | Adaptive Quiz Questions (10) |
      | Starting level of difficulty | 2                            |
      | Lowest level of difficulty   | 1                            |
      | Highest level of difficulty  | 5                            |
      | Minimum number of questions  | 1                            |
      | Maximum number of questions  | 10                           |
      | Standard Error to stop       | 20                           |
      | ID number                    | adaptivequiz1                |
    And I click on "Save and return to course" "button"
    And I log out
    When I am on the "adaptivequiz1" "Activity" page logged in as "student1"
    And I press "Start attempt"
    Then I should see " (difficulty 2)."
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see " (difficulty 4)."
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see " (difficulty 5)."
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see " (difficulty 5)."
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see " (difficulty 4)."
    And I click on "True" "radio"
    And I press "Submit answer"
    And "Continue" "button" should be visible
