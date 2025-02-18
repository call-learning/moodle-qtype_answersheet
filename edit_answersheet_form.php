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
 * The editing form for answersheet question type is defined here.
 *
 * @package     qtype_answersheet
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use qtype_answersheet\answersheet_docs;
use qtype_answersheet\utils;
use qtype_answersheet\output\answersheet;
use qtype_answersheet\output\sheet_renderer;

defined('MOODLE_INTERNAL') || die();

/**
 * answersheet question editing form defition.
 *
 * You should override functions as necessary from the parent class located at
 * /question/type/edit_question_form.php.
 */
class qtype_answersheet_edit_form extends question_edit_form {

    /**
     * Returns the question type name.
     *
     * @return string The question type name.
     */
    public function qtype() {
        return 'answersheet';
    }

    /**
     * Add question-type specific form fields.
     *
     * @param MoodleQuickForm $mform the form being built.
     */
    protected function definition_inner($mform) {
        global $PAGE, $OUTPUT;
        $this->add_documents_fields();

        $mform->addElement('text', 'startnumbering',
            get_string('startnumbering', 'qtype_answersheet'));
        $mform->setType('startnumbering', PARAM_INT);

        $id = optional_param('id', 0, PARAM_INT);
        $mform->addElement('header', 'answerhdr',
        get_string('answers', 'question'), '');
        $mform->setExpanded('answerhdr', 1);
        $renderer = new sheet_renderer($PAGE, $OUTPUT);
        $programm = new answersheet($id);
        $mform->addElement('hidden', 'newquestion', '');
        $mform->setType('newquestion', PARAM_TEXT);
        $mform->addElement('html', $renderer->render($programm));

        $this->add_combined_feedback_fields(true);
        $mform->disabledIf('shownumcorrect', 'single', 'eq', 1);

        $this->add_interactive_settings(true, true);
    }

    /**
     * Preprocess data
     *
     * @param object $question
     * @return object
     */
    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_answers($question, false);
        $question = $this->data_preprocessing_combined_feedback($question, true);
        $question = $this->data_preprocessing_hints($question, true, true);
        $question = $this->data_preprocessing_documents($question);
        return $question;
    }

    /**
     * Perform the necessary preprocessing for the fields added by
     * {@link question_edit_form::add_per_answer_fields()}.
     *
     * Note : this is a slightly different version from core as feedback field are text and not editors.
     *
     * @param object $question the data being passed to the form.
     * @param bool $withanswerfiles
     * @return object $question the modified data.
     */
    protected function data_preprocessing_answers($question, $withanswerfiles = false) {
        if (empty($question->options->answers)) {
            return $question;
        }
        if (!isset($question->feedback)) {
            $question->feedback = array();
        }
        $key = 0;
        foreach ($question->options->answers as $answer) {
            if ($withanswerfiles) {
                // Prepare the feedback editor to display files in draft area.
                $draftitemid = file_get_submitted_draft_itemid('answer[' . $key . ']');
                $question->answer[$key]['text'] = file_prepare_draft_area(
                    $draftitemid,          // Draftid.
                    $this->context->id,    // Context.
                    'question',            // Component.
                    'answer',              // Filarea.
                    !empty($answer->id) ? (int) $answer->id : null, // Itemid.
                    $this->fileoptions,    // Options.
                    $answer->answer        // Text.
                );
                $question->answer[$key]['itemid'] = $draftitemid;
                $question->answer[$key]['format'] = $answer->answerformat;
            } else {
                $question->answer[$key] = $answer->answer;
            }

            $question->fraction[$key] = 0 + $answer->fraction;

            // Evil hack alert. Formslib can store defaults in two ways for
            // repeat elements:
            // ->_defaultValues['fraction[0]'] and
            // ->_defaultValues['fraction'][0].
            // The $repeatedoptions['fraction']['default'] = 0 bit above means
            // that ->_defaultValues['fraction[0]'] has already been set, but we
            // are using object notation here, so we will be setting
            // ->_defaultValues['fraction'][0]. That does not work, so we have
            // to unset ->_defaultValues['fraction[0]'].
            unset($this->_form->_defaultValues["fraction[{$key}]"]);

            $question->feedback[$key] = $answer->feedback;

            $key++;
        }

        // Now process extra answer fields.
        $extraanswerfields = question_bank::get_qtype($question->qtype)->extra_answer_fields();
        if (is_array($extraanswerfields)) {
            // Omit table name.
            array_shift($extraanswerfields);
            $question = $this->data_preprocessing_extra_answer_fields($question, $extraanswerfields);
        }

        return $question;
    }

    /**
     * Perform the necessary preprocessing for audio and document fields
     *
     * @param object $question the data being passed to the form.
     * @return object $question the modified data.
     */
    protected function data_preprocessing_documents($question) {
        if (!empty($question->id)) {
            $fs = get_file_storage();
            foreach (answersheet_docs::DOCUMENT_TYPE_SHORTNAMES as $type => $area) {
                // Very similar to file_get_submitted_draft_itemid.
                $draftitemids = optional_param_array($area, [], PARAM_INT);
                if ($draftitemids) {
                    require_sesskey();
                }
                $docsforthisquestion = \qtype_answersheet\answersheet_docs::get_records(
                    array('questionid' => $question->id, 'type' => $type),
                    'sortorder'
                );
                foreach (array_values($docsforthisquestion) as $index => $doc) {
                    $draftitemid = $draftitemids[$index] ?? 0;
                    file_prepare_draft_area($draftitemid, $this->context->id, 'qtype_answersheet',
                        $area, $doc->get('id'),
                        utils::file_manager_options($area));
                    // Remove draft aread if empty.
                    $currentdraftcontent = file_get_drafarea_files($draftitemid);
                    if (empty($currentdraftcontent->list)) {
                        $doc->delete();
                        $fs->delete_area_files($question->contextid, 'qtype_answersheet',
                            $area, $doc->get('sortorder'));
                    } else {
                        $question->{$area}[] = $draftitemid;
                    }

                }
            }
            answersheet_docs::add_data($question);
        }
        return $question;
    }

    /**
     * Add a set of form fields, obtained from get_per_answer_fields, to the form,
     * one for each existing answer, with some blanks for some new ones.
     * @param object $mform the form being built.
     * @param $label the label to use for each option.
     * @param $gradeoptions the possible grades for each answer.
     * @param $minoptions the minimum number of answer blanks to display.
     *      Default QUESTION_NUMANS_START.
     * @param $addoptions the number of answer blanks to add. Default QUESTION_NUMANS_ADD.
     */
    protected function add_per_answer_fields(&$mform, $label, $gradeoptions,
            $minoptions = QUESTION_NUMANS_START, $addoptions = QUESTION_NUMANS_ADD) {
        $mform->addElement('header', 'answerhdr',
                get_string('answers', 'question'), '');
        $mform->setExpanded('answerhdr', 1);
        $answersoption = '';
        $repeatedoptions = array();
        $repeated = $this->get_per_answer_fields_custom($mform, $label, $gradeoptions, 5,
                $repeatedoptions, $answersoption);

        if (isset($this->question->options)) {
            $repeatsatstart = count($this->question->options->$answersoption);
        } else {
            $repeatsatstart = $minoptions;
        }

         $this->repeat_elements($repeated, $repeatsatstart, $repeatedoptions,
                'noanswers', 'addanswers', $addoptions,
                $this->get_more_choices_string(), true);
    }

    /**
     * Get a single row of answers
     *
     * @param MoodleQuickForm $mform
     * @param string $label
     * @param mixed $gradeoptions
     * @param mixed $repeatedoptions
     * @param mixed $answersoption
     * @return array
     * @throws coding_exception
     */
    protected function get_per_answer_fields($mform, $label, $gradeoptions,
        &$repeatedoptions, &$answersoption) {
        $repeated = array();
        $radioarray = array();
        // Answer 'answer' is a key in saving the question (see {@link save_question_answers()}).
        // Same for feedback.
        for ($i = 1; $i <= utils::OPTION_COUNT; $i++) {
            $radioarray[] = $mform->createElement('radio', 'answer', '', get_string('option', 'qtype_answersheet', $i), $i);
        }
        $repeated[] =
            $mform->createElement('group', 'answergroup', get_string('answer', 'qtype_answersheet'), $radioarray, array(' '),
                false);
        $repeated[] = $mform->createElement('hidden', 'fraction');
        $repeated[] = $mform->createElement('text', 'feedback',
            get_string('feedback', 'question'), array('rows' => 1), $this->editoroptions);
        $repeatedoptions['answer']['type'] = PARAM_RAW;
        $repeatedoptions['feedback']['type'] = PARAM_TEXT;
        $repeatedoptions['fraction']['default'] = 0;
        $repeatedoptions['fraction']['type'] = PARAM_INT;
        $answersoption = 'answers';
        return $repeated;
    }

        /**
     * Get a single row of answers
     *
     * @param MoodleQuickForm $mform
     * @param string $label
     * @param mixed $gradeoptions
     * @param mixed $repeatedoptions
     * @param int $numradios
     * @param mixed $answersoption
     * @return array
     * @throws coding_exception
     */
    protected function get_per_answer_fields_custom($mform, $label, $gradeoptions, $numradios,
        &$repeatedoptions, &$answersoption) {
        $repeated = array();
        $radioarray = array();
        // Answer 'answer' is a key in saving the question (see {@link save_question_answers()}).
        // Same for feedback.
        for ($i = 1; $i <= $numradios; $i++) {
            // Get the alpabetical letter for the option based on the index.
            $letter = chr(64 + $i);
            $radioarray[] = $mform->createElement('radio', 'answer', '', get_string('option', 'qtype_answersheet', $letter), $i);
        }
        $repeated[] =
            $mform->createElement('group', 'answergroup', get_string('answer', 'qtype_answersheet'), $radioarray, array(' '),
                false);
        $repeated[] = $mform->createElement('hidden', 'fraction');
        $repeated[] = $mform->createElement('text', 'feedback',
            get_string('feedback', 'question'), array('rows' => 1), $this->editoroptions);
        $repeatedoptions['answer']['type'] = PARAM_RAW;
        $repeatedoptions['feedback']['type'] = PARAM_TEXT;
        $repeatedoptions['fraction']['default'] = 0;
        $repeatedoptions['fraction']['type'] = PARAM_INT;
        $answersoption = 'answers';
        return $repeated;
    }

    /**
     * Add documents fields
     */
    protected function add_documents_fields() {
        $mform = $this->_form;
        foreach (answersheet_docs::DOCUMENT_TYPE_SHORTNAMES as $item) {
            $mform->addElement('header', $item . 'header',
                get_string($item . ':title', 'qtype_answersheet'));
            $repeated = [];
            $repeated[] =
                $mform->createElement('filemanager',
                    $item,
                    get_string($item, 'qtype_answersheet'),
                    null,
                    utils::file_manager_options($item));
            $repeated[] =
                $mform->createElement('text',
                    $item . 'name',
                    get_string($item . 'name', 'qtype_answersheet'));

            $repeatcount = 1;
            if (!empty($this->question->id)) {
                $repeatcount = \qtype_answersheet\answersheet_docs::count_records(
                    array('questionid' => $this->question->id,
                        'type' => array_flip(answersheet_docs::DOCUMENT_TYPE_SHORTNAMES)[$item]));
                $repeatcount = $repeatcount ? $repeatcount : 1;
            }
            $repeatedoptions = [];
            $repeatedoptions[$item]['type'] = PARAM_RAW;
            $repeatedoptions[$item . 'name']['type'] = PARAM_RAW;
            $this->repeat_elements(
                $repeated,
                $repeatcount,
                $repeatedoptions,
                $item . 'item',
                'add' . $item,
                1
            );
        }
    }
}
