@mod @mod_adaptivequiz @mod_adaptivequiz_calc_steps
Feature: Debug calculation steps
  In order to test how steps calculation is performed in the CAT algorithm
  As a manager
  I need to be able to attempt an adaptive quiz and view the debugging info on each step

  Background:
    Given the following "users" exist:
      | username | firstname | lastname    | email                      |
      | manager  | John      | The Manager | johnthemanager@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role    |
      | manager | C1     | manager |
    And the following "activities" exist:
      | activity | name    | course | idnumber |
      | qbank    | Qbank 1 | C1     | qbank1   |
    And the following "question categories" exist:
      | contextlevel    | reference | name                    |
      | Activity module | qbank1    | Adaptive Quiz Questions |
    And the following "questions" exist:
      | questioncategory        | qtype     | name | questiontext                  | answer |
      | Adaptive Quiz Questions | truefalse | Q1   | Question 1 (difficulty 1).    | True   |
      | Adaptive Quiz Questions | truefalse | Q2   | Question 2 (difficulty 1).    | True   |
      | Adaptive Quiz Questions | truefalse | Q3   | Question 3 (difficulty 1).    | True   |
      | Adaptive Quiz Questions | truefalse | Q4   | Question 4 (difficulty 1).    | True   |
      | Adaptive Quiz Questions | truefalse | Q5   | Question 5 (difficulty 1).    | True   |
      | Adaptive Quiz Questions | truefalse | Q6   | Question 6 (difficulty 1).    | True   |
      | Adaptive Quiz Questions | truefalse | Q7   | Question 7 (difficulty 1).    | True   |
      | Adaptive Quiz Questions | truefalse | Q8   | Question 8 (difficulty 1).    | True   |
      | Adaptive Quiz Questions | truefalse | Q9   | Question 9 (difficulty 1).    | True   |
      | Adaptive Quiz Questions | truefalse | Q10  | Question 10 (difficulty 1).   | True   |
      | Adaptive Quiz Questions | truefalse | Q11  | Question 11 (difficulty 1).   | True   |
      | Adaptive Quiz Questions | truefalse | Q12  | Question 12 (difficulty 2).   | True   |
      | Adaptive Quiz Questions | truefalse | Q13  | Question 13 (difficulty 2).   | True   |
      | Adaptive Quiz Questions | truefalse | Q14  | Question 14 (difficulty 2).   | True   |
      | Adaptive Quiz Questions | truefalse | Q15  | Question 15 (difficulty 2).   | True   |
      | Adaptive Quiz Questions | truefalse | Q16  | Question 16 (difficulty 2).   | True   |
      | Adaptive Quiz Questions | truefalse | Q17  | Question 17 (difficulty 2).   | True   |
      | Adaptive Quiz Questions | truefalse | Q18  | Question 18 (difficulty 2).   | True   |
      | Adaptive Quiz Questions | truefalse | Q19  | Question 19 (difficulty 2).   | True   |
      | Adaptive Quiz Questions | truefalse | Q20  | Question 20 (difficulty 2).   | True   |
      | Adaptive Quiz Questions | truefalse | Q21  | Question 21 (difficulty 3).   | True   |
      | Adaptive Quiz Questions | truefalse | Q22  | Question 22 (difficulty 3).   | True   |
      | Adaptive Quiz Questions | truefalse | Q23  | Question 23 (difficulty 3).   | True   |
      | Adaptive Quiz Questions | truefalse | Q24  | Question 24 (difficulty 3).   | True   |
      | Adaptive Quiz Questions | truefalse | Q25  | Question 25 (difficulty 3).   | True   |
      | Adaptive Quiz Questions | truefalse | Q26  | Question 26 (difficulty 3).   | True   |
      | Adaptive Quiz Questions | truefalse | Q27  | Question 27 (difficulty 3).   | True   |
      | Adaptive Quiz Questions | truefalse | Q28  | Question 28 (difficulty 3).   | True   |
      | Adaptive Quiz Questions | truefalse | Q29  | Question 29 (difficulty 4).   | True   |
      | Adaptive Quiz Questions | truefalse | Q30  | Question 30 (difficulty 4).   | True   |
      | Adaptive Quiz Questions | truefalse | Q31  | Question 31 (difficulty 4).   | True   |
      | Adaptive Quiz Questions | truefalse | Q32  | Question 32 (difficulty 4).   | True   |
      | Adaptive Quiz Questions | truefalse | Q33  | Question 33 (difficulty 4).   | True   |
      | Adaptive Quiz Questions | truefalse | Q34  | Question 34 (difficulty 4).   | True   |
      | Adaptive Quiz Questions | truefalse | Q35  | Question 35 (difficulty 4).   | True   |
      | Adaptive Quiz Questions | truefalse | Q36  | Question 36 (difficulty 4).   | True   |
      | Adaptive Quiz Questions | truefalse | Q37  | Question 37 (difficulty 5).   | True   |
      | Adaptive Quiz Questions | truefalse | Q38  | Question 38 (difficulty 5).   | True   |
      | Adaptive Quiz Questions | truefalse | Q39  | Question 39 (difficulty 5).   | True   |
      | Adaptive Quiz Questions | truefalse | Q40  | Question 40 (difficulty 5).   | True   |
      | Adaptive Quiz Questions | truefalse | Q41  | Question 41 (difficulty 5).   | True   |
      | Adaptive Quiz Questions | truefalse | Q42  | Question 42 (difficulty 6).   | True   |
      | Adaptive Quiz Questions | truefalse | Q43  | Question 43 (difficulty 6).   | True   |
      | Adaptive Quiz Questions | truefalse | Q44  | Question 44 (difficulty 6).   | True   |
      | Adaptive Quiz Questions | truefalse | Q45  | Question 45 (difficulty 6).   | True   |
      | Adaptive Quiz Questions | truefalse | Q46  | Question 46 (difficulty 6).   | True   |
      | Adaptive Quiz Questions | truefalse | Q47  | Question 47 (difficulty 7).   | True   |
      | Adaptive Quiz Questions | truefalse | Q48  | Question 48 (difficulty 7).   | True   |
      | Adaptive Quiz Questions | truefalse | Q49  | Question 49 (difficulty 7).   | True   |
      | Adaptive Quiz Questions | truefalse | Q50  | Question 50 (difficulty 7).   | True   |
      | Adaptive Quiz Questions | truefalse | Q51  | Question 51 (difficulty 7).   | True   |
      | Adaptive Quiz Questions | truefalse | Q52  | Question 52 (difficulty 7).   | True   |
      | Adaptive Quiz Questions | truefalse | Q53  | Question 53 (difficulty 8).   | True   |
      | Adaptive Quiz Questions | truefalse | Q54  | Question 54 (difficulty 8).   | True   |
      | Adaptive Quiz Questions | truefalse | Q55  | Question 55 (difficulty 8).   | True   |
      | Adaptive Quiz Questions | truefalse | Q56  | Question 56 (difficulty 8).   | True   |
      | Adaptive Quiz Questions | truefalse | Q57  | Question 57 (difficulty 8).   | True   |
      | Adaptive Quiz Questions | truefalse | Q58  | Question 58 (difficulty 8).   | True   |
      | Adaptive Quiz Questions | truefalse | Q59  | Question 59 (difficulty 9).   | True   |
      | Adaptive Quiz Questions | truefalse | Q60  | Question 60 (difficulty 9).   | True   |
      | Adaptive Quiz Questions | truefalse | Q61  | Question 61 (difficulty 9).   | True   |
      | Adaptive Quiz Questions | truefalse | Q62  | Question 62 (difficulty 9).   | True   |
      | Adaptive Quiz Questions | truefalse | Q63  | Question 63 (difficulty 9).   | True   |
      | Adaptive Quiz Questions | truefalse | Q64  | Question 64 (difficulty 9).   | True   |
      | Adaptive Quiz Questions | truefalse | Q65  | Question 65 (difficulty 10).  | True   |
      | Adaptive Quiz Questions | truefalse | Q66  | Question 66 (difficulty 10).  | True   |
      | Adaptive Quiz Questions | truefalse | Q67  | Question 67 (difficulty 10).  | True   |
      | Adaptive Quiz Questions | truefalse | Q68  | Question 68 (difficulty 10).  | True   |
      | Adaptive Quiz Questions | truefalse | Q69  | Question 69 (difficulty 10).  | True   |
      | Adaptive Quiz Questions | truefalse | Q70  | Question 70 (difficulty 10).  | True   |
      | Adaptive Quiz Questions | truefalse | Q71  | Question 71 (difficulty 10).  | True   |
      | Adaptive Quiz Questions | truefalse | Q72  | Question 72 (difficulty 10).  | True   |
      | Adaptive Quiz Questions | truefalse | Q73  | Question 73 (difficulty 11).  | True   |
      | Adaptive Quiz Questions | truefalse | Q74  | Question 74 (difficulty 11).  | True   |
      | Adaptive Quiz Questions | truefalse | Q75  | Question 75 (difficulty 11).  | True   |
      | Adaptive Quiz Questions | truefalse | Q76  | Question 76 (difficulty 11).  | True   |
      | Adaptive Quiz Questions | truefalse | Q77  | Question 77 (difficulty 11).  | True   |
      | Adaptive Quiz Questions | truefalse | Q78  | Question 78 (difficulty 12).  | True   |
      | Adaptive Quiz Questions | truefalse | Q79  | Question 79 (difficulty 12).  | True   |
      | Adaptive Quiz Questions | truefalse | Q80  | Question 80 (difficulty 13).  | True   |
      | Adaptive Quiz Questions | truefalse | Q81  | Question 81 (difficulty 13).  | True   |
      | Adaptive Quiz Questions | truefalse | Q82  | Question 82 (difficulty 13).  | True   |
      | Adaptive Quiz Questions | truefalse | Q83  | Question 83 (difficulty 13).  | True   |
      | Adaptive Quiz Questions | truefalse | Q84  | Question 84 (difficulty 14).  | True   |
      | Adaptive Quiz Questions | truefalse | Q85  | Question 85 (difficulty 14).  | True   |
      | Adaptive Quiz Questions | truefalse | Q86  | Question 86 (difficulty 14).  | True   |
      | Adaptive Quiz Questions | truefalse | Q87  | Question 87 (difficulty 14).  | True   |
      | Adaptive Quiz Questions | truefalse | Q88  | Question 88 (difficulty 15).  | True   |
      | Adaptive Quiz Questions | truefalse | Q89  | Question 89 (difficulty 15).  | True   |
      | Adaptive Quiz Questions | truefalse | Q90  | Question 90 (difficulty 15).  | True   |
      | Adaptive Quiz Questions | truefalse | Q91  | Question 91 (difficulty 15).  | True   |
      | Adaptive Quiz Questions | truefalse | Q92  | Question 92 (difficulty 15).  | True   |
      | Adaptive Quiz Questions | truefalse | Q93  | Question 93 (difficulty 15).  | True   |
      | Adaptive Quiz Questions | truefalse | Q94  | Question 94 (difficulty 15).  | True   |
      | Adaptive Quiz Questions | truefalse | Q95  | Question 95 (difficulty 15).  | True   |
      | Adaptive Quiz Questions | truefalse | Q96  | Question 96 (difficulty 15).  | True   |
      | Adaptive Quiz Questions | truefalse | Q97  | Question 97 (difficulty 15).  | True   |
      | Adaptive Quiz Questions | truefalse | Q98  | Question 98 (difficulty 15).  | True   |
      | Adaptive Quiz Questions | truefalse | Q99  | Question 99 (difficulty 15).  | True   |
      | Adaptive Quiz Questions | truefalse | Q100 | Question 100 (difficulty 15). | True   |
    And the following "core_question > Tags" exist:
      | question | tag     |
      | Q1       | adpq_1  |
      | Q2       | adpq_1  |
      | Q3       | adpq_1  |
      | Q4       | adpq_1  |
      | Q5       | adpq_1  |
      | Q6       | adpq_1  |
      | Q7       | adpq_1  |
      | Q8       | adpq_1  |
      | Q9       | adpq_1  |
      | Q10      | adpq_1  |
      | Q11      | adpq_1  |
      | Q12      | adpq_2  |
      | Q13      | adpq_2  |
      | Q14      | adpq_2  |
      | Q15      | adpq_2  |
      | Q16      | adpq_2  |
      | Q17      | adpq_2  |
      | Q18      | adpq_2  |
      | Q19      | adpq_2  |
      | Q20      | adpq_2  |
      | Q21      | adpq_3  |
      | Q22      | adpq_3  |
      | Q23      | adpq_3  |
      | Q24      | adpq_3  |
      | Q25      | adpq_3  |
      | Q26      | adpq_3  |
      | Q27      | adpq_3  |
      | Q28      | adpq_3  |
      | Q29      | adpq_4  |
      | Q30      | adpq_4  |
      | Q31      | adpq_4  |
      | Q32      | adpq_4  |
      | Q33      | adpq_4  |
      | Q34      | adpq_4  |
      | Q35      | adpq_4  |
      | Q36      | adpq_4  |
      | Q37      | adpq_5  |
      | Q38      | adpq_5  |
      | Q39      | adpq_5  |
      | Q40      | adpq_5  |
      | Q41      | adpq_5  |
      | Q42      | adpq_6  |
      | Q43      | adpq_6  |
      | Q44      | adpq_6  |
      | Q45      | adpq_6  |
      | Q46      | adpq_6  |
      | Q47      | adpq_7  |
      | Q48      | adpq_7  |
      | Q49      | adpq_7  |
      | Q50      | adpq_7  |
      | Q51      | adpq_7  |
      | Q52      | adpq_7  |
      | Q53      | adpq_8  |
      | Q54      | adpq_8  |
      | Q55      | adpq_8  |
      | Q56      | adpq_8  |
      | Q57      | adpq_8  |
      | Q58      | adpq_8  |
      | Q59      | adpq_9  |
      | Q60      | adpq_9  |
      | Q61      | adpq_9  |
      | Q62      | adpq_9  |
      | Q63      | adpq_9  |
      | Q64      | adpq_9  |
      | Q65      | adpq_10 |
      | Q66      | adpq_10 |
      | Q67      | adpq_10 |
      | Q68      | adpq_10 |
      | Q69      | adpq_10 |
      | Q70      | adpq_10 |
      | Q71      | adpq_10 |
      | Q72      | adpq_10 |
      | Q73      | adpq_11 |
      | Q74      | adpq_11 |
      | Q75      | adpq_11 |
      | Q76      | adpq_11 |
      | Q77      | adpq_11 |
      | Q78      | adpq_12 |
      | Q79      | adpq_12 |
      | Q80      | adpq_13 |
      | Q81      | adpq_13 |
      | Q82      | adpq_13 |
      | Q83      | adpq_13 |
      | Q84      | adpq_14 |
      | Q85      | adpq_14 |
      | Q86      | adpq_14 |
      | Q87      | adpq_14 |
      | Q88      | adpq_15 |
      | Q89      | adpq_15 |
      | Q90      | adpq_15 |
      | Q91      | adpq_15 |
      | Q92      | adpq_15 |
      | Q93      | adpq_15 |
      | Q94      | adpq_15 |
      | Q95      | adpq_15 |
      | Q96      | adpq_15 |
      | Q97      | adpq_15 |
      | Q98      | adpq_15 |
      | Q99      | adpq_15 |
      | Q100     | adpq_15 |

  @javascript
  Scenario: Unable to fetch a question for level 14
    Given the following "activity" exists:
      | activity          | adaptivequiz            |
      | idnumber          | adaptivequiz            |
      | course            | C1                      |
      | name              | Adaptive Quiz           |
      | startinglevel     | 6                       |
      | lowestlevel       | 1                       |
      | highestlevel      | 15                      |
      | minimumquestions  | 12                      |
      | maximumquestions  | 25                      |
      | standarderror     | 11                      |
      | questionpoolnamed | Adaptive Quiz Questions |
      | debuginfoenable   | 1                       |
    And the following "mod_adaptivequiz > links with question banks" exist:
      | adaptivequiz  | idnumber |
      | Adaptive Quiz | qbank1   |
    When I am on the "adaptivequiz" "Activity" page logged in as "manager"
    And I click on "Start attempt" "button"
    And I click on "True" "radio"
    And I press "Submit answer"
    Then I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -0.5877867    | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 0.7114963     | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 3.2764457     | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 6.5722826     | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 9.1372320     | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 12.4330689    | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 15.7289058    | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 19.0247427    | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 21.5896921    | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 24.8855290    | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 28.1813659    | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 30.7463153    | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 34.0421522    | 0.65828          | 3.82260    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 37.3379891    | 0.59161          | 3.58329    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 39.1297486    | 0.58387          | 3.62025    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 42.4255855    | 0.57735          | 3.75021    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 45.7214224    | 0.57177          | 3.86815    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 49.0172593    | 0.56695          | 3.97594    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 52.3130962    | 0.56273          | 4.07508    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 55.6089331    | 0.55902          | 4.16674    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria                     |
      | 57.4006926    | 0.55572          | 4.18029    | Unable to fetch a question for level 14 |

  @javascript
  Scenario: Calculated standard error of 11 is within the limits imposed by the activity 11
    Given the following "activity" exists:
      | activity          | adaptivequiz            |
      | idnumber          | adaptivequiz            |
      | course            | C1                      |
      | name              | Adaptive Quiz           |
      | startinglevel     | 6                       |
      | lowestlevel       | 1                       |
      | highestlevel      | 15                      |
      | minimumquestions  | 12                      |
      | maximumquestions  | 25                      |
      | standarderror     | 11                      |
      | questionpoolnamed | Adaptive Quiz Questions |
      | debuginfoenable   | 1                       |
    And the following "mod_adaptivequiz > links with question banks" exist:
      | adaptivequiz  | idnumber |
      | Adaptive Quiz | qbank1   |
    When I am on the "adaptivequiz" "Activity" page logged in as "manager"
    And I click on "Start attempt" "button"
    And I click on "False" "radio"
    And I press "Submit answer"
    Then I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -0.5877867    | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -3.1527361    | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -4.9444956    | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -5.8607863    | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -6.1484684    | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -6.1484684    | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -6.4361505    | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -6.4361505    | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -6.7238326    | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -6.7238326    | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -7.0115147    | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -7.0115147    | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -6.7238326    | 0.60093          | 0.29371    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -6.1360459    | 0.59161          | 0.47800    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -5.2197552    | 0.58387          | 0.66362    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -3.9204722    | 0.57735          | 0.85358    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -2.1287127    | 0.57177          | 1.05344    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 0.4362367     | 0.56695          | 1.27700    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 3.7320736     | 0.56273          | 1.51818    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 7.0279105     | 0.55902          | 1.73769    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 10.3237474    | 0.51235          | 1.65476    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 12.8886968    | 0.47871          | 1.56668    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 14.6804563    | 0.45316          | 1.46496    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 15.9797393    | 0.44909          | 1.55313    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria                                                             |
      | 17.7714988    | 0.44544          | 1.65532    | Calculated standard error of 11 is within the limits imposed by the activity 11 |

  @javascript
  Scenario: Maximum number of questions attempted, standard error is 11
    Given the following "activity" exists:
      | activity          | adaptivequiz            |
      | idnumber          | adaptivequiz            |
      | course            | C1                      |
      | name              | Adaptive Quiz           |
      | startinglevel     | 6                       |
      | lowestlevel       | 1                       |
      | highestlevel      | 15                      |
      | minimumquestions  | 12                      |
      | maximumquestions  | 25                      |
      | standarderror     | 2                       |
      | questionpoolnamed | Adaptive Quiz Questions |
      | debuginfoenable   | 1                       |
    And the following "mod_adaptivequiz > links with question banks" exist:
      | adaptivequiz  | idnumber |
      | Adaptive Quiz | qbank1   |
    When I am on the "adaptivequiz" "Activity" page logged in as "manager"
    And I click on "Start attempt" "button"
    And I click on "True" "radio"
    And I press "Submit answer"
    Then I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -0.5877867    | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 0.7114963     | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 0.9991784     | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 1.9154691     | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 2.2031512     | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 2.7909379     | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 3.7072286     | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 5.0065116     | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 5.9228023     | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 7.7145618     | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 10.2795112    | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 13.5753481    | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 16.8711850    | 0.65828          | 2.50176    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 20.1670219    | 0.59161          | 2.35679    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 22.7319713    | 0.58387          | 2.52707    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 26.0278082    | 0.57735          | 2.72535    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 29.3236451    | 0.57177          | 2.90358    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 32.6194820    | 0.56695          | 3.06496    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 35.9153189    | 0.56273          | 3.21204    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 39.2111558    | 0.51640          | 3.05917    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 41.7761052    | 0.51235          | 3.15249    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 45.0719421    | 0.50875          | 3.27250    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 48.3677790    | 0.50553          | 3.38388    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 51.6636159    | 0.50262          | 3.48765    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria                   |
      | 54.9594528    | 0.46829          | 3.35106    | Maximum number of questions attempted |

  @javascript
  Scenario: Unable to fetch a question for level 1
    Given the following "activity" exists:
      | activity          | adaptivequiz            |
      | idnumber          | adaptivequiz            |
      | course            | C1                      |
      | name              | Adaptive Quiz           |
      | startinglevel     | 6                       |
      | lowestlevel       | 1                       |
      | highestlevel      | 15                      |
      | minimumquestions  | 12                      |
      | maximumquestions  | 25                      |
      | standarderror     | 11                      |
      | questionpoolnamed | Adaptive Quiz Questions |
      | debuginfoenable   | 1                       |
    And the following "mod_adaptivequiz > links with question banks" exist:
      | adaptivequiz  | idnumber |
      | Adaptive Quiz | qbank1   |
    When I am on the "adaptivequiz" "Activity" page logged in as "manager"
    And I click on "Start attempt" "button"
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -0.5877867    |	0.00000	         | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -3.1527361    |	0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -4.9444956    |	0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -7.5094450    |	0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      |	-10.8052819   |	0.00000	         | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -14.1011188   |	0.00000     	 | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -16.6660682   |	0.00000	         | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -19.9619051   |	0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -22.5268545   |	0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -25.8226914   |	0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -29.1185283   |	0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -32.4143652   |	0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -35.7102021   |	0.65828	         | -3.95091   | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -39.0060390   |	0.65134	         | -4.08543   | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -42.3018759   | 	0.64550	     | -4.20642   | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -45.5977128   |	0.64051	         | -4.31619   | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -48.8935497   |	0.63621	         | -4.41654   | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria                    |
      | -51.4584991   |	0.63246	         | -4.46824   | Unable to fetch a question for level 1 |

  @javascript
  Scenario: Maximum number of questions attempted, standard error is 7
    Given the following "activity" exists:
      | activity          | adaptivequiz            |
      | idnumber          | adaptivequiz            |
      | course            | C1                      |
      | name              | Adaptive Quiz           |
      | startinglevel     | 6                       |
      | lowestlevel       | 1                       |
      | highestlevel      | 15                      |
      | minimumquestions  | 12                      |
      | maximumquestions  | 25                      |
      | standarderror     | 7                       |
      | questionpoolnamed | Adaptive Quiz Questions |
      | debuginfoenable   | 1                       |
    And the following "mod_adaptivequiz > links with question banks" exist:
      | adaptivequiz  | idnumber |
      | Adaptive Quiz | qbank1   |
    When I am on the "adaptivequiz" "Activity" page logged in as "manager"
    And I click on "Start attempt" "button"
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -0.5877867	  | 0.00000          |	0.00000   | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 0.7114963     | 0.00000          |	0.00000   | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 0.9991784	  | 0.00000          |	0.00000   | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 1.9154691	  | 0.00000          |	0.00000   | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 2.2031512	  | 0.00000          |	0.00000   | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 2.2031512	  | 0.00000          |	0.00000   | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 1.9154691	  | 0.00000          |	0.00000   | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 1.9154691	  | 0.00000          |	0.00000   | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 1.6277870	  | 0.00000          |	0.00000   | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 1.6277870	  | 0.00000          |	0.00000   | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 1.9154691	  | 0.00000          |	0.00000   | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 2.5032558	  | 0.00000          |	0.00000   | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 3.4195465	  | 0.57009          |	0.73305   | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 4.7188295	  | 0.54006          |	0.62474   | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 5.6351202	  | 0.51755          |	0.50921   | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 6.2229069	  | 0.50395          |	0.64025   | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 7.1391976	  | 0.49281          | 0.77663    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 8.9309571	  | 0.48349          | 0.94815    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 11.4959065	  | 0.47559          | 1.14404    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 14.7917434	  | 0.46881          | 1.35863    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 18.0875803	  | 0.46291          | 1.55446    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 21.3834172	  | 0.44320          | 1.53159    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 23.9483666	  | 0.43780          | 1.66984    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 27.2442035	  | 0.42164          | 1.64600    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria                   |
      | 29.8091529	  | 0.40825          | 1.59783    | Maximum number of questions attempted |

  @javascript
  Scenario: Maximum number of questions attempted - 50
    Given the following "activity" exists:
      | activity          | adaptivequiz            |
      | idnumber          | adaptivequiz            |
      | course            | C1                      |
      | name              | Adaptive Quiz           |
      | startinglevel     | 10                      |
      | lowestlevel       | 1                       |
      | highestlevel      | 15                      |
      | minimumquestions  | 25                      |
      | maximumquestions  | 50                      |
      | standarderror     | 7                       |
      | questionpoolnamed | Adaptive Quiz Questions |
      | debuginfoenable   | 1                       |
    And the following "mod_adaptivequiz > links with question banks" exist:
      | adaptivequiz  | idnumber |
      | Adaptive Quiz | qbank1   |
    When I am on the "adaptivequiz" "Activity" page logged in as "manager"
    And I click on "Start attempt" "button"
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 0.5877867	  | 0.00000          |	0.00000   | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -0.7114963	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -3.2764457	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -5.0682052	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -6.3674882	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -7.2837789	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -8.5830619	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -9.4993526	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -10.0871393	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -11.0034300	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -12.3027130	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -13.2190037	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -13.8067904	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -14.0944725	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -14.0944725	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -13.8067904	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -13.8067904	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -14.0944725	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -14.0944725	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -13.8067904	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -13.8067904	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -13.5191083	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -12.9313216	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -12.0150309	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -10.7157479	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -8.9239884	  | 0.42492          | 0.46770    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -6.3590390	  | 0.40825          | 0.45763    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -4.5672795	  | 0.40465          | 0.58410    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -2.0023301	  | 0.40139          | 0.72946    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 1.2935068	  | 0.38730          | 0.73626    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 3.8584562	  | 0.38421          | 0.86640    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 7.1542931	  | 0.38139          | 1.01203    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 10.4501300	  | 0.37879          | 1.14958    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 13.7459669	  | 0.37639          | 1.27976    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 17.0418038	  | 0.36411          | 1.26707    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 19.6067532	  | 0.35355          | 1.23778    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 21.3985127	  | 0.35119          | 1.31231    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 24.6943496	  | 0.34899          | 1.42304    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 27.9901865	  | 0.34694          |	1.52863   | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 31.2860234	  | 0.33758          |	1.51304   | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 33.0777829	  | 0.32934          |	1.46355   | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 34.3770659	  | 0.32733          |	1.51165   | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 37.6729028	  | 0.32544          |	1.60435   | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 40.9687397	  | 0.32367          |	1.69325   | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 44.2645766	  | 0.31623          |	1.67680   | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 45.1808673	  | 0.30957          |	1.61080   | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 45.7686540	  | 0.30783          |	1.63520   | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 46.6849447	  | 0.30180          |	1.57338   | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 47.2727314	  | 0.29633          |	1.50837   | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria                   |
      | 47.5604135	  | 0.29463          |	1.52657   | Maximum number of questions attempted |

  @javascript
  Scenario: Calculated standard error of 8 is within the limits imposed by the activity 8
    Given the following "activity" exists:
      | activity          | adaptivequiz            |
      | idnumber          | adaptivequiz            |
      | course            | C1                      |
      | name              | Adaptive Quiz           |
      | startinglevel     | 10                      |
      | lowestlevel       | 1                       |
      | highestlevel      | 15                      |
      | minimumquestions  | 25                      |
      | maximumquestions  | 60                      |
      | standarderror     | 8                       |
      | questionpoolnamed | Adaptive Quiz Questions |
      | debuginfoenable   | 1                       |
    And the following "mod_adaptivequiz > links with question banks" exist:
      | adaptivequiz  | idnumber |
      | Adaptive Quiz | qbank1   |
    When I am on the "adaptivequiz" "Activity" page logged in as "manager"
    And I click on "Start attempt" "button"
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 0.5877867  	  | 0.00000	         | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 3.1527361	  | 0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 6.4485730     | 0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 9.0135224 	  | 0.00000	         | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 10.8052819	  | 0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 13.3702313	  | 0.00000	         | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 15.1619908	  | 0.00000	         | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 17.7269402	  | 0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 21.0227771	  | 0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 22.8145366    | 0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 24.1138196	  | 0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 25.0301103	  | 0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 25.6178970	  | 0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 25.9055791	  | 0.00000	         | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 25.9055791	  | 0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 26.1932612	  | 0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 26.1932612 	  | 0.00000	       | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 25.9055791	  | 0.00000	         | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 25.3177924	  | 0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 25.0301103	  | 0.00000	         | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 24.4423236	  | 0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 24.1546415	  | 0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 23.5668548 	  | 0.00000	         | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 22.6505641	  | 0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 22.0627774	  | 0.00000	         | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 21.1464867	  | 0.40311	         | 0.34332    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 20.5587000	  | 0.39853	         | 0.23081    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 19.6424093	  | 0.39441	         | 0.11373    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 18.3431263	  | 0.38271	         | 0.14005    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 17.4268356 	  | 0.37268	         | 0.17543    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 17.1391535	  | 0.36873	         | 0.09334    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 16.2228628	  | 0.36515          | -0.00386    | none               |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 14.9235798	  | 0.35626	         | 0.02145    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 14.6358977	  | 0.35291	         | -0.04911    | none               |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 13.3366147	  | 0.34503	         | -0.02442    | none               |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 13.0489326	  | 0.33806	         | 0.02600    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 13.0489326	  | 0.33485	         | -0.03032   | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 11.7496496	  | 0.32856	         | -0.00925   | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 11.7496496	  | 0.32292	         | 0.04344    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria                                                           |
      | 12.0373317	  | 0.31782	         | 0.10026    | Calculated standard error of 8 is within the limits imposed by the activity 8 |

  @javascript
  Scenario: Maximum number of questions attempted - 80
    Given the following "activity" exists:
      | activity          | adaptivequiz            |
      | idnumber          | adaptivequiz            |
      | course            | C1                      |
      | name              | Adaptive Quiz           |
      | startinglevel     | 4                       |
      | lowestlevel       | 1                       |
      | highestlevel      | 15                      |
      | minimumquestions  | 75                      |
      | maximumquestions  | 80                      |
      | standarderror     | 5                       |
      | questionpoolnamed | Adaptive Quiz Questions |
      | debuginfoenable   | 1                       |
    And the following "mod_adaptivequiz > links with question banks" exist:
      | adaptivequiz  | idnumber |
      | Adaptive Quiz | qbank1   |
    When I am on the "adaptivequiz" "Activity" page logged in as "manager"
    And I click on "Start attempt" "button"
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -1.2992830	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      |	-0.7114963	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 1.0802632	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 3.6452126	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 6.9410495	  | 0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 9.5059989	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 11.2977584	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 13.8627078	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 15.6544673	  | 0.00000	         | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 16.9537503	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 18.7455098	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 20.0447928	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 22.6097422	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 23.5260329	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 26.8218698	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 27.7381605	|0.00000|	0.00000| none |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 31.0339974	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 31.9502881	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 35.2461250	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 36.1624157	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 36.7502024	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 37.6664931	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 38.2542798	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 41.5501167	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 42.1379034	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 42.4255855	  | 0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 43.0133722	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 43.3010543	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 43.8888410	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 44.1765231	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 44.1765231	  | 0.00000          |	0.00000   | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 43.8888410	  | 0.00000          |	0.00000   | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 43.8888410	  | 0.00000          |	0.00000   | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 43.6011589	  | 0.00000          |	0.00000   | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 43.6011589	  | 0.00000          | 0.00000   | none                 |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 43.3134768	  | 0.00000          | 0.00000   | none                 |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 43.3134768	  | 0.00000          |	0.00000   | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 43.0257947	  | 0.00000       	 | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 43.0257947	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 42.7381126	  | 0.00000	         | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 42.1503259	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 41.8626438	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 41.2748571	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 41.2748571	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 40.6870704	  | 0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 40.9747525	  | 0.00000	         | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 40.3869658	  | 0.00000          |	0.00000   | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 40.6746479	  | 0.00000          |	0.00000   | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 40.0868612	  | 0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 40.3745433	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 39.4582526	  | 0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 40.0460393	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 39.1297486	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 39.7175353	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 38.8012446	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 42.0970815	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 41.1807908	  | 0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 39.8815078	  | 0.00000	         | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 38.0897483	  | 0.00000          | 0.00000    |none                 |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 36.7904653	  | 0.00000          | 0.00000    |none                 |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 34.9987058	  | 0.00000	         |0.00000     |none                 |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 33.6994228	  | 0.00000          | 0.00000    |none                 |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 31.9076633	  | 0.00000          | 0.00000    |none                 |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 30.6083803	  | 0.00000	         | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 28.8166208	  | 0.00000	         | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 27.5173378	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 25.7255783	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 24.4262953	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 22.6345358	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 21.3352528	  | 0.00000	         | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 19.5434933	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 18.6272026	  | 0.00000	         | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 16.8354431	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 20.1312800	  | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 17.5663306	  | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 20.8621675	  | 0.23013          | 0.11628    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 18.2972181	  | 0.22840	         | 0.10757    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 21.5930550	  | 0.22675	         | 0.17418    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 24.8888919	  | 0.22547	         | 0.18830    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria                   |
      | 22.3239425	  | 0.22389	         | 0.17897    | Maximum number of questions attempted |

  @javascript
  Scenario: Unable to fetch a question for level 15, continued attempt
    Given the following "activity" exists:
      | activity          | adaptivequiz            |
      | idnumber          | adaptivequiz            |
      | course            | C1                      |
      | name              | Adaptive Quiz           |
      | startinglevel     | 6                       |
      | lowestlevel       | 1                       |
      | highestlevel      | 15                      |
      | minimumquestions  | 12                      |
      | maximumquestions  | 25                      |
      | standarderror     | 11                      |
      | questionpoolnamed | Adaptive Quiz Questions |
      | debuginfoenable   | 1                       |
    And the following "mod_adaptivequiz > links with question banks" exist:
      | adaptivequiz  | idnumber |
      | Adaptive Quiz | qbank1   |
    When I am on the "adaptivequiz" "Activity" page logged in as "manager"
    And I click on "Start attempt" "button"
    And I click on "True" "radio"
    And I press "Submit answer"
    Then I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | -0.5877867    | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 0.7114963     | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 3.2764457     | 0.00000          | 0.00000    | none                |
    And I click on "Adaptive Quiz" "link"
    And I click on "Start attempt" "button"
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 5.8413951     | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 7.6331546     | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 10.1981040    | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 13.4939409    | 0.00000          | 0.00000    | none                |
    And I click on "Adaptive Quiz" "link"
    And I click on "Start attempt" "button"
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 16.0588903    | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 17.8506498    | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 21.1464867    | 0.00000          | 0.00000    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 24.4423236    | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 26.2340831    | 0.00000          | 0.00000    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 29.5299200    | 0.65828          | 3.47551    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 32.8257569    | 0.59161          | 3.26099    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 34.6175164    | 0.58387          | 3.31944    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 37.9133533    | 0.57735          | 3.46820    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 41.2091902    | 0.57177          | 3.60273    | none                |
    And I click on "False" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 44.5050271    | 0.52623          | 3.42801    | none                |
    And I click on "Adaptive Quiz" "link"
    And I click on "Start attempt" "button"
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 47.0699765    | 0.52099          | 3.50699    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 50.3658134    | 0.51640          | 3.61690    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria |
      | 53.6616503    | 0.51235          | 3.71847    | none                |
    And I click on "True" "radio"
    And I press "Submit answer"
    And I should see the following debugging info for the attempt:
      | difficultysum | standarderrorraw | measureraw | attemptstopcriteria                     |
      | 56.9574872    | 0.50875          | 3.81275    | Unable to fetch a question for level 15 |
