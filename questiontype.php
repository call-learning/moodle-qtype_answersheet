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
use qtype_answersheet\local\api\answersheet as answersheet_api;
use qtype_answersheet\local\persistent\answersheet_answers;
use qtype_answersheet\local\persistent\answersheet_module;
use qtype_answersheet\utils;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/questionlib.php');

/**
 * Few notes on the implementation:
 * - The answersheet question type is a complex question type that allows for various types of questions
 *   to be created and managed within a single question.
 *  - It uses a JSON structure to store the question data, which allows for flexibility in the
 *  types of questions that can be created.
 * Here we do not use the questiontype::extra_answer_field() method to link to other tables such as answers
 * or modules because we managed that in the answersheet_api::set_records() method.
 */

/**
 * Class that represents a answersheet question type.
 *
 * The class loads, saves and deletes questions of the type answersheet
 * to and from the database and provides methods to help with editing questions
 * of this type. It can also provide the implementation for import and export
 * in various formats.
 */
class qtype_answersheet extends question_type {
    #[\Override]
    public function save_question_options($question) {
        // Ensure arrays are initialized.
        $question->answer = $question->answer ?? [];
        $question->fraction = $question->fraction ?? [];
        $question->feedback = $question->feedback ?? [];

        // Format feedbacks.
        $question->feedback = array_map(fn($feedbacktext) => [
            'format' => FORMAT_PLAIN,
            'text' => $feedbacktext,
        ], $question->feedback);

        // Handle combined feedback.
        $question->options = $question->options ?? new stdClass();
        $options = $this->save_combined_feedback_helper($question->options, $question, $question->context, true);
        foreach ((array) $options as $itemname => $value) {
            $question->$itemname = $value;
        }

        // Save question data.
        $this->save_question_answers($question);
        $this->save_hints($question);
        $this->save_documents($question);
        parent::save_question_options($question);
    }

    #[\Override]
    public function save_question_answers($question) {
        // Process answersheet data.
        $jsondata = json_decode($question->jsonquestions, true);
        $question->extraanswerfields = [];
        // We need here to build a set of data compatible with the question type API.
        // The field that is native is the 'answer' field, which is an array of answers.
        // We add to this the 'extraanswerfields' field, which is an array of additional field values using the same
        // keys.

        if (!empty($jsondata)) {
            // First create or update the modules records.
            foreach ($jsondata as $module) {
                if (empty($module['id']) || !is_numeric($module['id'])) {
                    $mod = new answersheet_module();
                } else {
                    $mod = answersheet_module::get_record([
                        'id' => $module['id'],
                    ]);
                }
                $mod->set('name', $module['name']);
                $mod->set('type', $module['type']);
                $mod->set('sortorder', $module['sortorder']);
                $mod->set('questionid', $question->id);
                $mod->set('numoptions', $module['numoptions']);
                $mod->save();
                $moduleid = $mod->get('id');
                $moduletype = answersheet_module::TYPES[$module['type']];

                foreach ($module['rows'] as $row) {
                    $answerinfo = array_column($row['cells'], 'value', 'column');
                    switch ($moduletype) {
                        case answersheet_module::TYPES[answersheet_module::RADIO_CHECKED]:
                            $value = $answerinfo['answer'] ?? '';
                            break;
                        case answersheet_module::TYPES[answersheet_module::LETTER_BY_LETTER]:
                            $value = ord($answerinfo['answer']) - 64;
                            break;
                        default:
                            $value = $answerinfo['answer'] ?? '';
                    }

                    $question->answer[] = $value;
                    $question->fraction[] = $answerinfo['fraction'] ?? 1;
                    $question->feedback[] = [
                        'text' => $answerinfo['feedback'] ?? '',
                        'format' => FORMAT_PLAIN,
                    ];
                    // Add the extra answer field for this module.
                    $question->extraanswerfields[] = [
                        'moduleid' => $moduleid,
                        'sortorder' => $row['sortorder'],
                        'name' => $answerinfo['name'] ?? '',
                        'moduletype' => $moduletype,
                        'numoptions' => $module['numoptions'],
                        'options' => !empty($answerinfo['options']) ? json_encode($answerinfo['options']) : '',
                        'answer' => $answerinfo['answer'] ?? '',
                        'feedback' => $answerinfo['feedback'] ?? '',
                        'usermodified' => $row['usermodified'] ?? 0,
                        'timecreated' => $row['timecreated'] ?? 0,
                        'timemodified' => $row['timemodified'] ?? 0,
                    ];
                    if (!empty($row['id']) && is_numeric($row['id'])) {
                        $question->extraanswerfields['id'] = $row['id'];
                    }
                }
            }
        }
        parent::save_question_answers($question);
        // Now make sure we save the persistent answers with time and users.
        $answersheet = answersheet_answers::get_all_records_for_question($question->id);
        foreach ($answersheet as $answer) {
            $answer->update();
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
                    ['questionid' => $question->id, 'type' => $type, 'sortorder' => $index]
                );
                $currentdraftcontent = file_get_drafarea_files($question->{$area}[$index]);
                // Do not add the file if the content is empty.
                if (!empty($currentdraftcontent->list)) {
                    if (empty($docforthisquestion)) {
                        $docforthisquestion = new answersheet_docs(0, (object) [
                            'type' => $type,
                            'name' => $doctext,
                            'sortorder' => $index,
                            'questionid' => $question->id,
                        ]);
                        $docforthisquestion->create();
                    } else {
                        $docforthisquestion->set('name', $doctext);
                        $docforthisquestion->update();
                    }

                    // Make sure we delete file that is saved under the same index.
                    $fs = get_file_storage();
                    $fs->delete_area_files(
                        $question->context->id,
                        'qtype_answersheet',
                        $area,
                        $docforthisquestion->get('id')
                    );
                    file_save_draft_area_files(
                        $filedraftareaid,
                        $question->context->id,
                        'qtype_answersheet',
                        $area,
                        $docforthisquestion->get('id'),
                        utils::file_manager_options($area)
                    );
                }
            }
        }
    }

    #[\Override]
    public function extra_question_fields() {
        return [
            'qtype_answersheet',
            'correctfeedback',
            'correctfeedbackformat',
            'partiallycorrectfeedback',
            'partiallycorrectfeedbackformat',
            'incorrectfeedback',
            'incorrectfeedbackformat',
            'shownumcorrect',
            'startnumbering',
        ];
    }

    #[\Override]
    public function extra_answer_fields() {
        return [
            'qtype_answersheet_answers',
            'moduleid',
            'sortorder',
            'name',
            'numoptions',
            'options',
            'answer',
            'feedback',
            'usermodified',
            'timecreated',
            'timemodified',
        ];
    }

    #[\Override]
    protected function is_extra_answer_fields_empty($questiondata, $key) {
        return empty($questiondata->extraanswerfields[$key]);
    }

    #[\Override]
    protected function fill_extra_answer_fields($answerextra, $questiondata, $key, $context, $extraanswerfields) {
        // We override here so we do not add the extraanswerfields in the questiondata directly but rather in the answerextrafields
        // array.
        foreach ($extraanswerfields as $field) {
            $answerextra->$field = $questiondata->extraanswerfields[$key][$field];
        }
        return $answerextra;
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
            $fs->move_area_files_to_new_context(
                $oldcontextid,
                $newcontextid,
                'qtype_answersheet',
                $area
            );
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
        $this->initialise_question_extra_info($question, $questiondata);
        $this->initialise_question_hints($question, $questiondata);
        $this->initialise_combined_feedback($question, $questiondata);
        answersheet_docs::add_data($question);
    }

    /**
     * Initialise question extra answers
     *
     * This method loads the extra answer fields from the question data into the question instance.
     * This is mainly to have a consistent approach with the fact that we might not want to load all the
     * extra answer fields in the question instance from the dataabase while testing.
     *
     * @param question_definition $question
     * @param object $questiondata
     */
    protected function initialise_question_extra_info(question_definition $question, $questiondata) {
        // We use this as a way to load the extra answer fields from the question data into the
        $question->extraanswerfields = [];
        $question->extraanswerdatatypes = [];
        foreach($question->answers as $answerid => $answer) {
            $extraanswer = answersheet_answers::get_record([
                'answerid' => $answerid
            ]);

            if ($extraanswer) {
                $answerfieldstokeep = $this->extra_answer_fields();
                $answerfieldstokeep = array_fill_keys($answerfieldstokeep, 1);
                $answerfieldstokeep['answerid'] = 1;
                $answerfieldstokeep['id'] = 1;
                $question->extraanswerfields[] =
                    array_intersect_key(
                        (array) $extraanswer->to_record(),
                        $answerfieldstokeep
                    );
                $question->extraanswerdatatypes[] = [
                    'answerid' => $answerid,
                    'datatype' => $extraanswer->get_module_data_type(),
                    'type' => $extraanswer->get_module_type()
                ];
            }
        }
        $question->extradatainfo = answersheet_api::get_data($question->id); // Intialise the extra output info for display.
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
}
