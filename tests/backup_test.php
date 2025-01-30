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
 * Tests for the answersheet question type backup and restore logic.
 *
 * @package   qtype_answersheet
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/course/externallib.php');

/**
 * Tests for the question type backup and restore logic.
 */
class qtype_answersheet_backup_testcase extends advanced_testcase {

    /**
     * Duplicate quiz with a multichoice grid question, and check it worked.
     */
    public function test_duplicate_match_question() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $coregenerator = $this->getDataGenerator();
        $questiongenerator = $coregenerator->get_plugin_generator('core_question');

        // Create a course with a page that embeds a question.
        $course = $coregenerator->create_course();
        $quiz = $coregenerator->create_module('quiz', ['course' => $course->id]);
        $quizcontext = context_module::instance($quiz->cmid);

        $cat = $questiongenerator->create_question_category(['contextid' => $quizcontext->id]);
        $question = $questiongenerator->create_question('answersheet', 'ten', ['category' => $cat->id]);

        // Store some counts.
        $numquizzes = count(get_fast_modinfo($course)->instances['quiz']);
        $nummatchquestions = $DB->count_records('question', ['qtype' => 'answersheet']);

        // Duplicate the page.
        duplicate_module($course, get_fast_modinfo($course)->get_cm($quiz->cmid));

        // Verify the copied quiz exists.
        $this->assertCount($numquizzes + 1, get_fast_modinfo($course)->instances['quiz']);

        // Verify the copied question.
        $this->assertEquals($nummatchquestions + 1, $DB->count_records('question', ['qtype' => 'answersheet']));
        $newmatchid = $DB->get_field_sql("
                SELECT MAX(id)
                  FROM {question}
                 WHERE qtype = ?
                ", ['answersheet']);
        /* @var question_definition $multichoicedata */
        $multichoicedata = question_bank::load_question_data($newmatchid);

        $this->assertNotEmpty($multichoicedata->options->answers);
        $this->assertCount(10, $multichoicedata->options->answers);
        $this->assertEquals(1, $multichoicedata->options->startnumbering);
        $this->assertEquals(2, \qtype_answersheet\answersheet_parts::count_records(['questionid' => $newmatchid]));
        $this->assertEquals(1, \qtype_answersheet\answersheet_docs::count_records(['questionid' => $newmatchid,
            'type' => \qtype_answersheet\answersheet_docs::AUDIO_FILE_TYPE]));
        $fs = get_file_storage();
        $componentname = $multichoicedata->qtype;
        $files = $fs->get_area_files($multichoicedata->contextid, 'qtype_answersheet',
            'audio');
        $this->assertCount(2, $files);
    }
}
