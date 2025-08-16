@qtype @qtype_answersheet
Feature: Test editing a Answersheet question
  As a teacher
  In order to be able to update my Answersheet question
  I need to edit them

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
      | questioncategory | qtype       | name                        | template |
      | Test questions   | answersheet | answersheet-001 for editing | frogtoad |

  @javascript @_switch_window
  Scenario: Edit a Answersheet question
    When I am on the "answersheet-001 for editing" "core_question > edit" page logged in as teacher
    And I set the following fields to these values:
      | Question name | |
    And I press "id_submitbutton"
    And I should see "You must supply a value here."
    And I set the following fields to these values:
      | Question name | Edited answersheet-001 name |
    And I press "id_submitbutton"
    Then I should see "Edited answersheet-001 name"
    And I choose "Edit question" action for "Edited answersheet-001" in the question bank
    And I set the following fields to these values:
      | id_answer_1          | newt                       |
      | id_fraction_1        | 70%                        |
      | id_feedback_1        | Newt is an OK good answer. |
    And I press "id_submitbutton"
    And I should see "Edited answersheet-001 name"
    And I choose "Preview" action for "Edited answersheet-001" in the question bank
    And I should see "Name an amphibian:"
    # Set behaviour options
    And I set the following fields to these values:
      | behaviour | immediatefeedback |
    And I press "Save preview options and start again"
    And I set the field with xpath "//div[@class='qtext']//input[contains(@id, '1_answer')]" to "newt"
    And I press "Check"
    And I should see "Newt is an OK good answer."
    And I should see "Generalfeedback: frog or toad would have been OK."
    And I should see "The correct answer is: frog"
