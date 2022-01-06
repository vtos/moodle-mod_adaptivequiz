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

  @javascript
  Scenario: Add an adaptive quiz to a course to be visible by a student
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Adaptive Quiz" to section "1"
    And I set the following fields to these values:
      | Name                         | Adaptive Quiz              |
      | Description                  | Adaptive quiz description. |
      | Question pool                | Default for C1             |
      | Starting level of difficulty | 3                          |
      | Lowest level of difficulty   | 1                          |
      | Highest level of difficulty  | 10                         |
      | Minimum number of questions  | 2                          |
      | Maximum number of questions  | 20                         |
      | Standard Error to stop       | 5                          |
    And I click on "Save and return to course" "button"
    And I log out
    And I am on the "Adaptive Quiz" "adaptivequiz activity" page logged in as "student1"
    Then "Start attempt" "button" should exist
