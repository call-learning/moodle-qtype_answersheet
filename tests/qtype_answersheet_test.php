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
use qtype_answersheet;
use question_bank;

/**
 * Unit tests for answersheet definition class.
 *
 * @package     qtype_answersheet
 * @copyright   2025 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class qtype_answersheet_test extends advanced_testcase {
    /** @var qtype_answersheet instance of the question type class to test. */
    protected $qtype;

    /**
     * Test name
     */
    public function test_name(): void {
        $this->assertEquals($this->qtype->name(), 'answersheet');
    }

    /**
     * Test analyse response
     */
    public function test_can_analyse_responses(): void {
        $this->assertTrue($this->qtype->can_analyse_responses());
    }

    /**
     * Test get_question_options
     */
    public function test_get_question_options(): void {
        $this->resetAfterTest();

        // Create a mock question
        $question = new \stdClass();
        $question->id = 1;

        // Mock database records
        $qdata = new \stdClass();
        $qdata->id = 1;
        $qdata->startnumbering = 1;

        $answers = [
            (object)['id' => 1, 'answer' => 'A', 'fraction' => 1],
            (object)['id' => 2, 'answer' => 'B', 'fraction' => 0],
        ];

        $extrafields = [
            (object)['answerid' => 1, 'value' => 'Option A'],
            (object)['answerid' => 2, 'value' => 'Option B'],
        ];

        $this->assertTrue($this->qtype->get_question_options($question));
    }

    /**
     * Test save_question_options
     */
    public function test_save_question_options(): void {
        $this->resetAfterTest();

        $formdata = new \stdClass();
        $formdata->id = 1;
        $formdata->startnumbering = 2;
        $formdata->answer = ['A', 'B'];
        $formdata->fraction = [1, 0];

        $result = $this->qtype->save_question_options($formdata);
        $this->assertNotEmpty($result);
    }

    /**
     * Test delete_question
     */
    public function test_delete_question(): void {
        $this->resetAfterTest();

        $questionid = 1;
        $contextid = 1;

        // This should not throw any exceptions
        $this->qtype->delete_question($questionid, $contextid);
        $this->assertTrue(true); // Assert that we reach this point
    }

    /**
     * Test get_random_guess_score
     */
    public function test_get_random_guess_score(): void {
        $questiondata = new \stdClass();
        $questiondata->options = new \stdClass();
        $questiondata->options->answers = [
            1 => (object)['fraction' => 1],
            2 => (object)['fraction' => 0],
            3 => (object)['fraction' => 0],
        ];

        $score = $this->qtype->get_random_guess_score($questiondata);
        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(1, $score);
    }

    /**
     * Test get_possible_responses
     */
    public function test_get_possible_responses(): void {
        $questiondata = new \stdClass();
        $questiondata->options = new \stdClass();
        $questiondata->options->answers = [
            1 => (object)['id' => 1, 'answer' => 'A', 'fraction' => 1],
            2 => (object)['id' => 2, 'answer' => 'B', 'fraction' => 0],
        ];

        $responses = $this->qtype->get_possible_responses($questiondata);
        $this->assertIsArray($responses);
    }

    /**
     * Test move_files
     */
    public function test_move_files(): void {
        $this->resetAfterTest();

        $questionid = 1;
        $oldcontextid = 1;
        $newcontextid = 2;

        // This should not throw any exceptions
        $this->qtype->move_files($questionid, $oldcontextid, $newcontextid);
        $this->assertTrue(true);
    }

    /**
     * Test export_to_xml
     */
    public function test_export_to_xml(): void {
        $question = new \stdClass();
        $question->questiontext = 'Test question';
        $question->options = new \stdClass();
        $question->options->answers = [];

        $format = new \stdClass();
        $extra = null;

        $xml = $this->qtype->export_to_xml($question, $format, $extra);
        $this->assertIsString($xml);
    }

    /**
     * Test import_from_xml
     */
    public function test_import_from_xml(): void {
        $data = ['#' => ['text' => 'Test question text']];
        $question = new \stdClass();
        $format = new \stdClass();
        $extra = null;

        $result = $this->qtype->import_from_xml($data, $question, $format, $extra);
        $this->assertIsBool($result);
    }

    /**
     * Test extra_question_fields
     */
    public function test_extra_question_fields(): void {
        $fields = $this->qtype->extra_question_fields();
        $this->assertIsArray($fields);
        $this->assertContains('qtype_answersheet_options', $fields);
        $this->assertContains('startnumbering', $fields);
    }

    /**
     * Test questionid_column_name
     */
    public function test_questionid_column_name(): void {
        $column = $this->qtype->questionid_column_name();
        $this->assertEquals('questionid', $column);
    }

    /**
     * Test is_manual_graded
     */
    public function test_is_manual_graded(): void {
        $this->assertFalse($this->qtype->is_manual_graded());
    }

    /**
     * Test response_file_areas
     */
    public function test_response_file_areas(): void {
        $areas = $this->qtype->response_file_areas();
        $this->assertIsArray($areas);
    }

    /**
     * Test is_real_question_type
     */
    public function test_is_real_question_type(): void {
        $this->assertTrue($this->qtype->is_real_question_type());
    }

    /**
     * Test get_question_options with missing data
     */
    public function test_get_question_options_missing_data(): void {
        $question = new \stdClass();
        $question->id = 999; // Non-existent question

        $result = $this->qtype->get_question_options($question);
        $this->assertTrue($result); // Should handle gracefully
    }

    /**
     * Test save_question_options with invalid data
     */
    public function test_save_question_options_invalid_data(): void {
        $this->resetAfterTest();

        $formdata = new \stdClass();
        $formdata->id = null; // Invalid ID

        $result = $this->qtype->save_question_options($formdata);
        $this->assertNotEmpty($result);
    }

    /**
     * Setup
     */
    protected function setUp(): void {
        parent::setUp();
        $this->qtype = question_bank::get_qtype('answersheet');
    }

    protected function tearDown(): void {
        $this->qtype = null;
        parent::tearDown();
    }
}
