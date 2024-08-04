@mod @mod_adaptivequiz
Feature: User may leave the adaptive quiz page and return to the current in-progress attempt
  In order to make the adaptive testing process robust
  As a student
  I need to be able to continue the previously started attempt with no corruption of the testing score data

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
      | questioncategory        | qtype     | name | questiontext     | answer |
      | Adaptive Quiz Questions | truefalse | TF1  | TF1 - level 1.   | True   |
      | Adaptive Quiz Questions | truefalse | TF2  | TF2 - level 2.   | True   |
      | Adaptive Quiz Questions | truefalse | TF3  | TF3 - level 3.   | True   |
      | Adaptive Quiz Questions | truefalse | TF4  | TF4 - level 4.   | True   |
      | Adaptive Quiz Questions | truefalse | TF5  | TF5 - level 5.   | True   |
      | Adaptive Quiz Questions | truefalse | TF6  | TF6 - level 6.   | True   |
      | Adaptive Quiz Questions | truefalse | TF7  | TF7 - level 7.   | True   |
      | Adaptive Quiz Questions | truefalse | TF8  | TF8 - level 8.   | True   |
      | Adaptive Quiz Questions | truefalse | TF9  | TF9 - level 9.   | True   |
      | Adaptive Quiz Questions | truefalse | TF10 | TF10 - level 10. | True   |
      | Adaptive Quiz Questions | truefalse | TF11 | TF11 - level 11. | True   |
      | Adaptive Quiz Questions | truefalse | TF12 | TF12 - level 12. | True   |
      | Adaptive Quiz Questions | truefalse | TF13 | TF13 - level 13. | True   |
      | Adaptive Quiz Questions | truefalse | TF14 | TF14 - level 14. | True   |
      | Adaptive Quiz Questions | truefalse | TF15 | TF15 - level 15. | True   |
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
    And I click on "True" "radio" in the "TF4" "question"
    And I press "Submit answer"
    And I click on "False" "radio" in the "TF7" "question"
    And I press "Submit answer"
    And I am on the "adaptivequiz1" "Activity" page
    And I click on "Start attempt" "link"
    And I click on "True" "radio" in the "TF5" "question"
    And I press "Submit answer"
    And I click on "False" "radio" in the "TF8" "question"
    And I press "Submit answer"
    And I click on "True" "radio" in the "TF6" "question"
    And I press "Submit answer"
    And I am on the "adaptivequiz1" "Activity" page
    And I click on "Start attempt" "link"
    And I click on "True" "radio" in the "TF9" "question"
    And I press "Submit answer"
    And I click on "True" "radio" in the "TF10" "question"
    And I press "Submit answer"
    And I am on the "adaptivequiz1" "Activity" page
    And I click on "Start attempt" "link"
    And I click on "True" "radio" in the "TF11" "question"
    And I press "Submit answer"
    And I click on "True" "radio" in the "TF12" "question"
    And I press "Submit answer"
    And I click on "True" "radio" in the "TF13" "question"
    And I press "Submit answer"
    And I am on the "adaptivequiz1" "Activity" page
    And I click on "Start attempt" "link"
    And I click on "True" "radio" in the "TF14" "question"
    And I press "Submit answer"
    And I click on "True" "radio" in the "TF15" "question"
    And I press "Submit answer"
    And I click on "True" "radio" in the "TF3" "question"
    And I press "Submit answer"
    And I press "Continue"
    And I log out

  @javascript
  Scenario: Individual user attempts report
    When I am on the "adaptivequiz1" "Activity" page logged in as "teacher1"
    And I click on "1" "link" in the "Peter The Student" "table_row"
    And I click on "Review attempt" "link" in the "Completed" "table_row"
    Then I should see "complete" in the "Attempt state" "table_row"
    And I should see "13.29" in the "Score" "table_row"
    And I should see "18.2%" in the "Score" "table_row"
    And I should see "Unable to fetch a question for level 4" in the "Reason for stopping attempt" "table_row"
