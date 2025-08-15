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
 * External functions and service declaration for local_nixen
 *
 * Documentation: {@link https://moodledev.io/docs/apis/subsystems/external/description}
 *
 * @package    qtype_answersheet
 * @category   webservice
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'qtype_answersheet_get_columns' => [
        'classname' => \qtype_answersheet\external\get_columns::class,
        'methodname' => 'execute',
        'description' => 'Get the contents of a json file',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'qtype/answersheet:edit',
    ],
    'qtype_answersheet_get_data' => [
        'classname' => \qtype_answersheet\external\get_data::class,
        'methodname' => 'execute',
        'description' => 'Get the contents of the columns',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'qtype/answersheet:edit',
    ],
];
