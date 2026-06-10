@mod @mod_adaptivequiz
Feature: Add an adaptive quiz
  In order to evaluate students using an adaptive questions strategy
  As a teacher
  I need to add an adaptive quiz activity to a course and configure it

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
    And the following "activities" exist:
      | activity | name    | course | idnumber |
      | qbank    | Qbank 1 | C1     | qbank1   |
    And the following "question categories" exist:
      | contextlevel    | reference | name                    |
      | Activity module | qbank1    | Adaptive Quiz Questions |
    And the following "questions" exist:
      | questioncategory        | qtype     | name | questiontext     |
      | Adaptive Quiz Questions | truefalse | TF1  | First question.  |
      | Adaptive Quiz Questions | truefalse | TF2  | Second question. |
      | Adaptive Quiz Questions | truefalse | TF3  | Third question.  |
      | Adaptive Quiz Questions | truefalse | TF4  | Fourth question. |

  @javascript
  Scenario: Add an adaptive quiz to a course and configure it to be visible to a student
    When the following "core_question > Tags" exist:
      | question | tag    |
      | TF1      | adpq_1 |
      | TF2      | adpq_1 |
      | TF3      | adpq_2 |
      | TF4      | adpq_2 |
    And I log in as "teacher1"
    And I add a "adaptivequiz" activity to course "Course 1" section "1" and I fill the form with:
      | Name                         | Adaptive Quiz              |
      | Description                  | Adaptive quiz description. |
      | ID number                    | adaptivequiz1              |
    And I am on the "adaptivequiz1" "Activity" page
    And I should see "Item bank is not configured properly for this adaptive quiz module at this moment."
    And I click on "Item bank" "link"
    And I click on "Assign new question bank" "button"
    And I click on "#fitem_addqbanksthis .form-autocomplete-downarrow" "css_element"
    And I click on "C1 - Qbank 1" item in the autocomplete list
    And I press "Add selected question banks"
    And I click on "Edit parameters" "button"
    And I set the following fields to these values:
      | Highest level of difficulty  | 2  |
      | Lowest level of difficulty   | 1  |
      | Starting level of difficulty | 1  |
      | Minimum number of questions  | 1  |
      | Maximum number of questions  | 2  |
      | Standard Error to stop       | 25 |
    And I press "Save"
    And I log out
    And I am on the "adaptivequiz1" "Activity" page logged in as "student1"
    Then "Start attempt" "button" should exist

  @javascript
  Scenario: Add an adaptive quiz with no item bank configuration making it unavailable for students
    When I log in as "teacher1"
    And I add a "adaptivequiz" activity to course "Course 1" section "1" and I fill the form with:
      | Name                         | Adaptive Quiz               |
      | Description                  | Adaptive quiz description.  |
      | ID number                    | adaptivequiz1               |
    And I log out
    And I am on the "adaptivequiz1" "Activity" page logged in as "student1"
    Then I should see "Item bank is not configured properly for this adaptive quiz module at this moment."
    And "Start attempt" "button" should not exist
