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

/**
 * The answersheet attached documents
 *
 * @package     qtype_answersheet
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class answersheet_docs extends persistent {
    /** @var string TABLE */
    const TABLE = 'qtype_answersheet_docs';
    /**
     * Audio type
     */
    const AUDIO_FILE_TYPE = 1;
    /**
     * Document type
     */
    const DOCUMENT_FILE_TYPE = 2;

    /**
     * Document shortname equivalent with Type
     */
    const DOCUMENT_TYPE_SHORTNAMES = [
        self::AUDIO_FILE_TYPE => 'audio',
        self::DOCUMENT_FILE_TYPE => 'document',
    ];

    /**
     * Add data to question
     *
     * @param object $question either form data or the question_type itself
     * @throws \coding_exception
     */
    public static function add_data($question) {
        $docsforthisquestion = static::get_records(
            ['questionid' => $question->id]
        );
        foreach ($docsforthisquestion as $doc) {
            $type = $doc->get('type');
            $index = $doc->get('sortorder');
            $area = self::DOCUMENT_TYPE_SHORTNAMES[$type];
            if (empty($question->{$area . 'name'})) {
                $question->{$area . 'name'} = [];
            }
            $question->{$area . 'name'}[$index] = $doc->get('name');
        }
    }

    /**
     * Properties
     *
     * @return array[]
     */
    protected static function define_properties() {
        return [
            'type' => [
                'default' => self::AUDIO_FILE_TYPE,
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'choices' => [self::AUDIO_FILE_TYPE, self::DOCUMENT_FILE_TYPE],
            ],
            'name' => [
                'type' => PARAM_TEXT,
            ],
            'sortorder' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
            ],
            'questionid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
            ],
        ];
    }
}
