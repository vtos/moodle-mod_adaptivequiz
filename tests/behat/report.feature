@mod @mod_adaptivequiz @adaptivequiz_report
Feature: Attempt an adaptive quiz
  In order to control what results students have on attempting adaptive quizzes
  As a teacher
  I need an access to attempts reporting

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
      | questioncategory        | qtype     | name | questiontext    | answer 1 | grade |
      | Adaptive Quiz Questions | truefalse | TF1  | First question  | True     | 100%  |
      | Adaptive Quiz Questions | truefalse | TF2  | Second question | True     | 100%  |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Adaptive Quiz" to section "1"
    And I set the following fields to these values:
      | Name                         | Adaptive Quiz              |
      | Description                  | Adaptive quiz description. |
      | Question pool                | Adaptive Quiz Questions    |
      | Starting level of difficulty | 2                          |
      | Lowest level of difficulty   | 1                          |
      | Highest level of difficulty  | 10                         |
      | Minimum number of questions  | 2                          |
      | Maximum number of questions  | 20                         |
      | Standard Error to stop       | 5                          |
    And I click on "Save and return to course" "button"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank > Questions" in current page administration
    And I set the field "Select a category" to "Adaptive Quiz Questions (2)"
    And I choose "Edit question" action for "TF1" in the question bank
    And I expand all fieldsets
    And I set the field "Tags" to "adpq_2"
    And I press "id_submitbutton"
    And I wait until the page is ready
    And I choose "Edit question" action for "TF2" in the question bank
    And I expand all fieldsets
    And I set the field "Tags" to "adpq_3"
    And I press "id_submitbutton"
    And I log out
    And I log in as "student1"
    And I am on the "Adaptive Quiz" "adaptivequiz activity" page
    And I press "Start attempt"
    And I click on "True" "radio" in the "First question" "question"
    And I press "Submit answer"
    And I click on "True" "radio" in the "Second question" "question"
    And I press "Submit answer"
    And I press "Continue"
    And I log out

  @javascript
  Scenario: Attempts report
    When I am on the "Adaptive Quiz" "adaptivequiz activity" page logged in as "teacher1"
    And I press "View report"
    Then I should see "Attempts report"
    And "Peter The Student" "table_row" should exist
    And "Peter The Student" row "Number of attempts" column of "quizsummaryofattempt" table should contain "1"

  @javascript
  Scenario: Individual user attempts report
    When I am on the "Adaptive Quiz" "adaptivequiz activity" page logged in as "teacher1"
    And I press "View report"
    And I click on "1" "link" in the "Peter The Student" "table_row"
    Then I should see "Individual user attempts report for Peter The Student"
    And "Completed" "table_row" should exist
    And "Completed" row "Reason for stopping attempt" column of "quizsummaryofuserattempt" table should contain "Unable to fetch a questions for level 5"
    And "Completed" row "Sum of questions attempted" column of "quizsummaryofuserattempt" table should contain "2"

  @javascript
  Scenario: Review individual user attempt
    When I am on the "Adaptive Quiz" "adaptivequiz activity" page logged in as "teacher1"
    And I press "View report"
    And I click on "1" "link" in the "Peter The Student" "table_row"
    And I click on "Review attempt" "link" in the "Completed" "table_row"
    Then I should see "Attempt Summary"
    And I should see "Question Details"
    # Info on the first question
    And I should see "Correct" in the "[id^=question-][id$=-1] .info .state" "css_element"
    # Info on the second question
    And I should see "Correct" in the "[id^=question-][id$=-2] .info .state" "css_element"

  @javascript
  Scenario: View scoring tables on attempt
    When I am on the "Adaptive Quiz" "adaptivequiz activity" page logged in as "teacher1"
    And I press "View report"
    And I click on "1" "link" in the "Peter The Student" "table_row"
    And I click on "Review attempt" "link" in the "Completed" "table_row"
    And I click on "#adpq_scoring_table_link" "css_element"
    Then "#adpq_scoring_table" "css_element" should be visible
    # First scoring table with no caption, "Right/Wrong" column
    And I should see "r" in the "#adpq_scoring_table .generaltable:nth-of-type(1) tr:nth-of-type(1) td.c2" "css_element"
    And I should see "r" in the "#adpq_scoring_table .generaltable:nth-of-type(1) tr:nth-of-type(2) td.c2" "css_element"
    # Second scoring table with no caption, "Num right" column
    And I should see "2" in the "#adpq_scoring_table .generaltable:nth-of-type(2) tr:nth-of-type(1) td.c1" "css_element"
