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
use stdClass;

/**
 * Class sprogramme_module
 *
 * @package    qtype_answersheet
 * @copyright  2025 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class answersheet_module extends persistent {
    /**
     * Current table
     */
    const TABLE = 'qtype_answersheet_module';

    /**
     * Check the correct answer radio button
     */
    const RADIO_CHECKED = 1;

    /**
     * Enter the correct answer letter by letter
     */
    const LETTER_BY_LETTER = 2;

    /**
     * Enter the correct answer in a text area
     */
    const FREE_TEXT = 3;

    /**
     * Types definition
     */
    const TYPES = [
        self::RADIO_CHECKED => 'radiochecked',
        self::LETTER_BY_LETTER => 'letterbyletter',
        self::FREE_TEXT => 'freetext',
    ];

    /**
     * Return the custom definition of the properties of this model.
     *
     * Each property MUST be listed here.
     *
     * @return array Where keys are the property names.
     */
    protected static function define_properties() {
        return [
            'questionid' => [
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'qtype_answersheet', 'sprogramme_module:questionid'),
            ],
            'sortorder' => [
                'default' => '',
                'null' => NULL_ALLOWED,
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'qtype_answersheet', 'sprogramme_module:sortorder'),
            ],
            'name' => [
                'type' => PARAM_TEXT,
                'message' => new lang_string('invaliddata', 'qtype_answersheet', 'sprogramme_module:name'),
            ],
            'numoptions' => [
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'qtype_answersheet', 'sprogramme_module:numoptions'),
            ],
            'type' => [
                'type' => PARAM_INT,
                'default' => self::RADIO_CHECKED,
                'message' => new lang_string('invaliddata', 'qtype_answersheet', 'sprogramme_module:type'),
                'choices' => array_keys(self::TYPES),
            ],
            'questionpoints' => [
                'type' => PARAM_INT,
                'message' => new lang_string('invaliddata', 'qtype_answersheet', 'sprogramme_module:questionpoints'),
                'default' => 1,
            ],
        ];
    }

    /**
     * Get all records for a given question
     * @param int $questionid
     * @return array
     */
    public static function get_all_records_for_question(int $questionid): array {
        return self::get_records(['questionid' => $questionid], 'sortorder');
    }

    /**
     * Get the indicator for this module.
     * @return string
     */
    public function get_indicator(): string {
        $options = $this->get('numoptions');
        $a = new stdClass();
        $a->options = $options;
        $a->lastletter = chr(65 + $options - 1);
        $stringname = 'indicator:' . self::TYPES[$this->get('type')];
        return get_string($stringname, 'qtype_answersheet', $a);
    }

    /**
     * Get the type string for this module.
     * @return string
     */
    public function get_class(): string {
        return self::TYPES[$this->get('type')];
    }

    /**
     * Types definition
     */
    public const TYPES_TO_RAW_TYPE = [
        self::RADIO_CHECKED => PARAM_INT,
        self::LETTER_BY_LETTER => PARAM_TEXT,
        self::FREE_TEXT => PARAM_TEXT,
    ];

    /**
     * Get the data type for this module.
     *
     * @return int|null
     */
    public function get_data_type() {
        $data = $this->raw_get('type');
        if (array_key_exists($data, self::TYPES_TO_RAW_TYPE)) {
            return self::TYPES_TO_RAW_TYPE[$data] ?? PARAM_TEXT;
        }
    }
}
