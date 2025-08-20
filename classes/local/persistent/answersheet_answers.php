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

namespace qtype_answersheet\local\persistent;

use core\persistent;
use lang_string;

/**
 * Class sprogramme
 *
 * @package    qtype_answersheet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class answersheet_answers extends persistent {
    /**
     * Current table
     */
    const TABLE = 'qtype_answersheet_answers';

    /**
     * Return the custom definition of the properties of this model.
     *
     * Each property MUST be listed here.
     *
     * @return array Where keys are the property names.
     */
    protected static function define_properties() {
        return [
            'answerid' => [
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'qtype_answersheet', 'sprogramme:answerid'),
            ],
            'moduleid' => [
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'qtype_answersheet', 'sprogramme:moduleid'),
            ],
            'sortorder' => [
                'default' => '',
                'null' => NULL_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'qtype_answersheet', 'sprogramme:sortorder'),
            ],
            'name' => [
                'type' => PARAM_TEXT,
                'message' => new lang_string('invaliddata', 'qtype_answersheet', 'sprogramme:name'),
            ],
            'options' => [
                'default' => '',
                'null' => NULL_ALLOWED,
                'type' => PARAM_TEXT,
                'message' => new lang_string('invaliddata', 'qtype_answersheet', 'sprogramme:consignes'),
            ],
            'answer' => [
                'default' => '',
                'null' => NULL_ALLOWED,
                'type' => PARAM_TEXT,
                'message' => new lang_string('invaliddata', 'qtype_answersheet', 'sprogramme:consignes'),
            ],
            'feedback' => [
                'default' => '',
                'null' => NULL_ALLOWED,
                'type' => PARAM_TEXT,
                'message' => new lang_string('invaliddata', 'qtype_answersheet', 'sprogramme:consignes'),
            ],
        ];
    }

    /**
     * Get all records for a given question
     * @param int $questionid
     * @return array
     */
    public static function get_all_records_for_question(int $questionid): array {
        $allmodules = answersheet_module::get_records(['questionid' => $questionid], 'sortorder');
        if (empty($allmodules)) {
            return [];
        }
        // Loop through all modules to ensure we have the correct sort order.
        $allanswers = [];
        foreach ($allmodules as $module) {
            $answersheetanswers = self::get_records(['moduleid' => $module->get('id')], 'sortorder');
            if (empty($answersheetanswers)) {
                continue;
            }
            foreach ($answersheetanswers as $answer) {
                // Ensure we have the correct sort order.
                $allanswers[] = $answer;
            }
        }
        return $allanswers;
    }

    /**
     * Get all records for a given module
     * @param int $moduleid
     * @return array
     */
    public static function get_all_records_for_module(int $moduleid): array {
        return self::get_records(['moduleid' => $moduleid], 'sortorder');
    }
}
