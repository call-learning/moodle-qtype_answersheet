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

namespace qtype_answersheet\external;

require_once("$CFG->libdir/externallib.php");

use context_system;
use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;

use qtype_answersheet\local\api\answersheet;

/**
 * Class get_columns
 *
 * @package    qtype_answersheet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_columns extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'table' => new external_value(PARAM_TEXT, 'table', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Execute and return json data.
     *
     * @param string $table - The course module id
     * @return array $data - The plannings list
     * @throws \invalid_parameter_exception
     */
    public static function execute(string $table): array {
        global $CFG;
        $params = self::validate_parameters(self::execute_parameters(),
            [
                'table' => $table,
            ]
        );
        self::validate_context(context_system::instance());
        $table = $params['table'];

        $columns = answersheet::get_column_structure();
        return [
            'columns' => $columns,
        ];
    }

    /**
     * Returns description of method return value
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'columns' => new external_multiple_structure(
                new external_single_structure([
                    'column' => new external_value(PARAM_TEXT, 'Column id', VALUE_REQUIRED),
                    'type' => new external_value(PARAM_TEXT, 'Type', VALUE_REQUIRED),
                    'float' => new external_value(PARAM_BOOL, 'Float', VALUE_OPTIONAL),
                    'int' => new external_value(PARAM_BOOL, 'Int', VALUE_OPTIONAL),
                    'text' => new external_value(PARAM_BOOL, 'Text', VALUE_OPTIONAL),
                    'select' => new external_value(PARAM_BOOL, 'Select', VALUE_OPTIONAL),
                    'visible' => new external_value(PARAM_BOOL, 'Visible', VALUE_REQUIRED),
                    'canedit' => new external_value(PARAM_BOOL, 'Admin', VALUE_REQUIRED),
                    'label' => new external_value(PARAM_TEXT, 'Label', VALUE_REQUIRED),
                    'columnid' => new external_value(PARAM_INT, 'Column id', VALUE_REQUIRED),
                    'length' => new external_value(PARAM_INT, 'Length', VALUE_REQUIRED),
                    'field' => new external_value(PARAM_TEXT, 'Field', VALUE_REQUIRED),
                    'sample_value' => new external_value(PARAM_TEXT, 'Sample value', VALUE_REQUIRED),
                    'min' => new external_value(PARAM_INT, 'Min', VALUE_OPTIONAL),
                    'max' => new external_value(PARAM_INT, 'Max', VALUE_OPTIONAL),
                    'options' => new external_multiple_structure(
                        new external_single_structure([
                            'name' => new external_value(PARAM_TEXT, 'Name', VALUE_REQUIRED),
                            'selected' => new external_value(PARAM_BOOL, 'Selected', VALUE_REQUIRED),
                        ]), 'Option', VALUE_OPTIONAL
                    ),
                ])
            )]
        );
    }
}
