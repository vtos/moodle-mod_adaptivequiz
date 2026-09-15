@mod @mod_adaptivequiz
Feature: Construct an item bank for an adaptive quiz
  In order to enable CAT administration for an adaptive quiz
  As a manager
  I need to construct an item bank for the adaptive quiz

  Background:
    Given the following "users" exist:
      | username | firstname | lastname    | email                      |
      | manager  | John      | The Manager | johnthemanager@example.com |
      | student1 | Peter     | The Student | peterthestudent@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | manager  | C1     | manager |
      | student1 | C1     | student |
    And the following "activities" exist:
      | activity | name    | course | idnumber |
      | qbank    | Qbank 1 | C1     | qbank1   |
      | qbank    | Qbank 2 | C1     | qbank2   |
    And the following "activity" exists:
      | activity          | adaptivequiz  |
      | idnumber          | adaptivequiz  |
      | course            | C1            |
      | name              | Adaptive Quiz |

  @javascript
  Scenario: Manager accesses an adaptive quiz with no item bank constructed
    When I am on the "adaptivequiz" "Activity" page logged in as "manager"
    Then I should see "Item bank is not configured properly for this adaptive quiz module at this moment."
    And I navigate to "Item bank" in current page administration
    And I should see "Item bank is not configured properly for this adaptive quiz module at this moment."
    And I should see "No question banks assigned yet."
    And I should see "No single question categories assigned yet."
    And I should see "Item administration settings are available only when there are question banks assigned to the item bank."

  @javascript
  Scenario: Student accesses an adaptive quiz with no item bank constructed
    When I am on the "adaptivequiz" "Activity" page logged in as "student1"
    Then I should see "Item bank is not configured properly for this adaptive quiz module at this moment."
    And "Start attempt" "button" should not be visible

  @javascript
  Scenario: Manager constructs an item bank for an adaptive quiz
    When I am on the "Adaptive Quiz" "mod_adaptivequiz > Item bank" page logged in as "manager"
    And I click on "Assign new question bank" "button"
    And I click on "#fitem_addqbanksthis .form-autocomplete-downarrow" "css_element"
    And I click on "C1 - Qbank 1" item in the autocomplete list
    And I press "Add selected question banks"
    Then I should see "Question banks in this course"
    And I should see "Qbank 1" in the "#qbanksthis" "css_element"
    And I should not see "Question banks in other courses"
    And I should see "No single question categories assigned yet."

  @javascript
  Scenario: Manager sets item administration parameters for an adaptive quiz
    When the following "question categories" exist:
      | contextlevel    | reference | name                    |
      | Activity module | qbank1    | Adaptive Quiz Questions |
    And the following "questions" exist:
      | questioncategory        | qtype     | name | questiontext     |
      | Adaptive Quiz Questions | truefalse | TF1  | First question.  |
      | Adaptive Quiz Questions | truefalse | TF2  | Second question. |
      | Adaptive Quiz Questions | truefalse | TF3  | Third question.  |
      | Adaptive Quiz Questions | truefalse | TF4  | Fourth question. |
    And the following "core_question > Tags" exist:
      | question | tag    |
      | TF1      | adpq_1 |
      | TF2      | adpq_1 |
      | TF3      | adpq_2 |
      | TF4      | adpq_2 |
    And the following "mod_adaptivequiz > links with question banks" exist:
      | adaptivequiz  | idnumber |
      | Adaptive Quiz | qbank1   |
    And I am on the "Adaptive Quiz" "mod_adaptivequiz > Item bank" page logged in as "manager"
    And I should see "Item administration parameters are not set for this module yet."
    And I click on "Edit parameters" "button"
    And I set the following fields to these values:
      | Highest level of difficulty  | 2 |
      | Lowest level of difficulty   | 1 |
      | Starting level of difficulty | 1 |
      | Minimum number of questions  | 1 |
      | Maximum number of questions  | 2 |
      | Standard Error to stop       | 9 |
    And I click on "Save" "button"
    Then I should not see "Item administration parameters are not set for this module yet."
    And I should see "2" in the "Highest level of difficulty:" "table_row"
    And I should see "OK" in the "Highest level of difficulty:" "table_row"
    And I should see "1" in the "Lowest level of difficulty:" "table_row"
    And I should see "OK" in the "Lowest level of difficulty:" "table_row"
    And I should see "1" in the "Starting level of difficulty:" "table_row"
    And I should see "OK" in the "Starting level of difficulty:" "table_row"
    And I should see "1" in the "Minimum number of questions:" "table_row"
    And I should see "OK" in the "Minimum number of questions:" "table_row"
    And I should see "2" in the "Maximum number of questions:" "table_row"
    And I should see "OK" in the "Maximum number of questions:" "table_row"
    And I should see "9.00000" in the "Standard Error to stop:" "table_row"
    And I should see "OK" in the "Standard Error to stop:" "table_row"

  @javascript
  Scenario: Manager unlinks a question bank from an adaptive quiz's item bank
    When the following "mod_adaptivequiz > links with question banks" exist:
      | adaptivequiz  | idnumber |
      | Adaptive Quiz | qbank1   |
      | Adaptive Quiz | qbank2   |
    And I am on the "Adaptive Quiz" "mod_adaptivequiz > Item bank" page logged in as "manager"
    And I click on "Unassign from the item bank" action for "Qbank 1" in assigned items
    Then "Confirm" "dialogue" should be visible
    And I click on "Continue" "button" in the "Confirm" "dialogue"
    And I should not see "Qbank 1"
    And I should see "Qbank 2"
