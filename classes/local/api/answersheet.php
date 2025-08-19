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

namespace qtype_answersheet\local\api;

use qtype_answersheet\local\persistent\answersheet_answers;
use qtype_answersheet\local\persistent\answersheet_module;
use question_definition;

/**
 * Class programme
 *
 * @package    qtype_answersheet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class answersheet {
    /**
     * Get the data for a given question definition.
     *
     * It is important to note that this function does not take a question id, but a question object so like this
     * it can be used in the question bank API to load the question and its modules (and for test purposes we can
     * provide a question object directly).
     *
     * @param \question_definition $question The question object to get the data for.
     *
     * @return array $data
     */
    public static function get_data(question_definition $question): array {
        $columns = self::get_column_structure();
        $data = [];
        foreach ($question->modules ?? [] as $module) {
            $records = $question->answersheets[$module->get('id')] ?? [];
            $modulerows = [];
            foreach ($records as $record) {
                $row = [];
                foreach ($columns as $column) {
                    $row[] = [
                        'column' => $column['column'],
                        'value' => $record->get($column['column']),
                        'type' => $column['type'],
                        'visible' => $column['visible'],
                    ];
                }
                $modulerows[] = [
                    'id' => $record->get('id'),
                    'sortorder' => $record->get('sortorder'),
                    'cells' => $row,
                    'answerid' => $record->get('answerid'),
                ];
            }
            $data[] = [
                'id' => $module->get('id'),
                'modulename' => $module->get('name'),
                'modulesortorder' => $module->get('sortorder'),
                'numoptions' => $module->get('numoptions'),
                'type' => $module->get('type'),
                'class' => $module->get_class(),
                'indicator' => $module->get_indicator(),
                'rows' => $modulerows,
                'columns' => $columns,
            ];
        }
        return $data;
    }

    /**
     * Get the column structure for the custom field
     *
     * @return array $columns
     */
    public static function get_column_structure(): array {
        $table = self::get_table_structure();
        return array_values($table);
    }

    /**
     * Get the table structure for the custom field
     *
     * @return array $table
     */
    public static function get_table_structure(): array {
        $columns = [
            [
                'column' => 'name',
                'type' => PARAM_TEXT,
                'text' => true,
                'visible' => true,
                'canedit' => true,
                'label' => 'No',
                'columnid' => 1,
                'length' => 50,
                'field' => 'text',
                'sample_value' => 'A',
            ],
            [
                'column' => 'options',
                'type' => 'select',
                'select' => true,
                'visible' => true,
                'canedit' => true,
                'label' => 'Correct',
                'columnid' => 3,
                'length' => 1000,
                'field' => 'select',
                'sample_value' => '...',
                'options' => [
                    [
                        'name' => '-',
                        'selected' => true,
                    ],
                    [
                        'name' => 'A',
                        'selected' => false,
                    ],
                    [
                        'name' => 'B',
                        'selected' => false,
                    ],
                    [
                        'name' => 'C',
                        'selected' => false,
                    ],
                    [
                        'name' => 'D',
                        'selected' => false,
                    ],
                    [
                        'name' => 'E',
                        'selected' => false,
                    ],
                    [
                        'name' => 'F',
                        'selected' => false,
                    ],
                    [
                        'name' => 'G',
                        'selected' => false,
                    ],
                    [
                        'name' => 'H',
                        'selected' => false,
                    ],
                    [
                        'name' => 'I',
                        'selected' => false,
                    ],
                    [
                        'name' => 'J',
                        'selected' => false,
                    ],
                ],
            ],
            [
                'column' => 'answer',
                'type' => PARAM_TEXT,
                'text' => true,
                'visible' => true,
                'canedit' => true,
                'label' => 'Text',
                'columnid' => 4,
                'length' => 1000,
                'field' => 'select',
                'sample_value' => '...',
            ],
            [
                'column' => 'feedback',
                'type' => PARAM_TEXT,
                'text' => true,
                'visible' => false,
                'canedit' => true,
                'label' => 'Feedback',
                'columnid' => 5,
                'length' => 1000,
                'field' => 'select',
                'sample_value' => '...',
            ],
        ];
        return $columns;
    }

    /**
     * Populate the question data with answersheet data
     *
     * @param \question_definition $question The question object to populate.
     */
    public static function add_data(\question_definition $question): void {
        // Using the extra_answer_fields extension is making sure that the question->options->answers contains the answersheet data.
        // Except that we don't have the answersheet element id.)
        $question->modules = answersheet_module::get_all_records_for_question($question->id);
        $question->answersheets = [];
        foreach ($question->modules as $module) {
            $question->answersheets[$module->get('id')] = answersheet_answers::get_all_records_for_module($module->get('id'));
        }
        //$question->extraanswerfields = [];
        //$question->extraanswerdatatypes = [];
        //foreach($question->answers as $answerid => $answer) {
        //    $extraanswer = answersheet_answers::get_record([
        //        'answerid' => $answerid
        //    ]);
        //
        //    if ($extraanswer) {
        //        $answerfieldstokeep = $this->extra_answer_fields();
        //        $answerfieldstokeep = array_fill_keys($answerfieldstokeep, 1);
        //        $answerfieldstokeep['answerid'] = 1;
        //        $answerfieldstokeep['id'] = 1;
        //        $question->extraanswerfields[] =
        //            array_intersect_key(
        //                (array) $extraanswer->to_record(),
        //                $answerfieldstokeep
        //            );
        //        $question->extraanswerdatatypes[] = [
        //            'answerid' => $answerid,
        //            'datatype' => $extraanswer->get_module_data_type(),
        //            'type' => $extraanswer->get_module_type()
        //        ];
        //    }
        //}
        //        $question->extradatainfo = answersheet_api::get_data($question->id); // Intialise the extra output info for display.
        //        $question->answersheet = new stdClass();
        //        $question->answersheet->modules = self::get_data($question->id);
        //        $question->answersheet->columns = self::get_column_structure();
        //        $question->answersheet->table = self::get_table_structure();
    }

    /**
     * Normalise the answer value so we can store it in the database.
     *
     * @param mixed $value The value to normalise.
     * @param answersheet_answers $answersheet The answersheet answers object.
     * @return mixed The returned value once it has been normalised.
     */
    public static function to_stored_value(mixed $value, answersheet_answers $answersheet): mixed {
        switch ($answersheet->get_module_type()) {
            case answersheet_module::RADIO_CHECKED:
                // For radio checked, we store the answer as an integer, so we need to find the order of the answer.
                $options = $answersheet->get('options'); // TODO: add a way to get the options as array via persistent.
                $options = json_decode($options, true);
                $options = array_flip($options);
                if (isset($options[$value])) {
                    $answervalue = $options[$value];
                    return intval($answervalue);
                }
                return 0; // If the answer is not in the options, return 0.
            case answersheet_module::FREE_TEXT:
            case answersheet_module::LETTER_BY_LETTER:
            default:
                return trim($value);
        }
    }

    /**
     * Denormalise the answer so it can be displayed in the question.
     *
     * @param mixed $value The value to normalise.
     * @param answersheet_answers $answersheet The answersheet answers object.
     * @return string The returned value once it has been denormalised.
     */
    public static function to_human_value(mixed $value, answersheet_answers $answersheet): string {
        switch ($answersheet->get_module_type()) {
            case answersheet_module::RADIO_CHECKED:
                // For radio checked, we store the answer as an integer, so we need to find the order of the answer.
                $options = $answersheet->get('options');
                if ($options) {
                    $options = json_decode($options, true);
                    if (isset($options[$value])) {
                        return $value;
                    }
                }
                return ''; // If the answer is not in the options, return ''.
                break;
            case answersheet_module::FREE_TEXT:
            case answersheet_module::LETTER_BY_LETTER:
            default:
                return trim($value);
        }
    }
}
