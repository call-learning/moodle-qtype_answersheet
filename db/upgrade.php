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
 * Multiple choice grid
 *
 * @package     qtype_answersheet
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade code for the multiple choice grid type.
 *
 * @param int $oldversion the version we are upgrading from.
 */
function xmldb_qtype_answersheet_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();
    if ($oldversion < 2025100401) {

        // Define field questionpoints to be added to qtype_answersheet_module.
        $table = new xmldb_table('qtype_answersheet_module');
        $field = new xmldb_field('questionpoints', XMLDB_TYPE_INTEGER, '10', null, null, null, '1', 'type');

        // Conditionally launch add field questionpoints.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $DB->set_field('qtype_answersheet_module', 'questionpoints', 1);
        // Answersheet savepoint reached.
        upgrade_plugin_savepoint(true, 2025100401, 'qtype', 'answersheet');
    }
    return true;
}
