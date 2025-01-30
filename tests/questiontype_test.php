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

/**
 * Unit tests for the answersheet question definition class.
 *
 * @package     qtype_answersheet
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/answersheet/tests/helper.php');

/**
 * Unit tests for answersheet definition class.
 *
 * @package     qtype_answersheet
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_answersheet_test extends basic_testcase {
    /** @var qtype_answersheet instance of the question type class to test. */
    protected $qtype;

    /**
     * Test name
     */
    public function test_name() {
        $this->assertEquals($this->qtype->name(), 'answersheet');
    }

    /**
     * Test analyse response
     */
    public function test_can_analyse_responses() {
        $this->assertTrue($this->qtype->can_analyse_responses());
    }

    /**
     * Setup
     */
    protected function setUp(): void {
        $this->qtype = question_bank::get_qtype('answersheet');
    }

    protected function tearDown(): void {
        $this->qtype = null;
    }
}
