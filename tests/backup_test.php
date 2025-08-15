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

use context_module;
use question_bank;
use question_definition;
use backup_controller;
use restore_controller;
use backup;

/**
 * Tests for the answersheet question type backup and restore logic.
 *
 * @package   qtype_answersheet
 * @copyright 2025 Laurent David <laurent@call-learning.fr>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class backup_test extends \advanced_testcase {
    /**
     * Initialize common test setup elements.
     *
     * @return array Array containing initialized objects: [$course, $quiz, $question, $coregenerator, $questiongenerator]
     */
    private function initialize_test_environment() {
        global $CFG;

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $coregenerator = $this->getDataGenerator();
        $questiongenerator = $coregenerator->get_plugin_generator('core_question');

        // Create a course with a quiz that embeds a question.
        $course = $coregenerator->create_course();
        $quiz = $coregenerator->create_module('quiz', ['course' => $course->id]);
        $quizcontext = context_module::instance($quiz->cmid);

        $cat = $questiongenerator->create_question_category(['contextid' => $quizcontext->id]);
        $question = $questiongenerator->create_question('answersheet', 'standard', ['category' => $cat->id]);

        return [$course, $quiz, $question, $coregenerator, $questiongenerator];
    }

    /**
     * Duplicate quiz with a answersheet question, and check it worked.
     */
    public function test_duplicate_match_question(): void {
        global $DB;

        [$course, $quiz] = $this->initialize_test_environment();

        // Store some counts.
        $numquizzes = count(get_fast_modinfo($course)->instances['quiz']);
        $nummatchquestions = $DB->count_records('question', ['qtype' => 'answersheet']);

        // Duplicate the quiz.
        duplicate_module($course, get_fast_modinfo($course)->get_cm($quiz->cmid));

        // Verify the copied quiz exists.
        $this->assertCount($numquizzes + 1, get_fast_modinfo($course)->instances['quiz']);

        // Verify the copied question.
        $this->assertEquals($nummatchquestions + 1, $DB->count_records('question', ['qtype' => 'answersheet']));
        $newmatchid = $DB->get_field_sql(
            "SELECT MAX(id)
                  FROM {question}
                 WHERE qtype = ?",
            ['answersheet']
        );
        /* @var question_definition $answersheetdata */
        $questiontocheck = question_bank::load_question_data($newmatchid);

        $this->verify_restored_question($questiontocheck);
    }

    /**
     * Test backup and restore of answersheet question with user data.
     */
    public function test_backup_restore_with_user_data(): void {
        global $DB, $CFG;

        [$course, $quiz, $question, $coregenerator, $questiongenerator] = $this->initialize_test_environment();

        // Add question to quiz.
        quiz_add_quiz_question($question->id, $quiz);

        // Store original counts.
        $originalquizcount = $DB->count_records('quiz');
        $originalquestioncount = $DB->count_records('question', ['qtype' => 'answersheet']);
        $user1 = $coregenerator->create_user();

        // Attempt the question in the quiz.

        $this->setAdminUser();
        // Perform backup.
        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $course->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            get_admin()->id,
        );
        $bc->execute_plan();
        $backupid = $bc->get_backupid();
        $backupbasepath = $bc->get_plan()->get_basepath();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $bc->destroy();

        // Check if we need to unzip the file because the backup temp dir does not contains backup files.
        if (!file_exists($backupbasepath . "/moodle_backup.xml")) {
            $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $backupbasepath);
        }

        $newcourseid = \restore_dbops::create_new_course(
            $course->fullname . 'RESTORED',
            $course->shortname . 'RESTORED',
            $course->category
        );

        // Perform restore.
        $rc = new restore_controller(
            $backupid,
            $newcourseid,
            backup::INTERACTIVE_NO,
            backup::MODE_SAMESITE,
            get_admin()->id,
            backup::TARGET_NEW_COURSE,
        );
        $rc->execute_precheck();
        $rc->execute_plan();
        $rc->destroy();

        // Verify restored data.
        $this->assertEquals($originalquizcount + 1, $DB->count_records('quiz'));
        $this->assertEquals($originalquestioncount + 1, $DB->count_records('question', ['qtype' => 'answersheet']));

        // Get restored question.
        $restoredquestion = $DB->get_record_sql(
            "SELECT q.* FROM {question} q 
             JOIN {question_versions} qv ON q.id = qv.questionid
             JOIN {question_bank_entries} qbe ON qv.questionbankentryid = qbe.id
             JOIN {question_categories} qc ON qbe.questioncategoryid = qc.id
             JOIN {context} ctx ON qc.contextid = ctx.id
             JOIN {course_modules} cm ON ctx.instanceid = cm.id
             WHERE q.qtype = ? AND cm.course = ?",
            ['answersheet', $newcourseid]
        );
        $questiontocheck = question_bank::load_question_data($restoredquestion->id);
        $this->verify_restored_question($questiontocheck);
        // Clean up backup files.
        $backupbasepath = $CFG->tempdir . '/backup';
        if (is_dir($backupbasepath . '/' . $backupid)) {
            fulldelete($backupbasepath . '/' . $backupid);
        }
    }

    /**
     * Verify answersheet question data after backup/restore.
     * This method checks that all answersheet-specific data was properly restored.
     *
     * @param object $question The question object to verify
     * @param \PHPUnit\Framework\TestCase $testcase The test case for assertions
     */
    private function verify_restored_question($question) {
        // Load the question data.
        $answersheetdata = question_bank::load_question_data($question->id);

        // Verify basic question properties.
        $this->assertNotEmpty($answersheetdata->options->answers);
        $this->assertCount(6, $answersheetdata->options->answers);
        $this->assertEquals(2, $answersheetdata->options->startnumbering);

        // Verify answersheet-specific data.
        $modulecount = \qtype_answersheet\local\persistent\answersheet_module::count_records(
            ['questionid' => $question->id]
        );
        $this->assertEquals(3, $modulecount, 'Expected 1 answersheet modules');

        $docscount = \qtype_answersheet\answersheet_docs::count_records([
            'questionid' => $question->id,
            'type' => \qtype_answersheet\answersheet_docs::AUDIO_FILE_TYPE,
        ]);

        $this->assertEquals(1, $docscount, 'Expected 1 audio document');

        $docscount = \qtype_answersheet\answersheet_docs::count_records([
            'questionid' => $question->id,
            'type' => \qtype_answersheet\answersheet_docs::DOCUMENT_FILE_TYPE,
        ]);

        $this->assertEquals(1, $docscount, 'Expected 1 pdf document');
        // Verify files were restored.
        $fs = get_file_storage();
        $files = $fs->get_area_files($answersheetdata->contextid, 'qtype_answersheet', 'audio');
        $this->assertCount(2, $files, 'Expected 2 files (including directory entry)');

        return $answersheetdata;
    }
}
