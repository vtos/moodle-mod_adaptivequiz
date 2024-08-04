@mod @mod_adaptivequiz
Feature: View results of an individual student in adaptive quiz
  In order to control what results an individual student has on attempting adaptive quizzes
  As a teacher
  I need access to individual user report

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
      | questioncategory        | qtype     | name | questiontext | answer |
      | Adaptive Quiz Questions | truefalse | TF1  | TF1.         | True   |
      | Adaptive Quiz Questions | truefalse | TF2  | TF2.         | True   |
      | Adaptive Quiz Questions | truefalse | TF3  | TF3.         | True   |
      | Adaptive Quiz Questions | truefalse | TF4  | TF4.         | True   |
      | Adaptive Quiz Questions | truefalse | TF5  | TF5.         | True   |
      | Adaptive Quiz Questions | truefalse | TF6  | TF6.         | True   |
      | Adaptive Quiz Questions | truefalse | TF7  | TF7.         | True   |
      | Adaptive Quiz Questions | truefalse | TF8  | TF8.         | True   |
      | Adaptive Quiz Questions | truefalse | TF9  | TF9.         | True   |
      | Adaptive Quiz Questions | truefalse | TF10 | TF10.        | True   |
      | Adaptive Quiz Questions | truefalse | TF11 | TF11.        | True   |
      | Adaptive Quiz Questions | truefalse | TF12 | TF12.        | True   |
      | Adaptive Quiz Questions | truefalse | TF13 | TF13.        | True   |
      | Adaptive Quiz Questions | truefalse | TF14 | TF14.        | True   |
      | Adaptive Quiz Questions | truefalse | TF15 | TF15.        | True   |
      | Adaptive Quiz Questions | truefalse | TF16 | TF16.        | True   |
      | Adaptive Quiz Questions | truefalse | TF17 | TF17.        | True   |
      | Adaptive Quiz Questions | truefalse | TF18 | TF18.        | True   |
    And the following "core_question > Tags" exist:
      | question | tag     |
      | TF1      | adpq_1  |
      | TF2      | adpq_2  |
      | TF3      | adpq_3  |
      | TF4      | adpq_4  |
      | TF5      | adpq_5  |
      | TF6      | adpq_6  |
      | TF7      | adpq_7  |
      | TF8      | adpq_8  |
      | TF9      | adpq_9  |
      | TF10     | adpq_10 |
      | TF11     | adpq_11 |
      | TF12     | adpq_12 |
      | TF13     | adpq_13 |
      | TF14     | adpq_14 |
      | TF15     | adpq_15 |
      | TF16     | adpq_16 |
      | TF17     | adpq_17 |
      | TF18     | adpq_18 |
    And the following "activity" exists:
      | activity          | adaptivequiz            |
      | idnumber          | adaptivequiz1           |
      | course            | C1                      |
      | name              | Adaptive Quiz           |
      | startinglevel     | 1                       |
      | lowestlevel       | 1                       |
      | highestlevel      | 15                      |
      | minimumquestions  | 10                      |
      | maximumquestions  | 18                      |
      | standarderror     | 9                       |
      | questionpoolnamed | Adaptive Quiz Questions |
    And I am on the "adaptivequiz1" "Activity" page logged in as "student1"
    And I click on "Start attempt" "link"
    And I click on "True" "radio" in the "TF1" "question"
    And I press "Submit answer"
    And I click on "False" "radio" in the "TF4" "question"
    And I press "Submit answer"
    And I click on "True" "radio" in the "TF2" "question"
    And I press "Submit answer"
    And I click on "True" "radio" in the "TF3" "question"
    And I press "Submit answer"
    And I click on "True" "radio" in the "TF5" "question"
    And I press "Submit answer"
    And I click on "True" "radio" in the "TF6" "question"
    And I press "Submit answer"
    And I click on "True" "radio" in the "TF7" "question"
    And I press "Submit answer"
    And I click on "True" "radio" in the "TF8" "question"
    And I press "Submit answer"
    And I click on "True" "radio" in the "TF9" "question"
    And I press "Submit answer"
    And I click on "True" "radio" in the "TF10" "question"
    And I press "Submit answer"
    And I click on "True" "radio" in the "TF11" "question"
    And I press "Submit answer"
    And I click on "True" "radio" in the "TF12" "question"
    And I press "Submit answer"
    And I click on "False" "radio" in the "TF13" "question"
    And I press "Submit answer"
    And I press "Continue"
    And I log out

  @javascript
  Scenario: Individual user attempts report
    When I am on the "adaptivequiz1" "Activity" page logged in as "teacher1"
    And I click on "1" "link" in the "Peter The Student" "table_row"
    Then I should see "Adaptive Quiz - individual user attempts report for Peter The Student"
    And "Completed" "table_row" should exist
    And "Completed" row "Reason for stopping attempt" column of "individualuserattemptstable" table should contain "Unable to fetch a question for level 12"
    And "Completed" row "Sum of questions attempted" column of "individualuserattemptstable" table should contain "13"
    And "Completed" row "Score" column of "individualuserattemptstable" table should contain "11.9 ± 18%"

  @javascript
  Scenario: View attempt summary
    When I am on the "adaptivequiz1" "Activity" page logged in as "teacher1"
    And I click on "1" "link" in the "Peter The Student" "table_row"
    And I click on "Review attempt" "link" in the "Completed" "table_row"
    Then I should see "Peter The Student (peterthestudent@example.com)" in the "User" "table_row"
    And I should see "complete" in the "Attempt state" "table_row"
    # The below two lines split checking of the score value. Oddly, it's not possible to check the entire column's value.
    And I should see "11.89" in the "Score" "table_row"
    And I should see "18.3%" in the "Score" "table_row"
    And I should see "Unable to fetch a question for level 12" in the "Reason for stopping attempt" "table_row"

  @javascript
  Scenario: View attempt questions details
    When I am on the "adaptivequiz1" "Activity" page logged in as "teacher1"
    And I click on "1" "link" in the "Peter The Student" "table_row"
    And I click on "Review attempt" "link" in the "Completed" "table_row"
    And I click on "Questions Details" "link"
    # Question state elements.
    Then I should see "Correct" in the "[id^=question-][id$=-1] .info .state" "css_element"
    And I should see "Incorrect" in the "[id^=question-][id$=-2] .info .state" "css_element"
    And I should see "Correct" in the "[id^=question-][id$=-3] .info .state" "css_element"
    And I should see "Correct" in the "[id^=question-][id$=-4] .info .state" "css_element"
    And I should see "Correct" in the "[id^=question-][id$=-5] .info .state" "css_element"
    And I should see "Correct" in the "[id^=question-][id$=-6] .info .state" "css_element"
    And I should see "Correct" in the "[id^=question-][id$=-7] .info .state" "css_element"
    And I should see "Correct" in the "[id^=question-][id$=-8] .info .state" "css_element"
    And I should see "Correct" in the "[id^=question-][id$=-9] .info .state" "css_element"
    And I should see "Correct" in the "[id^=question-][id$=-10] .info .state" "css_element"
    And I click on "2" "link" in the "region-main" "region"
    And I should see "Correct" in the "[id^=question-][id$=-11] .info .state" "css_element"
    And I should see "Correct" in the "[id^=question-][id$=-12] .info .state" "css_element"
    And I should see "Incorrect" in the "[id^=question-][id$=-13] .info .state" "css_element"
