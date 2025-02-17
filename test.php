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
 * TODO describe file test
 *
 * @package    qtype_answersheet
 * @copyright  2025 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
use qtype_answersheet\output\answersheet;
use qtype_answersheet\output\sheet_renderer;

require_login();

$questionid = optional_param('questionid', 0, PARAM_INT);

$url = new moodle_url('/question/type/answersheet/test.php', ['questionid' => $questionid, 'questionid' => $questionid]);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();
$renderer = new sheet_renderer($PAGE, $OUTPUT);
$programm = new answersheet($questionid);
echo $renderer->render($programm);
echo $OUTPUT->footer();
