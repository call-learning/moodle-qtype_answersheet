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

namespace qtype_answersheet;

use core\persistent;

defined('MOODLE_INTERNAL') || die();

/**
 * The answersheet question parts
 *
 * @package     qtype_answersheet
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class answersheet_parts extends persistent {
    /** @var string TABLE */
    const TABLE = 'qtype_answersheet_parts';

    /**
     * Add data to question
     *
     * @param object $question either form data or the question_type itself
     * @throws \coding_exception
     */
    public static function add_data($question) {
        $partsforthisquestion = static::get_records(
            array('questionid' => $question->id)
        );
        foreach ($partsforthisquestion as $part) {
            $question->parts[] = [
                'partstart' => $part->get('start'),
                'partname' => $part->get('name')
            ];
        }
    }

    /**
     * Properties
     *
     * @return array[]
     */
    protected static function define_properties() {
        return array(
            'questionid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
            ],
            'start' => [
                'default' => 1,
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
            ],
            'name' => [
                'type' => PARAM_TEXT,
            ]
        );
    }

}
