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
 * Test helpers for the answersheet onto image question type.
 *
 * @package     qtype_answersheet
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

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
            'fraction' => 0
        ]
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
     * Get possible test questions
     *
     * @return string[]
     */
    public function get_test_questions() {
        return array('ten');
    }

    /**
     * Make mcqgrid question ten
     *
     * @return qtype_answersheet_question
     */
    public function make_answersheet_question_ten() {
        question_bank::load_question_definition_classes('answersheet');
        $dd = new qtype_answersheet_question();

        test_question_maker::initialise_a_question($dd);

        $dd->name = 'Test answersheet';
        $dd->questiontext = self::QUESTION_TEXT;
        $dd->generalfeedback = 'This sentence uses each letter of the alphabet.';
        $dd->qtype = question_bank::get_qtype('answersheet');
        $dd->penalty = 0.5; // For interactive behaviour.
        test_question_maker::set_standard_combined_feedback_fields($dd);
        foreach (self::TEN_QUESTIONS as $key => $answer) {
            $dd->answers[$key] =
                new question_answer($key, $answer['rightanswer'], $answer['fraction'], $answer['feedback'], FORMAT_HTML);
        }
        $dd->startnumbering = 1;
        $dd->parts = [
            [
                'partstart' => 1,
                'partname' => 'Part 1'
            ],
            [
                'partstart' => 5,
                'partname' => 'Part 2'
            ]
        ];
        return $dd;
    }

    /**
     * Make mcqgrid question ten
     *
     * @return object
     */
    public function get_answersheet_question_form_data_ten() {
        global $CFG;
        $form = new stdClass();
        $form->name = 'Test answersheet';
        test_question_maker::set_standard_combined_feedback_form_data($form);
        $form->audio = [
            self::create_fixture_draft_file($CFG->dirroot .
                '/question/type/answersheet/tests/fixtures/bensound-littleplanet.mp3')];
        $form->audioname = ['Audio 1'];
        $form->document = [
            self::create_fixture_draft_file($CFG->dirroot . '/question/type/answersheet/tests/fixtures/document.pdf')];
        $form->documentname = ['Document 1'];
        $form->noanswers = count(self::TEN_QUESTIONS);
        $form->answers = [];
        $form->feedback = [];
        $form->fraction = [];
        foreach (self::TEN_QUESTIONS as $key => $answer) {
            $form->answer[] = $answer['rightanswer'];
            $form->feedback[] = $answer['feedback'];
            $form->fraction[] = $answer['fraction'];
        }

        $form->startnumbering = 1;
        $form->parts = [
            [
                'partstart' => 1,
                'partname' => 'Part 1'
            ],
            [
                'partstart' => 5,
                'partname' => 'Part 2'
            ]
        ];
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
}
