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

namespace qtype_answersheet\local\api\answersheet;

use advanced_testcase;
use context_module;
use qtype_answersheet\local\api\answersheet;
use qtype_answersheet\local\persistent\answersheet_answers;
use qtype_answersheet\local\persistent\answersheet_module;

/**
 * Test helper class for the answersheet onto image question type.
 *
 * @package     qtype_answersheet
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \qtype_answersheet\local\api\answersheet
 */
class answersheet_test extends advanced_testcase {
    /**
     * Test table structure for the answersheet API.
     */
    public function test_get_table_structure() {
        $columns = answersheet::get_table_structure();

        $this->assertIsArray($columns);
        $this->assertNotEmpty($columns);
        $this->assertArrayHasKey('column', $columns[0]);
        $this->assertArrayHasKey('type', $columns[0]);
    }

    /**
     * Test column structure for the answersheet API.
     */
    public function test_get_column_structure() {
        $columns = answersheet::get_column_structure();

        $this->assertIsArray($columns);
        $this->assertNotEmpty($columns);
        $this->assertArrayHasKey('column', $columns[0]);
        $this->assertArrayHasKey('type', $columns[0]);
    }

    /**
     * Test the get_data method of the answersheet API.
     */
    public function test_get_data() {
        $this->resetAfterTest();

        $this->setAdminUser();
        $coregenerator = $this->getDataGenerator();
        $questiongenerator = $coregenerator->get_plugin_generator('core_question');
        // Create a course with a quiz that embeds a question.
        $course = $coregenerator->create_course();
        $quiz = $coregenerator->create_module('quiz', ['course' => $course->id]);
        $quizcontext = context_module::instance($quiz->cmid);

        $cat = $questiongenerator->create_question_category(['contextid' => $quizcontext->id]);
        $question = $questiongenerator->create_question('answersheet', 'standard', ['category' => $cat->id]);

        $data = answersheet::get_data($question->id);

        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertCount(3, $data);
        $this->assertEquals(['Module 1', 'Module 2', 'Module 3'], array_column($data, 'modulename'));
        $this->assertEquals([1,2,3], array_column($data, 'type'));
    }
}
