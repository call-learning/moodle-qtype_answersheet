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
use qtype_answersheet\answersheet_parts;
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
        $possibleanswers = [];
        for ($i = 1; $i <= utils::OPTION_COUNT; $i++) {
            $possibleanswers[] = get_string('option:' . $i, 'qtype_answersheet');
        }
        $questionstartnum = $question->startnumbering;

        $parts = answersheet_parts::get_records(['questionid' => $question->id], 'start', 'ASC');
        $data->parts = [];
        if ($parts) {
            foreach (array_values($parts) as $index => $part) {
                $data->parts[] = (object) [
                    'partid' => $part->get('id'),
                    'label' => $part->get('name'),
                    'questions' => [],
                    'possibleanswers' => $possibleanswers,
                    'isactive' => $index > 0 ? false : true
                ];
            }
        } else {
            $data->parts[] = (object) [
                'partid' => 0,
                'label' => '',
                'questions' => [],
                'possibleanswers' => $possibleanswers,
                'isactive' => true
            ];
        }
        $lastindex = 1;
        if (!empty($parts)) {
            array_shift($parts);
        }
        $nextpart = empty($parts) ? null : array_shift($parts);
        $currentpartindex = 0;
        foreach ($question->answers as $answerid => $answer) {
            $aquestion = new stdClass();
            $aquestion->answers = [];
            $aquestion->feedback = $this->options->feedback ? $answer->feedback : '';
            $aquestion->index = $questionstartnum++;
            $answerkey = 'answer' . $answerid;
            $aquestion->id = $this->qa->get_qt_field_name($answerkey);
            $response = $this->qa->get_last_qt_var($answerkey, '');
            $iscorrect = false;
            for ($i = 1; $i <= utils::OPTION_COUNT; $i++) {
                $ananswer = new stdClass();
                $ananswer->label = get_string('option:' . $i, 'qtype_answersheet');
                $ananswer->value = $i;
                if ($response == $i) {
                    $ananswer->selected = true;
                    $iscorrect = ($response == $answer->answer) ? 1 : 0;
                }
                if ($this->options->correctness) {
                    $isrightvalue = ($response == $answer->answer) ? 1 : 0;
                    $ananswer->additionalclass = $this->displayoptions[$isrightvalue]->additionalclass;

                }
                $aquestion->answers[] = $ananswer;

            }
            if ($this->options->correctness) {
                $ananswer->feedbackimage = $this->displayoptions[$iscorrect]->image;
            }
            $data->parts[$currentpartindex]->questions[] = $aquestion;
            if ($nextpart && $lastindex >= $nextpart->get('start')) {
                $nextpart = empty($parts) ? null : array_shift($parts);
                $currentpartindex++;
            }
            $lastindex++;
        }
        return $data;
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
