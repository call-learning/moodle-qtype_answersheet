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
 * Test helper class for the answersheet onto image question type.
 *
 * @package     qtype_answersheet
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_answersheet_test_helper extends question_test_helper {
    /**
     * Question text
     */
    const QUESTION_TEXT = 'The quick brown fox jumped over the lazy dog.';
    /**
     * Ten questions
     */
    const TEN_QUESTIONS = [
        1234 => [
            'rightanswer' => '1',
            'feedback' => 'feedback',
            'fraction' => 0,
        ],
        1235 => [
            'rightanswer' => '2',
            'feedback' => 'feedback',
            'fraction' => 0,
        ],
        1236 => [
            'rightanswer' => '3',
            'feedback' => 'feedback',
            'fraction' => 0,
        ],
        1237 => [
            'rightanswer' => '4',
            'feedback' => 'feedback',
            'fraction' => 0,
        ],
        1238 => [
            'rightanswer' => '1',
            'feedback' => 'feedback',
            'fraction' => 0,
        ],
        1239 => [
            'rightanswer' => '1',
            'feedback' => 'feedback',
            'fraction' => 0,
        ],
        1240 => [
            'rightanswer' => '1',
            'feedback' => 'feedback',
            'fraction' => 0,
        ],
        1241 => [
            'rightanswer' => '1',
            'feedback' => 'feedback',
            'fraction' => 0,
        ],
        1242 => [
            'rightanswer' => '1',
            'feedback' => 'feedback',
            'fraction' => 0,
        ],
        1243 => [
            'rightanswer' => '1',
            'feedback' => 'feedback',
            'fraction' => 0,
        ],
    ];

    /**
     * Get answer field name by its parameter/order
     *
     * @param question_definition $dd
     * @param int $desiredindex
     * @return string
     */
    public static function get_fieldname_from_definition($dd, $desiredindex) {
        $index = 0;
        foreach (self::get_questions('answersheet') as $key => $val) {
            if ($index == $desiredindex) {
                return $dd->field($key);
            }
            $index++;
        }
        return '';
    }

    /**
     * Get questions
     *
     * @param string $qtype
     * @param null $which
     * @return array[]
     */
    public static function get_questions($qtype, $which = null) {
        return self::TEN_QUESTIONS;
    }

    /**
     * Helper to create a question which is fully right
     *
     * @param question_definition $dd
     * @return array
     * @throws coding_exception
     */
    public static function create_full_right_response(question_definition $dd) {
        foreach (self::get_questions('answersheet') as $key => $val) {
            $fullresponse[$dd->field($key)] = $val['rightanswer'];
        }
        return $fullresponse;
    }

    /**
     * Helper to create a question which is fully right
     *
     * @param question_definition $dd
     * @return array
     * @throws coding_exception
     */
    public static function create_full_wrong_response(question_definition $dd) {
        foreach (self::get_questions('answersheet') as $key => $val) {
            $fullresponse[$dd->field($key)] = (intval($val['rightanswer']) + 1) % 4 + 1;
        }
        return $fullresponse;
    }

    /**
     * Helper to create a question which contains the value given in parameter
     *
     * @param question_definition $dd
     * @param mixed $value
     * @return array
     * @throws coding_exception
     */
    public static function create_full_response_with_value(question_definition $dd, $value) {
        foreach (self::get_questions('answersheet') as $key => $val) {
            $fullresponse[$dd->field($key)] = $value;
        }
        return $fullresponse;
    }

    /**
     * Create a complete answersheet question for testing purposes.
     * This method is designed to work with backup/restore tests.
     *
     * @param object $questiongenerator The question generator instance
     * @param int $categoryid The question category ID
     * @return object The created question object
     */
    public static function create_test_question($questiongenerator, $categoryid) {
        // Use the specific answersheet generator if available
        $answersheetgenerator = new qtype_answersheet_generator($questiongenerator->get_data_generator());
        $questiondata = $answersheetgenerator->create_question('ten', ['category' => $categoryid]);

        return $questiongenerator->create_question('answersheet', 'ten', $questiondata);
    }

    /**
     * Get possible test questions
     *
     * @return string[]
     */
    public function get_test_questions() {
        return ['ten'];
    }

    /**
     * Generate a answersheet question.
     *
     * @param string $which
     * @param array $overrides
     * @return object
     */
    public function create_question_data($which = 'ten', $overrides = []) {
        global $CFG;

        $questiondata = [];

        // Set default values.
        $questiondata['name'] = 'Test answersheet question';
        $questiondata['questiontext'] = '<p>Test question</p>';
        $questiondata['generalfeedback'] = 'This sentence uses each letter of the alphabet.';
        $questiondata['penalty'] = 0.3333333;
        $questiondata['startnumbering'] = 3;
        $questiondata['audioitem'] = 1;
        $questiondata['documentitem'] = 1;
        $questiondata['shownumcorrect'] = 1;

        // Add standard feedback.
        $questiondata['correctfeedback'] = [
            'text' => '<p>Your answer is correct.</p>',
            'format' => FORMAT_HTML,
        ];
        $questiondata['partiallycorrectfeedback'] = [
            'text' => '<p>Your answer is partially correct.</p>',
            'format' => FORMAT_HTML,
        ];
        $questiondata['incorrectfeedback'] = [
            'text' => '<p>Your answer is incorrect.</p>',
            'format' => FORMAT_HTML,
        ];

        // Add hints.
        $questiondata['numhints'] = 2;
        $questiondata['hint'] = [
            ['text' => '', 'format' => FORMAT_HTML],
            ['text' => '', 'format' => FORMAT_HTML],
        ];
        $questiondata['hintclearwrong'] = [0, 0];
        $questiondata['hintshownumcorrect'] = [0, 0];

        // Create fixture files if they exist.
        if (file_exists($CFG->dirroot . '/question/type/answersheet/tests/fixtures/bensound-littleplanet.mp3')) {
            $questiondata['audio'] = [
                self::create_fixture_draft_file(
                    $CFG->dirroot . '/question/type/answersheet/tests/fixtures/bensound-littleplanet.mp3'
                ),
            ];
            $questiondata['audioname'] = ['Test audio file'];
        }

        if (file_exists($CFG->dirroot . '/question/type/answersheet/tests/fixtures/document.pdf')) {
            $questiondata['document'] = [
                self::create_fixture_draft_file(
                    $CFG->dirroot . '/question/type/answersheet/tests/fixtures/document.pdf'
                ),
            ];
            $questiondata['documentname'] = ['Test document file'];
        }

        // Add the newquestion structure.
        $questiondata['newquestion'] = $this->get_sample_new_question();

        // Apply any overrides.
        foreach ($overrides as $key => $value) {
            $questiondata[$key] = $value;
        }

        return $questiondata;
    }
    /**
     * Make mcqgrid question ten
     *
     * @return object
     */
    public function get_answersheet_question_form_data_ten() {
        global $CFG;
        $questiondata = $this->create_question_data('ten');
        $form = (object) $questiondata;
        $form->name = 'Test answersheet';
        test_question_maker::set_standard_combined_feedback_form_data($form);
        $form->qtype = question_bank::get_qtype('answersheet');
        return $form;
    }

    /**
     * Create fixture draft file
     *
     * @param string $originalfilepath
     * @return int
     * @throws file_exception
     * @throws stored_file_creation_exception
     */
    public static function create_fixture_draft_file($originalfilepath) {
        global $USER;
        $drafitemid = 0;
        file_prepare_draft_area($drafitemid, null, null, null, null);
        $fs = get_file_storage();
        $filerecord = new stdClass();
        $filerecord->contextid = context_user::instance($USER->id)->id;
        $filerecord->component = 'user';
        $filerecord->filearea = 'draft';
        $filerecord->itemid = $drafitemid;
        $filerecord->filepath = '/';
        $filerecord->filename = basename($originalfilepath);
        $fs->create_file_from_pathname($filerecord, $originalfilepath);
        return $drafitemid;
    }


    /**
     * Get sample new question data for answersheet.
     * This is a JSON string representing a new answersheet question with two modules.
     *
     * @return string
     */
    protected function get_sample_new_question() {
        return json_encode([
            [
                "id" => 7,
                "sortorder" => 0,
                "name" => "B",
                "type" => 2,
                "numoptions" => 6,
                "rows" => [
                    [
                        "id" => 31,
                        "sortorder" => 1,
                        "cells" => [
                            ["type" => "text", "column" => "name", "value" => "1"],
                            ["type" => "select", "column" => "options", "value" => ""],
                            ["type" => "text", "column" => "answer", "value" => "fdsds"],
                            ["type" => "text", "column" => "feedback", "value" => ""],
                        ],
                    ],
                    [
                        "id" => 32,
                        "sortorder" => 2,
                        "cells" => [
                            ["type" => "text", "column" => "name", "value" => "2"],
                            ["type" => "select", "column" => "options", "value" => "B"],
                            ["type" => "text", "column" => "answer", "value" => "fdsdfs"],
                            ["type" => "text", "column" => "feedback", "value" => ""],
                        ],
                    ],
                    [
                        "id" => 33,
                        "sortorder" => 3,
                        "cells" => [
                            ["type" => "text", "column" => "name", "value" => ""],
                            ["type" => "select", "column" => "options", "value" => ""],
                            ["type" => "text", "column" => "answer", "value" => "dfssdq"],
                            ["type" => "text", "column" => "feedback", "value" => ""],
                        ],
                    ],
                    [
                        "id" => 34,
                        "sortorder" => 4,
                        "cells" => [
                            ["type" => "text", "column" => "name", "value" => ""],
                            ["type" => "select", "column" => "options", "value" => ""],
                            ["type" => "text", "column" => "answer", "value" => "dfsdfsq"],
                            ["type" => "text", "column" => "feedback", "value" => ""],
                        ],
                    ],
                    [
                        "id" => 35,
                        "sortorder" => 5,
                        "cells" => [
                            ["type" => "text", "column" => "name", "value" => ""],
                            ["type" => "select", "column" => "options", "value" => ""],
                            ["type" => "text", "column" => "answer", "value" => ""],
                            ["type" => "text", "column" => "feedback", "value" => ""],
                        ],
                    ],
                ],
            ],
            [
                "id" => 8,
                "sortorder" => 2,
                "name" => " A",
                "type" => 1,
                "numoptions" => 5,
                "rows" => [
                    [
                        "id" => 36,
                        "sortorder" => 1,
                        "cells" => [
                            ["type" => "text", "column" => "name", "value" => ""],
                            ["type" => "select", "column" => "options", "value" => "E"],
                            ["type" => "text", "column" => "answer", "value" => ""],
                            ["type" => "text", "column" => "feedback", "value" => ""],
                        ],
                    ],
                    [
                        "id" => 37,
                        "sortorder" => 2,
                        "cells" => [
                            ["type" => "text", "column" => "name", "value" => ""],
                            ["type" => "select", "column" => "options", "value" => "F"],
                            ["type" => "text", "column" => "answer", "value" => ""],
                            ["type" => "text", "column" => "feedback", "value" => ""],
                        ],
                    ],
                    [
                        "id" => 38,
                        "sortorder" => 3,
                        "cells" => [
                            ["type" => "text", "column" => "name", "value" => ""],
                            ["type" => "select", "column" => "options", "value" => "B"],
                            ["type" => "text", "column" => "answer", "value" => ""],
                            ["type" => "text", "column" => "feedback", "value" => ""],
                        ],
                    ],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE);
    }
}
