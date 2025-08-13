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
 * TODO describe file viewdoc
 *
 * @package    qtype_answersheet
 * @copyright  2025 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');



$cmid = required_param('cmid', PARAM_INT);
$docid = required_param('docid', PARAM_INT);
$qcid = required_param('qcid', PARAM_INT);
$qubaid = required_param('qubaid', PARAM_INT);
$slot = required_param('slot', PARAM_INT);

$cm = get_coursemodule_from_id('quiz', $cmid);
$course = $DB->get_record('course', ['id' => $cm->course]);
$context = context_module::instance($cm->id);
require_login($course);

$url = new moodle_url('/question/type/answersheet/viewdoc.php', ['cmid' => $cmid, 'docid' => $docid]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('popup');
$PAGE->add_body_class('answersheet-embed');

function get_docfile_url($files, $qubaid, $slot) {
    if ($files) {
        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }
            $url = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                "$qubaid/$slot/{$file->get_itemid()}",
                $file->get_filepath(),
                $file->get_filename()
            );
            return $url->out();
        }
    }
}

$PAGE->set_heading($SITE->fullname);
$fs = get_file_storage();
$files = $fs->get_area_files(
    $qcid,
    'qtype_answersheet',
    'document',
    $docid,
    'id'
);
$url = get_docfile_url($files, $qubaid, $slot);

echo $OUTPUT->header();
if ($url) {
    echo '<object id="documentpdf-{{uniqid}}" data="' . $url . '" type="application/pdf" class="fulleverything">
        <param name="src" value="' . $url . '"/>
    </object>';
} else {
    echo html_writer::tag('p', get_string('nodocument', 'qtype_answersheet'));
}
echo $OUTPUT->footer();
