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
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank > Questions" in current page administration
    And I set the field "Select a category" to "Adaptive Quiz Questions (10)"
    And I choose "Edit question" action for "Q1" in the question bank
    And I expand all fieldsets
    And I set the field "Tags" to "adpq_1"
    And I press "id_submitbutton"
    And I wait until the page is ready
    And I choose "Edit question" action for "Q2" in the question bank
    And I expand all fieldsets
    And I set the field "Tags" to "adpq_1"
    And I press "id_submitbutton"
    And I wait until the page is ready
    And I choose "Edit question" action for "Q3" in the question bank
    And I expand all fieldsets
    And I set the field "Tags" to "adpq_2"
    And I press "id_submitbutton"
    And I wait until the page is ready
    And I choose "Edit question" action for "Q4" in the question bank
    And I expand all fieldsets
    And I set the field "Tags" to "adpq_2"
    And I press "id_submitbutton"
    And I wait until the page is ready
    And I choose "Edit question" action for "Q5" in the question bank
    And I expand all fieldsets
    And I set the field "Tags" to "adpq_3"
    And I press "id_submitbutton"
    And I wait until the page is ready
    And I choose "Edit question" action for "Q6" in the question bank
    And I expand all fieldsets
    And I set the field "Tags" to "adpq_3"
    And I press "id_submitbutton"
    And I wait until the page is ready
    And I choose "Edit question" action for "Q7" in the question bank
    And I expand all fieldsets
    And I set the field "Tags" to "adpq_4"
    And I press "id_submitbutton"
    And I wait until the page is ready
    And I choose "Edit question" action for "Q8" in the question bank
    And I expand all fieldsets
    And I set the field "Tags" to "adpq_4"
    And I press "id_submitbutton"
    And I wait until the page is ready
    And I choose "Edit question" action for "Q9" in the question bank
    And I expand all fieldsets
    And I set the field "Tags" to "adpq_5"
    And I press "id_submitbutton"
    And I wait until the page is ready
    And I choose "Edit question" action for "Q10" in the question bank
    And I expand all fieldsets
    And I set the field "Tags" to "adpq_5"
    And I press "id_submitbutton"
    And I log out

  @javascript
  Scenario: 20% standard error, user performs 1 level above the starting level
    Given I am on the "C1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I add a "Adaptive Quiz" to section "1"
    And I set the following fields to these values:
      | Name                         | Adaptive Quiz              |
      | Description                  | Adaptive quiz description. |
      | Question pool                | Adaptive Quiz Questions    |
      | Starting level of difficulty | 2                          |
      | Lowest level of difficulty   | 1                          |
      | Highest level of difficulty  | 5                          |
      | Minimum number of questions  | 1                          |
      | Maximum number of questions  | 10                         |
      | Standard Error to stop       | 20                         |
    And I click on "Save and return to course" "button"
    And I log out
    When I am on the "Adaptive Quiz" "adaptivequiz activity" page logged in as "student1"
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
      | Name                         | Adaptive Quiz              |
      | Description                  | Adaptive quiz description. |
      | Question pool                | Adaptive Quiz Questions    |
      | Starting level of difficulty | 2                          |
      | Lowest level of difficulty   | 1                          |
      | Highest level of difficulty  | 5                          |
      | Minimum number of questions  | 1                          |
      | Maximum number of questions  | 10                         |
      | Standard Error to stop       | 20                         |
    And I click on "Save and return to course" "button"
    And I log out
    When I am on the "Adaptive Quiz" "adaptivequiz activity" page logged in as "student1"
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
      | Name                         | Adaptive Quiz              |
      | Description                  | Adaptive quiz description. |
      | Question pool                | Adaptive Quiz Questions    |
      | Starting level of difficulty | 2                          |
      | Lowest level of difficulty   | 1                          |
      | Highest level of difficulty  | 5                          |
      | Minimum number of questions  | 1                          |
      | Maximum number of questions  | 10                         |
      | Standard Error to stop       | 20                         |
    And I click on "Save and return to course" "button"
    And I log out
    When I am on the "Adaptive Quiz" "adaptivequiz activity" page logged in as "student1"
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
