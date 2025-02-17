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
use qtype_answersheet\local\persistent\answersheet_answers;
use qtype_answersheet\local\persistent\answersheet_module;
use qtype_answersheet\local\api\answersheet as answersheet_api;


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

        $formjsondata = json_decode($question->newquestion, true);
        if (!empty($formjsondata)) {
            answersheet_api::set_records($question->id, $formjsondata);
        }
        $modules = answersheet_api::get_data($question->id);
        $answers = answersheet_answers::get_all_records_for_question($question->id);
        if (empty($answers)) {
            $modules = answersheet_api::get_data($question->oldparent);
            $answers = answersheet_answers::get_all_records_for_question($question->oldparent);
        }
        $newanswers = [];
        foreach ($modules as $module) {
            $type = answersheet_module::TYPES[$module['type']];
            foreach ($module['rows'] as $row) {
                $newquestion = [];
                $newquestion['id'] = $row['id'];
                foreach ($row['cells'] as $cell) {
                    $newquestion[$cell['column']] = $cell['value'];
                }
                if ($type == 'radiochecked') {
                    $value = ord($newquestion['options']) - 64;
                    $newquestion['value'] = $value;
                } else if ($type == 'letterbyletter' || $type == 'freetext') {
                    $newquestion['value'] = $newquestion['answer'];
                }
                $newanswers[] = $newquestion;
            }
        }
        foreach ($newanswers as $answer) {
            $question->answer[] = $answer['value'];
            $question->answersheetanswer[] = $answer['id'];
            $question->fraction[] = 0;
            $question->feedback[] = $answer['feedback'];
        }
        // foreach ($answers as $answer) {
        //     $value = $answer->get('options');
        //     // Change the A, B, C into 1, 2, 3.
        //     $value = ord($value) - 64;
        //     $feedback = $answer->get('feedback');
        //     $question->answer[] = $value;
        //     $question->answersheetanswer[] = $answer->get('id');
        //     $question->fraction[] = 0;
        //     $question->feedback[] = $feedback;
        // }
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
            $modules = answersheet_api::get_data(1000);
            $start = 0;
            foreach ($modules as $mod) {
                $part = new stdClass();
                $part->questionid = $question->id;
                $part->start = $start;
                $part->name = $mod['modulename'];
                $start = count($mod['rows']);
                $questionpart = new answersheet_parts(0, $part);
                $questionpart->create();
            }
        }
    }

    /**
     * Save the answers, with any extra data.
     *
     * Questions that use answers will call it from {@link save_question_options()}.
     * @param object $question  This holds the information from the editing form,
     *      it is not a standard question object.
     * @return object $result->error or $result->notice
     */
    public function save_question_answers($question) {
        global $DB;

        $context = $question->context;
        $oldanswers = $DB->get_records('question_answers',
                array('question' => $question->id), 'id ASC');

        // We need separate arrays for answers and extra answer data, so no JOINS there.
        $extraanswerfields = $this->extra_answer_fields();
        $isextraanswerfields = is_array($extraanswerfields);
        $extraanswertable = '';
        $oldanswerextras = array();
        if ($isextraanswerfields) {
            $extraanswertable = array_shift($extraanswerfields);
            if (!empty($oldanswers)) {
                $oldanswerextras = $DB->get_records_sql("SELECT * FROM {{$extraanswertable}} WHERE " .
                    'answerid IN (SELECT id FROM {question_answers} WHERE question = ' . $question->id . ')' );
            }
        }

        // Insert all the new answers.
        foreach ($question->answer as $key => $answerdata) {
            // Check for, and ignore, completely blank answer from the form.
            if ($this->is_answer_empty($question, $key)) {
                continue;
            }

            // Update an existing answer if possible.
            $answer = array_shift($oldanswers);
            if (!$answer) {
                $answer = new stdClass();
                $answer->question = $question->id;
                $answer->answer = '';
                $answer->feedback = '';
                $answer->id = $DB->insert_record('question_answers', $answer);
            }

            $answer = $this->fill_answer_fields($answer, $question, $key, $context);
            $DB->update_record('question_answers', $answer);
            $answersheetanswerid = $question->answersheetanswer[$key];
            $answersheetanswer = answersheet_answers::get_record(['id' => $answersheetanswerid]);
            $answersheetanswer->set('answerid', $answer->id);
            $answersheetanswer->set('questionid', $question->id);
            $answersheetanswer->save();
            $moduleid = $answersheetanswer->get('moduleid');
            $module = answersheet_module::get_record(['id' => $moduleid]);
            $module->set('questionid', $question->id);
            $module->save();

            if ($isextraanswerfields) {
                // Check, if this answer contains some extra field data.
                if ($this->is_extra_answer_fields_empty($question, $key)) {
                    continue;
                }

                $answerextra = array_shift($oldanswerextras);
                if (!$answerextra) {
                    $answerextra = new stdClass();
                    $answerextra->answerid = $answer->id;
                    // Avoid looking for correct default for any possible DB field type
                    // by setting real values.
                    $answerextra = $this->fill_extra_answer_fields($answerextra, $question, $key, $context, $extraanswerfields);
                    $answerextra->id = $DB->insert_record($extraanswertable, $answerextra);
                } else {
                    // Update answerid, as record may be reused from another answer.
                    $answerextra->answerid = $answer->id;
                    $answerextra = $this->fill_extra_answer_fields($answerextra, $question, $key, $context, $extraanswerfields);
                    $DB->update_record($extraanswertable, $answerextra);
                }
            }
        }

        if ($isextraanswerfields) {
            // Delete any left over extra answer fields records.
            $oldanswerextraids = array();
            foreach ($oldanswerextras as $oldextra) {
                $oldanswerextraids[] = $oldextra->id;
            }
            $DB->delete_records_list($extraanswertable, 'id', $oldanswerextraids);
        }

        // Delete any left over old answer records.
        $fs = get_file_storage();
        foreach ($oldanswers as $oldanswer) {
            $fs->delete_area_files($context->id, 'question', 'answerfeedback', $oldanswer->id);
            $DB->delete_records('question_answers', array('id' => $oldanswer->id));
        }
    }
}
