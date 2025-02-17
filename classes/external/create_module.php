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

use qtype_answersheet\local\api\answersheet;

/**
 * Class create_module
 *
 * @package    qtype_answersheet
 * @copyright  2025 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_module extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'name' => new external_value(PARAM_TEXT, 'Name', VALUE_DEFAULT, ''),
            'questionid' => new external_value(PARAM_INT, 'questionid', VALUE_DEFAULT, ''),
            'sortorder' => new external_value(PARAM_INT, 'Sort order', VALUE_REQUIRED),
            'type' => new external_value(PARAM_INT, 'Type', VALUE_DEFAULT, 1),
            'numoptions' => new external_value(PARAM_INT, 'Options', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Create a new module
     *
     * @param string $name
     * @param int $questionid
     * @param int $sortorder
     * @param int $type
     * @param int $numoptions
     * @return int
     */
    public static function execute($name, $questionid, $sortorder, $type, $numoptions): int {
        if ($questionid == 0) {
            return 0;
        }
        $context = context_system::instance();
        require_capability('qtype/answersheet:edit', $context);

        $params = self::validate_parameters(self::execute_parameters(),
            [
                'name' => $name,
                'questionid' => $questionid,
                'sortorder' => $sortorder,
                'type' => $type,
                'numoptions' => $numoptions,
            ]
        );

        $moduleid = answersheet::create_module($params['name'], $params['questionid'],  $params['sortorder'],
            $params['type'], $params['numoptions']);
        return $moduleid;
    }

    /**
     * Returns description of method result value
     *
     * @return external_value
     */
    public static function execute_returns(): external_value {
        return new external_value(PARAM_INT, 'moduleid');
    }
}
