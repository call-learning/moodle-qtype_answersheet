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

use advanced_testcase;
use context_module;
use qtype_answersheet_test_helper;
use question_attempt_step;
use question_bank;
use question_definition;
use question_state;
use question_test_helper;
use test_question_maker;

/**
 * Unit tests for the matching question definition class.
 *
 * @package     qtype_answersheet
 * @copyright   2025 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_answersheet_question_test extends advanced_testcase {
    /**
     * @var qtype_answersheet_test_helper
     */
    protected question_test_helper $testhelper;
    /**
     * @var question_definition $dd
     */
    protected question_definition $dd;

    /**
     * Test get summary
     */
    public function test_get_question_summary() {
        $this->assertEquals($this->testhelper::QUESTION_TEXT, $this->dd->get_question_summary());
    }

    /**
     * Test summarize response
     */
    public function test_summarise_response() {
        $this->dd->start_attempt(new question_attempt_step(), 1);

        $response = $this->get_right_responses();

        $this->assertEquals('1 -> 1, 2 -> 2, 3 -> Answer 1, 4 -> Answer 2, 5 -> Text 1, 6 -> Text 2',
            $this->dd->summarise_response($response));
    }

    /**
     * Get right responses for the restored question.
     *
     * @return array The expected response for the restored question.
     */
    private function get_right_responses() {
        return [
            'answer1' => 1, // Correct
            'answer2' => 2, // Correct
            'answer3' => 'Answer 1', // Correct
            'answer4' => 'Answer 2', // Correct
            'answer5' => 'Text 1', // Correct
            'answer6' => 'Text 2', // Correct
        ];
    }

    /**
     * Test clear wrong from response
     */
    public function test_clear_wrong_from_response() {
        $this->dd->start_attempt(new question_attempt_step(), 1);
        $response = $this->get_right_responses();
        $response['answer2'] = 1; // Wrong answer.
        // The first 1 is wrong..
        $this->assertCount(5,
            $this->dd->clear_wrong_from_response($response));
    }

    /**
     * Test num parts right
     */
    public function test_get_num_parts_right() {
        $this->dd->start_attempt(new question_attempt_step(), 1);
        $response = $this->get_right_responses();
        $response['answer3'] = 'answer 4'; // Wrong answer.

        $this->assertEquals([5, 6], $this->dd->get_num_parts_right($response));

        $response['answer4'] = 'answer 1'; // Wrong answer.
        $this->assertEquals([4, 6], $this->dd->get_num_parts_right($response));
    }

    /**
     * Test expected data
     */
    public function test_get_expected_data() {
        $this->resetAfterTest();

        $question = $this->create_real_question();
        $question->start_attempt(new question_attempt_step(), 1);
        $this->assertEquals(
            [
                'int',
                'int',
                'alpha',
                'alpha',
                'raw',
                'raw',
            ],
            array_values($question->get_expected_data())
        );
    }

    /**
     * Create a real question for testing.
     *
     * This method creates a question of type 'answersheet' in a quiz context.
     * This sets up all table necessary for the question to be fully functional
     *
     * @return question_definition The created question object.
     */
    protected function create_real_question() {
        $coregenerator = $this->getDataGenerator();
        // Create a course with a quiz that embeds a question.
        $course = $coregenerator->create_course();
        $quiz = $coregenerator->create_module('quiz', ['course' => $course->id]);
        $quizcontext = context_module::instance($quiz->cmid);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category(['contextid' => $quizcontext->id]);
        $question = $questiongenerator->create_question('answersheet', 'standard', ['category' => $cat->id]);
        $question = @question_bank::load_question($question->id);
        return $question;
    }

    /**
     * Test correct response
     */
    public function test_get_correct_response() {
        $question = $this->create_real_question();
        $question->start_attempt(new question_attempt_step(), 1);
        $response = $this->get_right_responses();
        $this->assertEquals(
            array_values($response),
            array_values(
                $question->get_correct_response()
            )
        );
    }

    /**
     * Test is same response
     */
    public function test_is_same_response() {
        $this->dd->start_attempt(new question_attempt_step(), 1);

        $response = $this->get_right_responses();
        $emptyresponse = array_fill_keys(
            array_keys($response),
            ''
        );
        // Is an empty response same ''.
        $this->assertTrue(
            $this->dd->is_same_response(
                array(),
                $emptyresponse)
        );

        $emptybutone = $response;
        $emptybutone[array_keys($response)[0]] = '';

        $this->assertFalse(
            $this->dd->is_same_response(
                array(),
                $emptybutone));

        $this->assertTrue(
            $this->dd->is_same_response(
                $response,
                $response));

        $differentresponse = $response;
        $differentresponse[array_keys($response)[1]] = 'Wrong answer 5';
        $this->assertFalse(
            $this->dd->is_same_response(
                $response,
                $differentresponse)
        );
    }

    /**
     * Test is complete response
     */
    public function test_is_complete_response() {
        $dd = test_question_maker::make_question('answersheet');
        $dd->start_attempt(new question_attempt_step(), 1);
        $response = $this->get_right_responses();
        $firstoneempty[1] = '';
        $this->assertFalse($dd->is_complete_response([]));
        $this->assertFalse($dd->is_complete_response($firstoneempty));
        $this->assertTrue($dd->is_complete_response($response));
        $oneresponse = array_splice($response, 0, 1);
        $this->assertFalse($dd->is_complete_response($oneresponse));
    }

    /**
     * Test is gradable response
     */
    public function test_is_gradable_response() {
        $dd = test_question_maker::make_question('answersheet');
        $dd->start_attempt(new question_attempt_step(), 1);

        $fullresponse = $this->get_right_responses();
        $firstoneempty = array_splice($fullresponse, 1);
        $emptyresponse = array_fill_keys(
            array_keys($fullresponse),
            ''
        );
        $this->assertTrue($dd->is_gradable_response($fullresponse));
        $this->assertFalse($dd->is_gradable_response(array()));
        $this->assertFalse($dd->is_gradable_response($emptyresponse));
        $this->assertTrue($dd->is_gradable_response($firstoneempty));
        $oneresponse = array_splice($fullresponse, 0, 1);;
        $this->assertTrue($dd->is_gradable_response($oneresponse));

    }

    /**
     * Test grading
     */
    public function test_grading() {
        $dd = test_question_maker::make_question('answersheet');
        $dd->start_attempt(new question_attempt_step(), 1);
        $fullresponse = $this->get_right_responses();
        $this->assertEquals(
            [1, question_state::$gradedright],
            $dd->grade_response($fullresponse)
        );
        $oneresponse = $fullresponse;
        $oneresponse[array_keys($fullresponse)[0]] = 'Z'; // Wrong
        $oneresponse[array_keys($fullresponse)[1]] = 'Z'; // Wrong
        $oneresponse[array_keys($fullresponse)[3]] = 'Z'; // Wrong
        $this->assertEquals(
            [0.5, question_state::$gradedpartial],
            $dd->grade_response($oneresponse)
        );
        $emptyresponse = array_fill_keys(
            array_keys($fullresponse),
            'AA'
        );
        $this->assertEquals(
            [0, question_state::$gradedwrong],
            $dd->grade_response($emptyresponse)
        );
    }

    /**
     * Set up the test environment.
     */
    protected function setUp(): void {
        global $CFG;
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
        require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
        require_once($CFG->dirroot . '/question/type/answersheet/tests/helper.php');
        // Ensure the question type is loaded.
        $this->testhelper = test_question_maker::get_test_helper('answersheet');
        $this->dd = test_question_maker::make_question('answersheet');
    }
}