<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace qtype_answersheet;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');

use qtype_answersheet_test_helper;
use question_contains_tag_with_attributes;
use question_definition;
use question_hint_with_parts;
use question_state;
use question_test_helper;
use test_question_maker;

/**
 * Unit tests for the anwersheet question type.
 *
 * @package     qtype_answersheet
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class qtype_answersheet_walkthrough_test extends \qbehaviour_walkthrough_test_base {
    /**
     * @var qtype_answersheet_test_helper
     */
    protected question_test_helper $helper;

    /**
     * @var question_definition $dd
     */
    protected question_definition $dd;

    /**
     * Setup the test environment.
     */
    public function setUp(): void {
        global $CFG;
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
        require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
        require_once($CFG->dirroot . '/question/type/answersheet/tests/helper.php');
        $this->helper = test_question_maker::get_test_helper('answersheet');
        $this->dd = test_question_maker::make_question('answersheet');
    }

    /**
     * Test the question type name.
     */
    public function test_interactive_behaviour(): void {
        $this->dd->hints = [
            new question_hint_with_parts(13, 'This is the first hint.', FORMAT_HTML, false, false),
            new question_hint_with_parts(14, 'This is the second hint.', FORMAT_HTML, true, true),
        ];
        $this->start_attempt_at_question($this->dd, 'interactive', 6);

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);

        $this->check_current_output();

        $this->check_current_output(
            ...$this->get_all_input_expectation(),
        );
        $this->check_current_output(
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_tries_remaining_expectation(3),
            $this->get_no_hint_visible_expectation()
        );

        // Save the wrong answer.
        $fullwrong = $this->helper->get_full_wrong_machine_response($this->dd);

        $this->process_submission($fullwrong);
        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);

        $this->check_current_output(
            ...$this->get_all_input_expectation($fullwrong),
        );
        $this->check_current_output(
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_tries_remaining_expectation(3),
            $this->get_no_hint_visible_expectation()
        );
        // Submit the wrong answer.
        $this->process_submission(
            array_merge($fullwrong, ['-submit' => 1])
        );

        // Verify that the current mark is not set and we can submit again.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            ...$this->get_all_input_expectation($fullwrong),
        );
        $this->check_current_output(
            $this->get_does_not_contain_submit_button_expectation(),
            $this->get_contains_try_again_button_expectation(true),
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_contains_hint_expectation('This is the first hint')
        );
        // Do try again.
        $this->process_submission(['-tryagain' => 1]);

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);

        $this->check_current_output(
            ...$this->get_all_input_expectation($fullwrong),
        );
        $this->check_current_output(
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_tries_remaining_expectation(2),
            $this->get_no_hint_visible_expectation()
        );

        // Submit the right answer.
        $fullright = $this->helper->get_right_machine_response($this->dd);
        $this->process_submission(
            array_merge($fullright, ['-submit' => 1])
        );

        // Verify.
        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(4); // Penalty of 50%, 1 tries done, see adjust_fraction.
        $this->check_current_output(
            ...$this->get_all_input_expectation($fullright),
        );
        $this->check_current_output(
            $this->get_does_not_contain_submit_button_expectation(),
            $this->get_contains_correct_expectation(),
            $this->get_no_hint_visible_expectation()
        );

        // Check regrading does not mess anything up.
        $this->quba->regrade_all_questions();

        // Verify.
        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(4.0000002);
    }

    /**
     * Test deferred feedback
     */
    public function test_deferred_feedback(): void {
        $this->dd->hints = [
            new question_hint_with_parts(13, 'This is the first hint.', FORMAT_HTML, false, false),
            new question_hint_with_parts(14, 'This is the second hint.', FORMAT_HTML, true, true),
        ];
        $this->start_attempt_at_question($this->dd, 'deferredfeedback', 6);

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);

        $this->check_current_output(
            ...$this->get_all_input_expectation(),
        );
        $this->check_current_output(
            $this->get_does_not_contain_feedback_expectation()
        );

        // Save a partial answer.
        $fullright = $this->helper->get_right_machine_response($this->dd);
        $partialanswer = array_slice($fullright, 0, 3);
        $this->process_submission($partialanswer);
        // Verify.
        $this->check_current_state(question_state::$invalid);
        $this->check_current_mark(null);

        $this->check_current_output(
            ...$this->get_all_input_expectation($partialanswer),
        );
        $this->check_current_output(
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_does_not_contain_feedback_expectation()
        );
        // Save the right answer.
        $this->process_submission($fullright);

        // Verify.
        $this->check_current_state(question_state::$complete);
        $this->check_current_mark(null);
        $this->check_current_output(
            ...$this->get_all_input_expectation($fullright),
        );
        $this->check_current_output(
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_does_not_contain_feedback_expectation()
        );

        // Finish the attempt.
        $this->quba->finish_all_questions();

        // Verify.
        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(6);
        $this->check_current_output(
            ...$this->get_all_input_expectation($fullright),
        );
        $this->check_current_output(
            $this->get_contains_correct_expectation()
        );

        // Change the right answer a bit.
        $indexedanswers = array_values($this->dd->answers);
        $this->dd->answers[$indexedanswers[0]->id]->answer = 4;

        // Check regrading does not mess anything up.
        $this->quba->regrade_all_questions();

        // Verify.
        $this->check_current_state(question_state::$gradedpartial);
        $this->check_current_mark(5);
    }

    /**
     * Test deferred feedback unanswered
     */
    public function test_deferred_feedback_unanswered(): void {
        $this->dd->hints = [
            new question_hint_with_parts(13, 'This is the first hint.', FORMAT_HTML, false, false),
            new question_hint_with_parts(14, 'This is the second hint.', FORMAT_HTML, true, true),
        ];
        $this->start_attempt_at_question($this->dd, 'deferredfeedback', 6);

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            ...$this->get_all_input_expectation(),
        );
        $this->check_current_output(
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_does_not_contain_feedback_expectation()
        );
        $this->check_step_count(1);

        // Save a blank response.
        $fullright = $this->helper->get_right_machine_response($this->dd);
        $fullblank = array_map(function ($value) {
            return '';
        }, $fullright);
        $this->process_submission($fullblank);

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            ...$this->get_all_input_expectation(),
        );
        $this->check_current_output(
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_does_not_contain_feedback_expectation()
        );
        $this->check_step_count(1);

        // Finish the attempt.
        $this->quba->finish_all_questions();

        // Verify.
        $this->check_current_state(question_state::$gaveup);
        $this->check_current_mark(null);
        $this->check_current_output(
            ...$this->get_all_input_expectation(),
        );
    }

    /**
     * Test deferred feedback partial unanswered
     */
    public function test_deferred_feedback_partial_answer(): void {
        $this->dd->hints = [
            new question_hint_with_parts(13, 'This is the first hint.', FORMAT_HTML, false, false),
            new question_hint_with_parts(14, 'This is the second hint.', FORMAT_HTML, true, true),
        ];
        $this->start_attempt_at_question($this->dd, 'deferredfeedback', 6);

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            ...$this->get_all_input_expectation(),
        );
        $this->check_current_output(
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_does_not_contain_feedback_expectation()
        );

        // Save a partial answer.
        $fullright = $this->helper->get_right_machine_response($this->dd);
        $partialanswer = array_slice($fullright, 0, 3);
        $this->process_submission($partialanswer);

        // Verify.
        $this->check_current_state(question_state::$invalid);
        $this->check_current_mark(null);
        $this->check_current_output(
            ...$this->get_all_input_expectation($partialanswer),
        );
        $this->check_current_output(
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_does_not_contain_feedback_expectation()
        );

        // Finish the attempt.
        $this->quba->finish_all_questions();

        // Verify.
        $this->check_current_state(question_state::$gradedpartial);
        $this->check_current_mark(3); // 50% of the question is answered.
        $this->check_current_output(
            ...$this->get_all_input_expectation($partialanswer),
        );
        $this->check_current_output(
            $this->get_contains_partcorrect_expectation()
        );
    }

    /**
     * Test interactive grading
     */
    public function test_interactive_grading(): void {
        $this->dd->hints = [
            new question_hint_with_parts(13, 'This is the first hint.', FORMAT_HTML, true, true),
            new question_hint_with_parts(14, 'This is the second hint.', FORMAT_HTML, true, true),
        ];
        $this->dd->penalty = 0.3;
        $this->start_attempt_at_question($this->dd, 'interactive', 6);

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->assertEquals(
            'interactive',
            $this->quba->get_question_attempt($this->slot)->get_behaviour_name()
        );
        $this->check_current_output(
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_tries_remaining_expectation(3),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_no_hint_visible_expectation()
        );

        // Submit an response with the first two parts right.
        $fullright = $this->helper->get_right_machine_response($this->dd);
        $fullwrong = $this->helper->get_full_wrong_machine_response($this->dd);
        $partialright = array_merge(
            array_slice($fullright, 0, 3),
            array_slice($fullwrong, 3, 3),
            ['-submit' => 1]
        );
        $this->process_submission($partialright);

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            ...$this->get_all_input_expectation($partialright),
        );
        $this->check_current_output(
            $this->get_does_not_contain_submit_button_expectation(),
            $this->get_contains_try_again_button_expectation(true),
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_contains_hint_expectation('This is the first hint'),
            $this->get_contains_num_parts_correct(3),
            $this->get_contains_standard_partiallycorrect_combined_feedback_expectation()
        );

        // Do try again.
        // keys p3 and p4 are extra hidden fields to clear data.
        $fullblankkeys = $this->helper->get_answer_keys($this->dd);
        $fullblank = array_fill_keys(
            $fullblankkeys,
            '',
        );
        $fullblank = array_map(function ($value) {
            return '';
        }, $fullblank);

        $partialblanktryagain = array_merge(
            array_slice($fullright, 0, 3),
            array_slice($fullblank, 3, 3),
            ['-tryagain' => 1]
        );
        $this->process_submission($partialblanktryagain);

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            ...$this->get_all_input_expectation($partialblanktryagain),
        );
        $this->check_current_output(
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_try_again_button_expectation(),
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_tries_remaining_expectation(2),
            $this->get_no_hint_visible_expectation()
        );

        // Submit an response with the first and last parts right.
        $partialright = array_merge(
            array_slice($fullright, 0, 1),
            array_slice($fullblank, 1, 1),
            array_slice($fullright, 2, 4),
            ['-submit' => 1]
        );
        $this->process_submission($partialright);

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            ...$this->get_all_input_expectation($partialright),
        );
        $this->check_current_output(
            $this->get_does_not_contain_submit_button_expectation(),
            $this->get_contains_try_again_button_expectation(true),
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_contains_hint_expectation('This is the second hint'),
            $this->get_contains_num_parts_correct(5),
            $this->get_contains_standard_partiallycorrect_combined_feedback_expectation()
        );

        // Do try again.
        $this->process_submission($partialblanktryagain);

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            ...$this->get_all_input_expectation($partialblanktryagain),
        );
        $this->check_current_output(
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_try_again_button_expectation(),
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_tries_remaining_expectation(1),
            $this->get_no_hint_visible_expectation()
        );

        // Submit the right answer.
        $this->process_submission(array_merge($fullright, ['-submit' => 1]));

        // Verify.
        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(2.4);
        $this->check_current_output(
            ...$this->get_all_input_expectation($fullright),
        );
        $this->check_current_output(
            $this->get_does_not_contain_submit_button_expectation(),
            $this->get_does_not_contain_try_again_button_expectation(),
            $this->get_contains_correct_expectation(),
            $this->get_no_hint_visible_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_contains_standard_correct_combined_feedback_expectation()
        );
    }

    /**
     * Test interactive correct no submit
     */
    public function test_interactive_correct_no_submit(): void {
        $this->dd->hints = [
            new question_hint_with_parts(13, 'This is the first hint.', FORMAT_HTML, false, false),
            new question_hint_with_parts(14, 'This is the second hint.', FORMAT_HTML, true, true),
        ];

        $this->start_attempt_at_question($this->dd, 'interactive', 6);

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            ...$this->get_all_input_expectation(),
        );
        $this->check_current_output(
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_tries_remaining_expectation(3),
            $this->get_no_hint_visible_expectation()
        );

        // Save the right answer.
        $fullright = $this->helper->get_right_machine_response($this->dd);
        $this->process_submission($fullright);

        // Finish the attempt without clicking check.
        $this->quba->finish_all_questions();

        // Verify.
        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(6);
        $this->check_current_output(
            ...$this->get_all_input_expectation($fullright),
        );
        $this->check_current_output(
            $this->get_does_not_contain_submit_button_expectation(),
            $this->get_contains_correct_expectation(),
            $this->get_no_hint_visible_expectation()
        );

        // Check regrading does not mess anything up.
        $this->quba->regrade_all_questions();

        // Verify.
        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(6);
    }

    /**
     * Test interactive partial no submit
     */
    public function test_interactive_partial_no_submit(): void {
        $this->dd->hints = [
            new question_hint_with_parts(13, 'This is the first hint.', FORMAT_HTML, false, false),
            new question_hint_with_parts(14, 'This is the second hint.', FORMAT_HTML, true, true),
        ];

        $this->start_attempt_at_question($this->dd, 'interactive', 6);

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);

        $this->check_current_output(
            ...$this->get_all_input_expectation(),
        );
        $this->check_current_output(
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_tries_remaining_expectation(3),
            $this->get_no_hint_visible_expectation()
        );

        // Save the a partially right answer.
        $fullright = $this->helper->get_right_machine_response($this->dd);
        $fullwrong = $this->helper->get_full_wrong_machine_response($this->dd);
        $partialright = array_slice($fullright, 0, 3) + array_slice($fullwrong, 4, 2);
        $this->process_submission($partialright);

        // Finish the attempt without clicking check.
        $this->quba->finish_all_questions();

        // Verify.
        $this->check_current_state(question_state::$gradedpartial);
        $this->check_current_mark(3);

        $this->check_current_output(
            ...$this->get_all_input_expectation($partialright),
        );
        $this->check_current_output(
            $this->get_does_not_contain_submit_button_expectation(),
            $this->get_contains_partcorrect_expectation(),
            $this->get_no_hint_visible_expectation()
        );

        // Check regrading does not mess anything up.
        $this->quba->regrade_all_questions();

        // Verify.
        $this->check_current_state(question_state::$gradedpartial);
        $this->check_current_mark(3);
    }

    /**
     * Test interactive no right clears
     */
    public function test_interactive_no_right_clears(): void {
        $this->dd->hints = [
            new question_hint_with_parts(23, 'This is the first hint.', FORMAT_MOODLE, false, true),
            new question_hint_with_parts(24, 'This is the second hint.', FORMAT_MOODLE, true, true),
        ];

        $this->start_attempt_at_question($this->dd, 'interactive', 10);

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);

        $this->check_current_output(
            ...$this->get_all_input_expectation(),
        );
        $this->check_current_output(
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_tries_remaining_expectation(3),
            $this->get_no_hint_visible_expectation()
        );

        // Save the a completely wrong answer.
        $fullwrong = $this->helper->get_full_wrong_machine_response($this->dd);
        $this->process_submission($fullwrong + ['-submit' => 1]);

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            ...$this->get_all_input_expectation($fullwrong),
        );
        $this->check_current_output(
            $this->get_contains_marked_out_of_summary(),
            $this->get_does_not_contain_submit_button_expectation(),
            $this->get_contains_hint_expectation('This is the first hint')
        );

        // Do try again.
        $fullempty = $this->helper->get_right_machine_response($this->dd);
        $fullempty = array_map(function ($value) {
            return '';
        }, $fullempty);
        $this->process_submission($fullempty + ['-tryagain' => 1]);

        // Check that all the wrong answers have been cleared.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            ...$this->get_all_input_expectation($fullempty),
        );
        $this->check_current_output(
            $this->get_contains_marked_out_of_summary(),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_tries_remaining_expectation(2),
            $this->get_no_hint_visible_expectation()
        );
    }

    /**
     * Get answer expectation
     *
     * @param question_definition $dd
     * @param int $answerindex
     * @param mixed $value
     * @param bool $enabled
     * @param mixed $currentanswer
     * @return question_contains_tag_with_attributes
     */
    public function get_contains_radio_answer_expectation(
        question_definition $dd,
        int $answerindex,
        mixed $value,
        bool $enabled = true,
        mixed $currentanswer = null,
    ) {
        $fieldname = $this->quba->get_field_prefix($this->slot);
        $fieldname .= 'answer' . array_values($dd->answers)[$answerindex]->id;
        return $this->get_contains_radio_expectation(
            [
                'name' => $fieldname,
                'value' => $value,
            ],
            $enabled,
            $currentanswer == $value
        );
    }

    /**
     * Get answer expectation for input with letter answer
     *
     * @param question_definition $dd
     * @param int $answerindex
     * @param int $inputindex
     * @param mixed $currentanswer = null,
     * @return question_contains_tag_with_attributes
     */
    public function get_contains_input_answer_letter_expectation(
        question_definition $dd,
        int $answerindex,
        int $inputindex,
        mixed $currentanswer = null,
    ) {
        $expectedattributes = [
            'type' => 'text',
            'data-index' => $inputindex,
            'class' => "input letterinput",
        ];
        return new question_contains_tag_with_attributes('input', $expectedattributes, []);
    }

    /**
     * Get answer expectation for input with letter answer
     *
     * @param question_definition $dd
     * @param int $answerindex
     * @param mixed $currentanswer = null,
     * @return question_contains_tag_with_attributes
     */
    public function get_contains_input_answer_letter_hidden_expectation(
        question_definition $dd,
        int $answerindex,
        mixed $currentanswer = null,
    ) {
        $fieldname = $this->quba->get_field_prefix($this->slot);
        $fieldname .= 'answer' . array_values($dd->answers)[$answerindex]->id;
        $expectedattributes = [
            'name' => $fieldname,
            'type' => 'hidden',
        ];
        if (isset($currentanswerndex) && $currentanswer !== '') {
            $expectedattributes['value'] = $currentanswer;
        }
        return new question_contains_tag_with_attributes('input', $expectedattributes, []);
    }
    /**
     * Get answer expectation for input with text answer
     *
     * @param question_definition $dd
     * @param int $answerindex
     * @param mixed $currentanswer
     * @return question_contains_tag_with_attributes
     */
    public function get_contains_input_answer_text_expectation(
        question_definition $dd,
        int $answerindex,
        mixed $currentanswer = null,
    ) {
        $fieldname = $this->quba->get_field_prefix($this->slot);
        $fieldname .= 'answer' . array_values($dd->answers)[$answerindex]->id;
        $expectedattributes = [
            'name' => $fieldname,
            'type' => 'text',
        ];
        if (isset($currentanswer) && $currentanswer !== '') {
            $expectedattributes['value'] = $currentanswer;
        }
        return new question_contains_tag_with_attributes('input', $expectedattributes, []);
    }

    /**
     * Get all input expectations for the "standard" answersheet question version.
     *
     * The question contains 2 radio buttons, 2 letter by letter inputs, and 2 text inputs.
     *
     * @param array $currentanswer
     * @return array
     */
    private function get_all_input_expectation(array $currentanswer = []): array {
        $answerkeys = $this->helper->get_answer_keys($this->dd);
        $currentanswers = array_map(function ($key) use ($currentanswer) {
            return $currentanswer[$key] ?? null;
        }, $answerkeys);
        $currentanswer = array_values($currentanswers); // We can index from 0;
        return [
            // This is the first set of radio buttons.
            $this->get_contains_radio_answer_expectation($this->dd, 0, 1, currentanswer: $currentanswer[0]),
            $this->get_contains_radio_answer_expectation($this->dd, 0, 2, currentanswer: $currentanswer[0]),
            $this->get_contains_radio_answer_expectation($this->dd, 0, 3, currentanswer: $currentanswer[0]),
            $this->get_contains_radio_answer_expectation($this->dd, 0, 4, currentanswer: $currentanswer[0]),
            // Second radio button is there and has possible values from 1 to 4.
            $this->get_contains_radio_answer_expectation($this->dd, 1, 1, currentanswer: $currentanswer[1]),
            $this->get_contains_radio_answer_expectation($this->dd, 1, 2, currentanswer: $currentanswer[1]),
            $this->get_contains_radio_answer_expectation($this->dd, 1, 3, currentanswer: $currentanswer[1]),
            $this->get_contains_radio_answer_expectation($this->dd, 1, 4, currentanswer: $currentanswer[1]),
            // Now check that we have a set of input called letter input (4).
            $this->get_contains_input_answer_letter_expectation($this->dd, 2, 1, currentanswer: $currentanswer[2]),
            $this->get_contains_input_answer_letter_expectation($this->dd, 2, 2, currentanswer: $currentanswer[2]),
            $this->get_contains_input_answer_letter_expectation($this->dd, 2, 3, currentanswer: $currentanswer[2]),
            $this->get_contains_input_answer_letter_expectation($this->dd, 2, 4, currentanswer: $currentanswer[2]),
            $this->get_contains_input_answer_letter_hidden_expectation($this->dd, 2, currentanswer: $currentanswer[2]),
            // Second letter by letter input.
            $this->get_contains_input_answer_letter_expectation($this->dd, 3, 1, currentanswer: $currentanswer[3]),
            $this->get_contains_input_answer_letter_expectation($this->dd, 3, 2, currentanswer: $currentanswer[3]),
            $this->get_contains_input_answer_letter_expectation($this->dd, 3, 3, currentanswer: $currentanswer[3]),
            $this->get_contains_input_answer_letter_expectation($this->dd, 3, 4, currentanswer: $currentanswer[3]),
            $this->get_contains_input_answer_letter_hidden_expectation($this->dd, 3, currentanswer: $currentanswer[3]),
            // Next is to check for the other input text.
            $this->get_contains_input_answer_text_expectation($this->dd, 4, currentanswer: $currentanswer[4]),
            $this->get_contains_input_answer_text_expectation($this->dd, 5, currentanswer: $currentanswer[5]),
        ];
    }
}
