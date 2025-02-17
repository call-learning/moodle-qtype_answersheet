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

/**
 * Plugin strings are defined here.
 *
 * @package     qtype_answersheet
 * @category    string
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Answer sheet';
$string['pluginname_help'] = 'Answer sheet type of question with audio and pdf file';
$string['pluginnameediting'] = 'Editing a Answer sheet question';
$string['pluginnameadding'] = 'Adding a Answer sheet question';
$string['pluginname_link'] = 'question/type/answersheet';
$string['pluginnamesummary'] = 'Answer sheet type of question are used to display a main document and a series of multiple
 choices questions.';

$string['audio:title'] = 'Audio files';
$string['audio'] = 'Audio files {no}';
$string['audioname'] = 'Audio files name {no}';
$string['document:title'] = 'Documents';
$string['document'] = 'Documents {no}';

$string['documentname'] = 'Document name {no} ';
$string['editor'] = 'Editor files';

$string['answerheader'] = 'Answers';
$string['numberofanswers'] = 'Number of answers';
$string['answer'] = 'Answer {no}';
$string['answer_help'] = 'Answer {no} help';
$string['option:1'] = 'A';
$string['option:2'] = 'B';
$string['option:3'] = 'C';
$string['option:4'] = 'D';
$string['option'] = '{$a}';
$string['startnumbering'] = 'Start numbering';
$string['partsheader'] = 'Parts';
$string['parts'] = 'Parts {no}';
$string['partstart'] = 'Index from';
$string['partname'] = 'Part name';

$string['row'] = 'Row {$a}';
$string['save'] = 'Save';
$string['saving'] = 'Saving...';
$string['addmodule'] = 'Add module';
$string['addrow'] = 'Add row';
$string['invaliddata'] = 'Invalid data: {$a}';
$string['answersheet:edit'] = 'Edit the Answer sheet';
$string['answersheet:view'] = 'View the Answer sheet';
$string['modulename'] = 'Module name';
$string['type'] = 'Type';
$string['radiochecked'] = 'Radio checked';
$string['letterbyletter'] = 'Letter by letter';
$string['freetext'] = 'Free text';
$string['numoptions'] = 'Number of options';
$string['indicator:radiochecked'] = '{$a->options} ( A - {$a->lastletter} )';
$string['indicator:letterbyletter'] = '{$a->options} letters';
$string['indicator:freetext'] = 'Free text';