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
 * The answersheet question renderer class is defined here.
 *
 * @package     qtype_answersheet
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_answersheet\output;

use moodle_url;
use qtype_answersheet\answersheet_docs;
use qtype_answersheet\local\persistent\answersheet_module;
use qtype_answersheet\local\persistent\answersheet_answers;
use qtype_answersheet\local\api\answersheet as answersheet_api;
use qtype_answersheet\utils;
use question_attempt;
use question_display_options;
use renderable;
use renderer_base;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Generates the output for answersheet questions.
 *
 * You should override functions as necessary from the parent class located at
 * /question/type/rendererbase.php.
 *
 * @package     qtype_answersheet
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class answersheet_question implements renderable, templatable {

    /**
     * @var question_attempt $qa
     */
    private $qa;

    /**
     * @var question_display_options $options
     */
    private $options;

    /**
     * @var array $displayoptions with information on how to display true or false response.
     */
    private $displayoptions;

    /**
     * Constructor
     *
     * @param question_attempt $qa
     * @param question_display_options $options
     * @param array $displayoptions
     */
    public function __construct(question_attempt $qa, question_display_options $options, array $displayoptions) {
        $this->qa = $qa;
        $this->options = $options;
        $this->displayoptions = $displayoptions;
    }

    /**
     * Export to template context
     *
     * @param renderer_base $output
     * @return stdClass
     * @throws \coding_exception
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        $question = $this->qa->get_question();
        $data->questiontext = $question->questiontext;
        $data->audiofiles = $this->get_document_info('audio');
        $data->pdffiles = $this->get_document_info('document');
        // return $data;
        $data->questionid = $question->id;
        $data->modules = $this->processmodules($question->id);
        $uniquenumber = uniqid();
        $data->cssurl = new moodle_url('/question/type/answersheet/scss/styles.css', ['v' => $uniquenumber ]);
        return $data;
    }

    private function processmodules(int $questionid): array {
        $data = answersheet_api::get_data($questionid);
        $newdata = [];
        //$newdata['questionid'] = $questionid;
        foreach ($data as $module) {
            $newmodule = [
                'moduleid' => $module['moduleid'],
                'modulename' => $module['modulename'],
                'questions' => [],
                'columns' => $module['columns'],
                'colspan' => $module['numoptions'] + 1,
            ];
            $type = answersheet_module::TYPES[$module['type']];
            $newmodule[$type] = true;
            if ($module['type'] == answersheet_module::RADIO_CHECKED) {
                // The possible answers are and array of A B C D etc values, length is defined by numoptions.
                $newmodule['possibleanswers'] = array_map(function($index) {
                    return chr(65 + $index);
                }, range(0, $module['numoptions'] - 1));
            }
            foreach ($module['rows'] as $row) {
                $newquestion = [
                    'id' => $this->qa->get_qt_field_name('answer' . $row['answerid']),
                    'response' => $this->qa->get_last_qt_var('answer' . $row['answerid'], ''),
                    'answers' => [],
                ];
                foreach ($row['cells'] as $cell) {
                    $newquestion[$cell['column']] = $cell['value'];
                }
                if ($module['type'] == answersheet_module::RADIO_CHECKED) {
                    $newquestion['correctanswer'] = ord($newquestion['options']) - 64;
                    $newquestion['answers'] = array_map(function($index) use ($newquestion) {
                        $answer = [
                            'label' => chr(65 + $index),
                            'value' => $index + 1,
                        ];
                        if ($newquestion['response'] == $index + 1) {
                            $answer['selected'] = true;
                            if ($this->options->correctness) {
                                $iscorrect = ($newquestion['response'] == $newquestion['correctanswer']) ? 1 : 0;
                                $answer['additionalclass'] = $this->displayoptions[$iscorrect]->additionalclass;
                            }
                        }

                        return $answer;
                    }, range(0, $module['numoptions'] - 1));
                }
                if ($module['type'] == answersheet_module::FREE_TEXT) {
                    $newquestion['correctanswer'] = $newquestion['answer'];
                    if ($this->options->correctness) {
                        $iscorrect = ($newquestion['response'] == $newquestion['correctanswer']) ? 1 : 0;
                        $newquestion['additionalclass'] = $this->displayoptions[$iscorrect]->additionalclass;
                        $newquestion['showanswer'] = true;
                    }
                }
                if ($module['type'] == answersheet_module::LETTER_BY_LETTER) {
                    $newquestion['correctanswer'] = $newquestion['answer'];
                    $newquestion['answers'] = array_map(function($index) use ($newquestion) {
                        if ($index >= strlen($newquestion['correctanswer'])) {
                            return [
                                'label' => '',
                                'index' => $index + 1,
                            ];
                        }
                        $answer = [
                            'label' => $newquestion['answer'][$index],
                            'index' => $index + 1,
                        ];
                        return $answer;
                    }, range(0, $module['numoptions'] - 1));
                    if ($this->options->correctness) {
                        $iscorrect = ($newquestion['response'] == $newquestion['correctanswer']) ? 1 : 0;
                        $newquestion['additionalclass'] = $this->displayoptions[$iscorrect]->additionalclass;
                        $newquestion['showanswer'] = true;
                    }
                }
                $newmodule['questions'][] = $newquestion;
            }
            $newdata[] = $newmodule;
        }
        return $newdata;
    }

    /**
     * Returns the URL of the first image or document
     *
     * @param string $filearea File area descriptor
     * @param int $itemid Item id to get
     * @return string Output url, or null if not found
     */
    protected function get_url_for_document($filearea, $itemid = 0) {
        $question = $this->qa->get_question();
        $qubaid = $this->qa->get_usage_id();
        $slot = $this->qa->get_slot();
        $fs = get_file_storage();
        $componentname = $question->qtype->plugin_name();
        $files = $fs->get_area_files($question->contextid, $componentname,
            $filearea, $itemid, 'id');
        if ($files) {
            foreach ($files as $file) {
                if ($file->is_directory()) {
                    continue;
                }
                $url = moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    "$qubaid/$slot/{$file->get_itemid()}",
                    $file->get_filepath(),
                    $file->get_filename());
                return $url->out();
            }
        }
        return '';
    }

    /**
     * Get document info for template
     *
     * @param string $documenttype
     * @return array
     * @throws \coding_exception
     */
    protected function get_document_info($documenttype) {
        $doccontext = [];
        $question = $this->qa->get_question();
        $docs =
            answersheet_docs::get_records(array('questionid' => $question->id,
                'type' => array_flip(answersheet_docs::DOCUMENT_TYPE_SHORTNAMES)[$documenttype]),
                'sortorder');

        foreach (array_values($docs) as $index => $doc) {
            $doccontext[] = [
                'url' => $this->get_url_for_document($documenttype, $doc->get('id')),
                'name' => $doc->get('name')
            ];
        }
        return $doccontext;
    }
}
