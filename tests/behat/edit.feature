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
      | Test questions   | answersheet | answersheet-001 for editing | standard |

  @javascript @_switch_window
  Scenario: Edit a Answersheet question
    When I am on the "answersheet-001 for editing" "core_question > edit" page logged in as teacher
    And I set the following fields to these values:
      | Question name |  |
    And I press "id_submitbutton"
    And I should see "You must supply a value here."
    And I set the following fields to these values:
      | Question name | Edited answersheet-001 name |
    And I press "id_submitbutton"
    Then I should see "Edited answersheet-001 name"
    And I choose "Edit question" action for "Edited answersheet-001" in the question bank
    And I set the following fields to these values:
      | id_options_0_0    | B        |
      | input_id_module_0 | Module X |
    And I press "id_submitbutton"
    And I should see "Edited answersheet-001 name"
    And I choose "Preview" action for "Edited answersheet-001" in the question bank
    And I should see "This is an Answersheet question"
    # Set behaviour options
    And I set the following fields to these values:
      | behaviour | immediatefeedback |
    And I press "Save preview options and start again"
    And I click on "//input[@id='radio_checked_question_0_0_1']" "xpath"
    And I press "Submit and finish"
    And I should see "You have correctly selected 1"
    And I should see "The correct answer is: 1 -> B, 2 -> B, 1 -> Answer 1, 2 -> Answer 2, 1 -> Text 1, 2 -> Text 2"
