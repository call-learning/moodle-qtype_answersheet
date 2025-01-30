<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Question type class for answersheet is defined here.
 *
 * @package     qtype_answersheet
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use qtype_answersheet\answersheet_docs;
use qtype_answersheet\answersheet_parts;
use qtype_answersheet\utils;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/questionlib.php');

/**
 * Class that represents a answersheet question type.
 *
 * The class loads, saves and deletes questions of the type answersheet
 * to and from the database and provides methods to help with editing questions
 * of this type. It can also provide the implementation for import and export
 * in various formats.
 */
class qtype_answersheet extends question_type {

    // Override functions as necessary from the parent class located at
    // /question/type/questiontype.php.
    /**
     * Save question options
     *
     * @param object $question
     * @return object|void
     */
    public function save_question_options($question) {
        // The feedback are supposed to be editor, but to speed up the page rendering, we will use plain
        // text fields. This means we will need to create mock editor structure.
        foreach ($question->feedback as $key => $feedbacktext) {
            $feedbacks[] = [
                'format' => FORMAT_PLAIN,
                'text' => $feedbacktext
            ];
        }
        $question->feedback = $feedbacks;
        $this->save_question_answers($question);
        $this->save_hints($question);

        $this->save_documents($question);
        $this->save_parts($question);
        // This will flattern the structure regarding the combined feedback.
        if (empty($question->options)) {
            $question->options = new stdClass();
        }
        $options = $this->save_combined_feedback_helper($question->options, $question, $question->context, true);
        foreach ((array) $options as $itemname => $value) {
            $question->$itemname = $value;
        }
        parent::save_question_options($question);
    }

    /**
     * Defines the table which extends the question table. This allows the base questiontype
     * to automatically save, backup and restore the extra fields.
     *
     * @return an array with the table name (first) and then the column names (apart from id and questionid)
     */
    public function extra_question_fields() {
        return array('qtype_answersheet',
            'correctfeedback',
            'correctfeedbackformat',
            'partiallycorrectfeedback',
            'partiallycorrectfeedbackformat',
            'incorrectfeedback',
            'incorrectfeedbackformat',
            'shownumcorrect',
            'startnumbering'
        );
    }

    /**
     * Move files
     *
     * @param int $questionid
     * @param int $oldcontextid
     * @param int $newcontextid
     */
    public function move_files($questionid, $oldcontextid, $newcontextid) {
        $fs = get_file_storage();

        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_answers($questionid, $oldcontextid, $newcontextid, true);
        $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);

        foreach (utils::get_fileareas() as $area) {
            $fs->move_area_files_to_new_context($oldcontextid,
                $newcontextid, 'qtype_answersheet', $area);
        }
    }

    /**
     * Initialise question instance
     *
     * @param question_definition $question
     * @param object $questiondata
     * @throws coding_exception
     */
    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        $this->initialise_question_answers($question, $questiondata, false);
        $this->initialise_question_hints($question, $questiondata);
        $this->initialise_combined_feedback($question, $questiondata);
        answersheet_docs::add_data($question);
        answersheet_parts::add_data($question);
    }

    /**
     * Delete files
     *
     * @param int $questionid
     * @param int $contextid
     */
    protected function delete_files($questionid, $contextid) {
        $fs = get_file_storage();

        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_answers($questionid, $contextid, true);
        $this->delete_files_in_hints($questionid, $contextid);

        foreach (utils::get_fileareas() as $area) {
            $fs->delete_area_files($contextid, 'qtype_answersheet', $area);
        }
    }

    /**
     * Save documents
     *
     * @param object $question
     * @throws \core\invalid_persistent_exception
     * @throws coding_exception
     */
    protected function save_documents($question) {
        foreach (answersheet_docs::DOCUMENT_TYPE_SHORTNAMES as $type => $area) {
            foreach ($question->$area as $index => $filedraftareaid) {
                $doctext = $question->{$area . 'name'}[$index] ?? "";
                $docforthisquestion = \qtype_answersheet\answersheet_docs::get_record(
                    array('questionid' => $question->id, 'type' => $type, 'sortorder' => $index)
                );
                $currentdraftcontent = file_get_drafarea_files($question->{$area}[$index]);
                // Do not add the file if the content is empty.
                if (!empty($currentdraftcontent->list)) {
                    if (empty($docforthisquestion)) {
                        $docforthisquestion = new answersheet_docs(0, (object) [
                            'type' => $type,
                            'name' => $doctext,
                            'sortorder' => $index,
                            'questionid' => $question->id
                        ]);
                        $docforthisquestion->create();
                    } else {
                        $docforthisquestion->set('name', $doctext);
                        $docforthisquestion->update();
                    }

                    // Make sure we delete file that is saved under the same index.
                    $fs = get_file_storage();
                    $fs->delete_area_files($question->context->id,
                        'qtype_answersheet', $area, $docforthisquestion->get('id'));
                    file_save_draft_area_files($filedraftareaid, $question->context->id,
                        'qtype_answersheet', $area, $docforthisquestion->get('id'),
                        utils::file_manager_options($area));
                }
            }
        }
    }

    /**
     * Save parts information
     *
     * @param object $question
     */
    protected function save_parts($question) {
        if ($question->id) {
            global $DB;
            // First delete all existing parts for this question.
            $DB->delete_records(answersheet_parts::TABLE, array('questionid' => $question->id));
            foreach ($question->parts as $index => $partdef) {
                $part = new stdClass();
                $part->questionid = $question->id;
                $part->start = $partdef['partstart'];
                $part->name = $partdef['partname'];
                $questionpart = new answersheet_parts(0, $part);
                $questionpart->create();
            }
        }
    }
}
