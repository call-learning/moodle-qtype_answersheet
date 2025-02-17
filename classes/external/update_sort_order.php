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

namespace qtype_answersheet\external;

require_once("$CFG->libdir/externallib.php");

use context_system;
use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;

use qtype_answersheet\local\api\answersheet;

/**
 * Class update_sort_order
 *
 * @package    qtype_answersheet
 * @copyright  2025 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_sort_order extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'type' => new external_value(PARAM_TEXT, 'Type', VALUE_REQUIRED),
            'questionid' => new external_value(PARAM_INT, 'questionid', VALUE_DEFAULT, ''),
            'moduleid' => new external_value(PARAM_INT, 'moduleid', VALUE_DEFAULT, ''),
            'id' => new external_value(PARAM_INT, 'row id', VALUE_REQUIRED),
            'previd' => new external_value(PARAM_INT, 'Previous row id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Update the sort order of a row
     * @param string $type
     * @param int $questionid
     * @param int $moduleid
     * @param int $id
     * @param int $previd
     * @return bool
     */
    public static function execute($type, $questionid, $moduleid, $id, $previd): bool {
        if ($questionid == 0) {
            return 0;
        }
        $context = context_system::instance();
        require_capability('qtype/answersheet:edit', $context);

        $params = self::validate_parameters(self::execute_parameters(),
            [
                'type' => $type,
                'questionid' => $questionid,
                'moduleid' => $moduleid,
                'id' => $id,
                'previd' => $previd,
            ]);

        $sheet = new answersheet($questionid);
        $sheet->update_sort_order($params['type'], $params['moduleid'], $params['id'], $params['previd']);
        return true;
    }

    /**
     * Returns description of method result value
     *
     * @return external_value
     */
    public static function execute_returns(): external_value {
        return new external_value(PARAM_BOOL, 'status');
    }
}
