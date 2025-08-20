@qtype @qtype_answersheet
Feature: Test creating a Answersheet question
  As a teacher
  In order to test my students
  I need to be able to create a Answersheet question

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

  @javascript
  Scenario: Create an Answersheet question
    When I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    And I add a "Answer sheet" question filling the form with:
      | Question name    | answersheet-001                           |
      | Question text    | What is the national langauge in France?  |
      | General feedback | The national langauge in France is French |
      | Default mark     | 1                                         |
      | Start numbering  | 2                                         |
    Then I should see "answersheet-001"
    # Checking that the next new question form displays user preferences settings.
    And I press "Create a new question ..."
    And I set the field "Answer sheet" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And the following fields match these values:
      | Start numbering | 1 |
