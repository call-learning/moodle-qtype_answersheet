@qtype @qtype_answersheet
Feature: Test duplicating a quiz containing a Answersheet question
  As a teacher
  In order re-use my courses containing Answersheet questions
  I need to be able to backup and restore them

  Background:
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype       | name            | template |
      | Test questions   | answersheet | answersheet-001 | standard |
    And the following "activities" exist:
      | activity | name      | course | idnumber |
      | quiz     | Test quiz | C1     | quiz1    |
    And quiz "Test quiz" contains the following questions:
      | answersheet-001 | 1 |
    And the following config values are set as admin:
      | enableasyncbackup | 0 |

  @javascript
  Scenario: Backup and restore a course containing a Answersheet question
    When I am on the "Course 1" course page logged in as admin
    And I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name       | Course 2 |
      | Schema | Course short name | C2       |
    And I am on the "Course 2" "core_question > course question bank" page
    And I choose "Edit question" action for "answersheet-001" in the question bank
    Then the following fields match these values:
      | Question name    | answersheet-001                                         |
      | Question text    | <p><strong>This is an Answersheet question</strong></p> |
      | General feedback | General feedback for the question.                      |
      | Default mark     | 1                                                       |
      | Start numbering  | 2                                                       |
      | id_answer_0_0    | A                                                       |
      | id_answer_0_1    | B                                                       |
      | id_answer_1_0    | Answer 1                                                |
      | id_answer_1_1    | Answer 2                                                |
      | id_answer_2_0    | Text 1                                                  |
      | id_answer_2_1    | Text 2                                                  |