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
use qtype_answersheet\local\api\answersheet;
use qtype_answersheet\local\persistent\answersheet_answers;
use qtype_answersheet\local\persistent\answersheet_module;

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
    const QUESTION_TEXT = 'THE QUICK BROWN FOX JUMPED OVER THE LAZY DOG.';

    /**
     * Generate a answersheet question.
     *
     * This is the basic question data used in tests. It will create a question with some files.
     *
     * @param string $which
     * @param array $overrides
     * @return array
     */
    protected static function create_question_data($which = 'standard', $overrides = []) {
        global $CFG;
        $questiondata = self::get_common_question_data();
        // Set the question type.
        $questiondata['qtype'] = 'answersheet';
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
        $questiondata['id'] = 0;
        $questiondata['category'] = 0; // This will be set by the question form.
        $questiondata['contextid'] = context_system::instance()->id;
        return $questiondata;
    }

    /**
     * Get common question data for answersheet questions.
     *
     * @param string $which
     * @param array $overrides
     * @return array
     */
    public static function get_common_question_data($which = 'standard', $overrides = []) {
        $questiondata = [
            'name' => 'Test question',
            'questiontext' => '<strong>The quick brown fox jumped over the lazy dog.</strong>',
            'questiontextformat' => FORMAT_HTML,
            'defaultmark' => 1.0,
            'idnumber' => '',
            'startnumbering' => 2,
            'shownumcorrect' => true,
            'penalty' => 0.3333333,
            'qtype' => 'answersheet',
            'makecopy' => 0,
            'updatebutton' => 'Save changes and continue editing',
            'modulename' => 'mod_quiz',
            'audioitem' => 1,
            'documentitem' => 1,
            'audioname' => ['Test audio file'],
            'documentname' => ['Test document file'],
            'correctfeedback' => '<p>Your answer is correct.</p>',
            'correctfeedbackformat' => FORMAT_HTML,
            'partiallycorrectfeedback' => '<p>Your answer is partially correct.</p>',
            'partiallycorrectfeedbackformat' => FORMAT_HTML,
            'incorrectfeedback' => '<p>Your answer is incorrect.</p>',
            'incorrectfeedbackformat' => FORMAT_HTML,
            'parent' => 0,
            'generalfeedback' => '<p>General feedback for the question.</p>',
            'generalfeedbackformat' => FORMAT_HTML,

        ];
        // Merge overrides into the question data.
        foreach ($overrides as $key => $value) {
            $questiondata[$key] = $value;
        }
        return $questiondata;
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
     * Get possible test questions
     *
     * @return string[]
     */
    public function get_test_questions() {
        return ['standard'];
    }

    /**
     *
     * Make answersheet question standard
     *
     * This is used by the generator so we need to have this available. The type of question is 'standard'.
     * Note that this will create a real question in the database and it will be used in Behat tests.
     *
     * @return object
     */
    public function get_answersheet_question_form_data_standard() {
        $questiondata = self::create_question_form_data('standard');

        $form = (object) $questiondata;
        $form->name = 'Test answersheet';
        test_question_maker::set_standard_combined_feedback_form_data($form);
        $form->qtype = question_bank::get_qtype('answersheet');
        $form->jsonquestions = $this->get_sample_new_question();
        return $form;
    }

    /**
     * Generate a answersheet question.
     *
     * This is the basic question data used in tests. It will create a question with some files.
     *
     * @param string $which
     * @param array $overrides
     * @return array
     */
    protected static function create_question_form_data($which = 'standard', $overrides = []) {
        global $CFG;
        $questiondata = self::get_common_question_data();
        // Add standard feedback.
        $questiondata['correctfeedback'] = [
            'text' => $questiondata['correctfeedback'],
            'format' => FORMAT_HTML,
        ];
        $questiondata['partiallycorrectfeedback'] = [
            'text' => $questiondata['partiallycorrectfeedback'],
            'format' => FORMAT_HTML,
        ];
        $questiondata['incorrectfeedback'] = [
            'text' => $questiondata['incorrectfeedback'],
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

        return $questiondata;
    }

    /**
     * Get sample new question data for answersheet.
     * This is a JSON string representing a new answersheet question with two modules.
     *
     * @return string
     */
    protected function get_sample_new_question() {
        return json_encode(
            [
                [
                    'sortorder' => 0,
                    'name' => 'Module 1',
                    'type' => 1,
                    'numoptions' => 4,
                    'rows' =>
                        [

                            [
                                'sortorder' => 1,
                                'cells' =>
                                    [
                                        [
                                            'type' => 'text',
                                            'column' => 'name',
                                            'value' => '1',
                                        ],
                                        [
                                            'type' => 'select',
                                            'column' => 'options',
                                            'value' => ['-', 'A', 'B', 'C', 'D'],
                                        ],
                                        [
                                            'type' => 'text',
                                            'column' => 'answer',
                                            'value' => 'A',
                                        ],
                                        [
                                            'type' => 'text',
                                            'column' => 'feedback',
                                            'value' => '',
                                        ],
                                    ],
                            ],

                            [
                                'sortorder' => 2,
                                'cells' =>
                                    [
                                        [
                                            'type' => 'text',
                                            'column' => 'name',
                                            'value' => '2',
                                        ],
                                        [
                                            'type' => 'select',
                                            'column' => 'options',
                                            'value' => ['-', 'A', 'B', 'C', 'D'],
                                        ],
                                        [
                                            'type' => 'text',
                                            'column' => 'answer',
                                            'value' => 'B',
                                        ],
                                        [
                                            'type' => 'text',
                                            'column' => 'feedback',
                                            'value' => '',
                                        ],
                                    ],
                            ],
                        ],
                ],

                [
                    'sortorder' => 2,
                    'name' => 'Module 2',
                    'type' => 2,
                    'numoptions' => 4,
                    'rows' =>
                        [
                            [
                                'sortorder' => 1,
                                'cells' =>
                                    [
                                        [
                                            'type' => 'text',
                                            'column' => 'name',
                                            'value' => '1',
                                        ],
                                        [
                                            'type' => 'select',
                                            'column' => 'options',
                                            'value' => '',
                                        ],
                                        [
                                            'type' => 'text',
                                            'column' => 'answer',
                                            'value' => 'Answer 1',
                                        ],
                                        [
                                            'type' => 'text',
                                            'column' => 'feedback',
                                            'value' => '',
                                        ],
                                    ],
                            ],

                            [
                                'sortorder' => 2,
                                'cells' =>
                                    [
                                        [
                                            'type' => 'text',
                                            'column' => 'name',
                                            'value' => '2',
                                        ],

                                        [
                                            'type' => 'select',
                                            'column' => 'options',
                                            'value' => '',
                                        ],
                                        [
                                            'type' => 'text',
                                            'column' => 'answer',
                                            'value' => 'Answer 2',
                                        ],
                                        [
                                            'type' => 'text',
                                            'column' => 'feedback',
                                            'value' => '',
                                        ],
                                    ],
                            ],
                        ],
                ],
                [
                    'sortorder' => 3,
                    'name' => 'Module 3',
                    'type' => 3,
                    'numoptions' => 4,
                    'rows' =>
                        [
                            [
                                'sortorder' => 1,
                                'cells' =>
                                    [
                                        [
                                            'type' => 'text',
                                            'column' => 'name',
                                            'value' => '1',
                                        ],
                                        [
                                            'type' => 'select',
                                            'column' => 'options',
                                            'value' => '',
                                        ],
                                        [
                                            'type' => 'text',
                                            'column' => 'answer',
                                            'value' => 'Text 1',
                                        ],
                                        [
                                            'type' => 'text',
                                            'column' => 'feedback',
                                            'value' => '',
                                        ],
                                    ],
                            ],
                            [
                                'sortorder' => 2,
                                'cells' =>
                                    [
                                        [
                                            'type' => 'text',
                                            'column' => 'name',
                                            'value' => '2',
                                        ],
                                        [
                                            'type' => 'select',
                                            'column' => 'options',
                                            'value' => '',
                                        ],
                                        [
                                            'type' => 'text',
                                            'column' => 'answer',
                                            'value' => 'Text 2',
                                        ],
                                        [
                                            'type' => 'text',
                                            'column' => 'feedback',
                                            'value' => '',
                                        ],
                                    ],
                            ],
                        ],
                ],
            ],
            JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * Create a standard answersheet question without using the question form and persisting data.
     * Called test_question_maker::make_question('answersheet');
     * This will avoid creating any entry in the database so everything is done in memory.
     *
     * @param array $overrides
     * @return qtype_answersheet_question
     */
    public function make_answersheet_question_standard($overrides = []) {
        // Here we create a sample question without using the question form.
        question_bank::load_question_definition_classes('answersheet');
        $q = new qtype_answersheet_question();
        // Merge this with actual question data.
        $questiondata = self::get_common_question_data('standard', $overrides);
        $questiondata = (object) $questiondata;
        $questiondata->id = 1; // Set a default ID for the question.
        foreach ($questiondata as $key => $value) {
            if (property_exists($q, $key)) {
                $q->{$key} = $value;
            }
        }
        $q->qtype = question_bank::get_qtype('answersheet');
        $q->version = 1; // Set a default version for the question.
        $questioninfo = $this->get_sample_new_question();
        $q->modules = [];
        $q->answersheets = [];
        $jsondata = json_decode($questioninfo, true);
        [$answerdata,$q->modules, $q->answersheets] = $this->fill_question_data($jsondata);
        @$q->answers = $answerdata;

        return $q;
    }

    /**
     * Génère la structure extradatainfo pour les tests à partir de données simplifiées
     *
     * @param array $modulesdefinition Tableau des modules avec leurs réponses
     * @return array Structure extradatainfo complète
     */
    protected function fill_question_data(array $modulesdefinition): array {
        $moduleid = 403000;
        $answersheetid = 400000;
        $answerid = 500000;
        $modules = [];
        $answersheets = [];
        $answerdata = [];
        foreach ($modulesdefinition as $index => $moduledef) {
            $type = $moduledef['type'] ?? answersheet_module::RADIO_CHECKED; // Default to type 1 if not set.
            $newmodule =  new answersheet_module(0);
            $newmodule->from_record((object)[
                'id' => $moduleid,
                'sortorder' => $moduledef['sortorder'] ?? 0,
                'name' => $moduledef['name'] ?? 'Module ' . ($index + 1),
                'numoptions' => $moduledef['numoptions'] ?? 4,
                'type' => $type,
            ]);
            $modules[$moduleid] = $newmodule;
            foreach ($moduledef['rows'] as $row) {
                $newanswersheet = new answersheet_answers(0);
                $newanswersheet = $newanswersheet->from_record((object)[
                    'id' => $answersheetid,
                    'answerid' => $answerid,
                    'moduleid' => $moduleid,
                    'name' => $row['cells'][0]['value'] ?? '-',
                    'options' => json_encode($row['cells'][1]['value'] ?? []),
                    'value' => $row['cells'][2]['value'] ?? '',
                ]);
                $expectedvalue = '';
                foreach ($row['cells'] as $cell) {
                    if ($cell['column'] === 'answer') {
                        $expectedvalue = $cell['value'] ?? '';
                    }
                }
                // Initialize answers property.
                $answerdata[$answerid] = new question_answer(
                    $answerid,
                    $expectedvalue,
                    1,
                    '',
                    FORMAT_PLAIN,
                );
                $answersheets[$moduleid][] = $newanswersheet;

                $answerid++;
                $answersheetid++;
            }
            $moduleid++;
        }

        return [$answerdata, $modules, $answersheets];
    }


    /**
     * Get right responses that would end up being submitted (integer for choice)
     * (Radio will be 1, 2, ...)
     * @return array The expected response for the restored question.
     */
    public function get_right_machine_response(question_definition $question): array {
        $responsekeys = array_map(function($key) {
            return 'answer' . $key;
        }, array_keys($question->answers));
        return array_combine($responsekeys, [
            1, // Radio 1.
            2, // Radio 2.
            'Answer 1',
            'Answer 2',
            'Text 1',
            'Text 2',
        ]);
    }

    /**
     * Get right responses that would end up being submitted (integer for choice)
     * (Radio will be 1, 2, ...)
     * @return array The expected response for the restored question.
     */
    public function get_full_wrong_machine_response(question_definition $question): array {
        $responsekeys = array_map(function($key) {
            return 'answer' . $key;
        }, array_keys($question->answers));
        return array_combine($responsekeys, [
            2, // Radio 1.
            1, // Radio 2.
            'Answer 3',
            'Answer 4',
            'Text 5',
            'Text 6',
        ]);
    }
}

