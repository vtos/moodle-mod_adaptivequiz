@mod @mod_adaptivequiz
Feature: View students results in adaptive quiz
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
      | questioncategory        | qtype     | name | questiontext    | answer |
      | Adaptive Quiz Questions | truefalse | TF1  | First question  | True   |
      | Adaptive Quiz Questions | truefalse | TF2  | Second question | True   |
    And the following "core_question > Tags" exist:
      | question  | tag    |
      | TF1       | adpq_2 |
      | TF2       | adpq_3 |
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
    And I am on "Course 1" course homepage
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
  Scenario: Attempts report
    When I am on the "adaptivequiz1" "Activity" page logged in as "teacher1"
    Then "Attempts: 1" "link" should exist
    And I click on "Attempts: 1" "link"
    And I should see "Attempts report"
    And "Peter The Student" "table_row" should exist
    And "Peter The Student" row "Number of attempts" column of "usersattemptstable" table should contain "1"

  @javascript
  Scenario: Deleted user should not appear in the attempts report
    Given the following "user" exists:
      | username     | student2                    |
      | firstname    | Henry                       |
      | lastname     | The Student                 |
      | email        | henrythestudent@example.com |
    And the following "course enrolment" exists:
      | user         | student2 |
      | course       | C1       |
      | role         | student  |
    And I am on the "adaptivequiz1" "Activity" page logged in as "student2"
    And I press "Start attempt"
    And I click on "True" "radio" in the "First question" "question"
    And I press "Submit answer"
    And I click on "True" "radio" in the "Second question" "question"
    And I press "Submit answer"
    And I press "Continue"
    And I log out
    And I log in as "admin"
    And I navigate to "Users > Accounts > Bulk user actions" in site administration
    And I set the field "Available" to "Henry The Student"
    And I press "Add to selection"
    And I set the field "id_action" to "Delete"
    And I press "Go"
    And I press "Yes"
    And I log out
    When I am on the "adaptivequiz1" "Activity" page logged in as "teacher1"
    And I click on "Attempts: 2" "link"
    Then "Henry The Student" "table_row" should not exist

  @javascript
  Scenario: Individual user attempts report
    When I am on the "adaptivequiz1" "Activity" page logged in as "teacher1"
    And I click on "Attempts: 1" "link"
    And I click on "1" "link" in the "Peter The Student" "table_row"
    Then I should see "Individual user attempts report for Peter The Student"
    And "Completed" "table_row" should exist
    And "Completed" row "Reason for stopping attempt" column of "individualuserattemptstable" table should contain "Unable to fetch a question for level 5"
    And "Completed" row "Sum of questions attempted" column of "individualuserattemptstable" table should contain "2"

  @javascript
  Scenario: View attempt summary
    When I am on the "adaptivequiz1" "Activity" page logged in as "teacher1"
    And I click on "Attempts: 1" "link"
    And I click on "1" "link" in the "Peter The Student" "table_row"
    And I click on "Review attempt" "link" in the "Completed" "table_row"
    Then I should see "Peter The Student (peterthestudent@example.com)" in the "User" "table_row"

  @javascript
  Scenario: View attempt graph
    When I am on the "adaptivequiz1" "Activity" page logged in as "teacher1"
    And I click on "Attempts: 1" "link"
    And I click on "1" "link" in the "Peter The Student" "table_row"
    And I click on "Review attempt" "link" in the "Completed" "table_row"
    And I click on "Attempt Graph" "link"
    Then "img.adaptivequiz-attemptgraph" "css_element" should be visible
    And I should see "Table View of Attempt Graph"
    # "Question Level" column
    And I should see "2" in the ".generaltable tr:nth-of-type(1) td.c1" "css_element"
    And I should see "3" in the ".generaltable tr:nth-of-type(2) td.c1" "css_element"
    # "Right/Wrong" column
    And I should see "r" in the ".generaltable tr:nth-of-type(1) td.c2" "css_element"
    And I should see "r" in the ".generaltable tr:nth-of-type(2) td.c2" "css_element"
    # "Ability Measure" column
    And I should see "2" in the ".generaltable tr:nth-of-type(1) td.c3" "css_element"
    And I should see "4.26" in the ".generaltable tr:nth-of-type(2) td.c3" "css_element"
    # "Standard Error (± x%)" column
    And I should see "38.1%" in the ".generaltable tr:nth-of-type(1) td.c4" "css_element"
    And I should see "33.7%" in the ".generaltable tr:nth-of-type(2) td.c4" "css_element"

  @javascript
  Scenario: View attempt answer distribution
    When I am on the "adaptivequiz1" "Activity" page logged in as "teacher1"
    And I click on "Attempts: 1" "link"
    And I click on "1" "link" in the "Peter The Student" "table_row"
    And I click on "Review attempt" "link" in the "Completed" "table_row"
    And I click on "Answer Distribution" "link"
    Then "img.adaptivequiz-answerdistributiongraph" "css_element" should be visible
    And I should see "Table View of Answer Distribution"
    # Second scoring table with no caption, "Question Level" column
    And I should see "2" in the ".generaltable tr:nth-of-type(2) td.c0" "css_element"
    And I should see "3" in the ".generaltable tr:nth-of-type(3) td.c0" "css_element"
    # Second scoring table with no caption, "Num right" column
    And I should see "1" in the ".generaltable tr:nth-of-type(2) td.c1" "css_element"
    And I should see "1" in the ".generaltable tr:nth-of-type(3) td.c1" "css_element"
    # Second scoring table with no caption, "Num wrong" column
    And I should see "0" in the ".generaltable tr:nth-of-type(2) td.c2" "css_element"
    And I should see "0" in the ".generaltable tr:nth-of-type(3) td.c2" "css_element"

  @javascript
  Scenario: View attempt questions details
    When I am on the "adaptivequiz1" "Activity" page logged in as "teacher1"
    And I click on "Attempts: 1" "link"
    And I click on "1" "link" in the "Peter The Student" "table_row"
    And I click on "Review attempt" "link" in the "Completed" "table_row"
    And I click on "Questions Details" "link"
    # Info on the first question
    Then I should see "Correct" in the "[id^=question-][id$=-1] .info .state" "css_element"
    # Info on the second question
    And I should see "Correct" in the "[id^=question-][id$=-2] .info .state" "css_element"
