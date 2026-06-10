@mod @mod_adaptivequiz
Feature: View analysis of questions usage in adaptive quizzes
  In order to assess how items from the question bank are administered and answered in adaptive quizzes
  As a teacher
  I need a report with analysis of questions usage

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
      | questioncategory        | qtype     | name | questiontext    | answer |
      | Adaptive Quiz Questions | truefalse | TF1  | First question  | True   |
      | Adaptive Quiz Questions | truefalse | TF2  | Second question | True   |
    And the following "core_question > Tags" exist:
      | question  | tag    |
      | TF1       | adpq_1 |
      | TF2       | adpq_2 |
    And the following "activity" exists:
      | activity          | adaptivequiz            |
      | idnumber          | adaptivequiz1           |
      | course            | C1                      |
      | name              | Adaptive Quiz           |
      | startinglevel     | 1                       |
      | lowestlevel       | 1                       |
      | highestlevel      | 2                       |
      | minimumquestions  | 1                       |
      | maximumquestions  | 2                       |
      | standarderror     | 5                       |
      | questionpoolnamed | Adaptive Quiz Questions |
    And the following "mod_adaptivequiz > links with question banks" exist:
      | adaptivequiz  | idnumber |
      | Adaptive Quiz | qbank1   |
    And I am on the "adaptivequiz1" "Activity" page logged in as "student1"
    And I click on "Start attempt" "button"
    And I click on "True" "radio" in the "First question" "question"
    And I press "Submit answer"
    And I click on "True" "radio" in the "Second question" "question"
    And I press "Submit answer"
    And I press "Continue"
    And I log out

  @javascript
  Scenario: Navigate to reviewing of an attempt listed in single question analysis
    When I am on the "adaptivequiz1" "Activity" page logged in as "teacher1"
    And I click on "Question Analysis" "link"
    And I click on "TF1" "link"
    And I click on "Review attempt" "link" in the "Peter The Student" "table_row"
    Then I should see "Adaptive Quiz - reviewing attempt by Peter The Student"
