@qtype @qtype_answersheet
Feature: Preview a Answersheet question
  As a teacher
  In order to check my Answersheet questions will work for students
  I need to preview them

  Background:
    Given the following "users" exist:
      | username |
      | teacher  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype       | name            | template |
      | Test questions   | answersheet | answersheet-001 | standard |

  @javascript @_switch_window
  Scenario: Preview a Answersheet question with correct answer
    When I am on the "answersheet-001" "core_question > preview" page logged in as teacher
    And I should see "This is an Answersheet question"
    # Set behaviour options
    And I set the following fields to these values:
      | behaviour | immediatefeedback |
    And I press "Save preview options and start again"
    And I click on "//input[@id='radio_checked_question_0_0_1']" "xpath"
    And I click on "//input[@id='radio_checked_question_0_1_2']" "xpath"
    And I set the field with xpath "//input[@id='letter_by_letter_question_1_0_1']" to "A"
    And I set the field with xpath "//input[@id='letter_by_letter_question_1_0_2']" to "N"
    And I set the field with xpath "//input[@id='letter_by_letter_question_1_0_3']" to "S"
    And I set the field with xpath "//input[@id='letter_by_letter_question_1_0_4']" to "W"
    And I set the field with xpath "//input[@id='letter_by_letter_question_1_1_1']" to "A"
    And I set the field with xpath "//input[@id='letter_by_letter_question_1_1_2']" to "N"
    And I set the field with xpath "//input[@id='letter_by_letter_question_1_1_3']" to "S"
    And I set the field with xpath "//input[@id='letter_by_letter_question_1_1_4']" to "W"
    And I set the field with xpath "//input[@id='freetext_question_2_0']" to "Text 1"
    And I set the field with xpath "//input[@id='freetext_question_2_1']" to "Text 2"
    And I press "Submit and finish"
    Then I should see "Your answer is correct."
    And I should see "The correct answer is: 1 -> A, 2 -> B, 1 -> Answer 1, 2 -> Answer 2, 1 -> Text 1, 2 -> Text 2"

  @javascript @_switch_window
  Scenario: Preview a Answersheet question with almost correct answer
    When I am on the "answersheet-001" "core_question > preview" page logged in as teacher
    And I should see "This is an Answersheet question"
    # Set behaviour options
    And I set the following fields to these values:
      | behaviour | immediatefeedback |
    And I press "Save preview options and start again"
    And I click on "//input[@id='radio_checked_question_0_0_1']" "xpath"
    And I set the field with xpath "//input[@id='letter_by_letter_question_1_0_1']" to "A"
    And I set the field with xpath "//input[@id='letter_by_letter_question_1_0_2']" to "N"
    And I set the field with xpath "//input[@id='letter_by_letter_question_1_0_3']" to "S"
    And I set the field with xpath "//input[@id='letter_by_letter_question_1_0_4']" to "W"
    And I set the field with xpath "//input[@id='letter_by_letter_question_1_1_1']" to "A"
    And I set the field with xpath "//input[@id='letter_by_letter_question_1_1_2']" to "N"
    And I set the field with xpath "//input[@id='letter_by_letter_question_1_1_3']" to "S"
    And I set the field with xpath "//input[@id='letter_by_letter_question_1_1_4']" to "W"
    And I set the field with xpath "//input[@id='freetext_question_2_0']" to "Text 1"
    And I set the field with xpath "//input[@id='freetext_question_2_1']" to "Text 2"
    And I press "Submit and finish"
    Then I should see "You have correctly selected 3."
    And I should see "The correct answer is: 1 -> A, 2 -> B, 1 -> Answer 1, 2 -> Answer 2, 1 -> Text 1, 2 -> Text 2"

  @javascript @_switch_window
  Scenario: Preview a Answersheet question with incorrect answer
    When I am on the "answersheet-001" "core_question > preview" page logged in as teacher
    And I should see "This is an Answersheet question"
    # Set behaviour options
    And I set the following fields to these values:
      | behaviour | immediatefeedback |
    And I press "Save preview options and start again"
    And I click on "//input[@id='radio_checked_question_0_0_2']" "xpath"
    And I set the field with xpath "//input[@id='freetext_question_2_0']" to "Text 3"
    And I press "Submit and finish"
    Then I should see "That is not right at all"
    And I should see "The correct answer is: 1 -> A, 2 -> B, 1 -> Answer 1, 2 -> Answer 2, 1 -> Text 1, 2 -> Text 2"
