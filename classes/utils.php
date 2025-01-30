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
defined('MOODLE_INTERNAL') || die();

/**
 * The editing form for answersheet question type is defined here.
 *
 * @package     qtype_answersheet
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {
    /**
     * Option count for each answer.
     */
    const OPTION_COUNT = 4;

    /**
     * Number of answer by default.
     */
    const BASE_ANSWER_COUNT = 100;

    /**
     * Option for filemanager
     */
    const FILEMANAGER_OPTIONS = [
        'audio' => array('accepted_types' => 'web_audio', 'maxbytes' => 0, 'maxfiles' => 1, 'subdirs' => 0),
        'document' => array('accepted_types' => 'pdf', 'maxbytes' => 0, 'maxfiles' => 1, 'subdirs' => 0),
        'correctfeedback' => array('trusttext' => true, 'subdirs' => true),
        'partiallycorrectfeedback' => array('trusttext' => true, 'subdirs' => true),
        'incorrectfeedback' => array('trusttext' => true, 'subdirs' => true)
    ];

    /**
     * Filearea for this plugin
     *
     * @return string[]
     */
    public static function get_basic_fileareas() {
        return [
                'correctfeedback',
                'partiallycorrectfeedback',
                'incorrectfeedback'
            ];
    }
    /**
     * Filearea for this plugin
     *
     * @return string[]
     */
    public static function get_fileareas() {
        return static::get_basic_fileareas()
            + array_values(answersheet_docs::DOCUMENT_TYPE_SHORTNAMES);
    }
    /**
     * File manager options
     *
     * @param string $type
     * @return string[]
     */
    public static function file_manager_options($type) {
        return self::FILEMANAGER_OPTIONS[$type];
    }
}
