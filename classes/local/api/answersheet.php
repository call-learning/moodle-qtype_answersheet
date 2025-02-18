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

namespace qtype_answersheet\local\api;

use qtype_answersheet\local\persistent\answersheet_answers;
use qtype_answersheet\local\persistent\answersheet_module;
use xmldb_structure;
/**
 * Class programme
 *
 * @package    qtype_answersheet
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class answersheet {

    /**
     * Get the table structure for the custom field
     * @return array $table
     */
    public static function get_table_structure(): array {
        $columns = [
            [
                'column' => 'name',
                'type' => PARAM_TEXT,
                'text' => true,
                'visible' => true,
                'canedit' => true,
                'label' => 'No',
                'columnid' => 1,
                'length' => 50,
                'field' => 'text',
                'sample_value' => 'A',
            ],
            [
                'column' => 'options',
                'type' => 'select',
                'select' => true,
                'visible' => true,
                'canedit' => true,
                'label' => 'Correct',
                'columnid' => 3,
                'length' => 1000,
                'field' => 'select',
                'sample_value' => '...',
                'options' => [
                    [
                        'name' => '-',
                        'selected' => true,
                    ],
                    [
                        'name' => 'A',
                        'selected' => false,
                    ],
                    [
                        'name' => 'B',
                        'selected' => false,
                    ],
                    [
                        'name' => 'C',
                        'selected' => false,
                    ],
                    [
                        'name' => 'D',
                        'selected' => false,
                    ],
                    [
                        'name' => 'E',
                        'selected' => false,
                    ],
                    [
                        'name' => 'F',
                        'selected' => false,
                    ],
                    [
                        'name' => 'G',
                        'selected' => false,
                    ],
                    [
                        'name' => 'H',
                        'selected' => false,
                    ],
                    [
                        'name' => 'I',
                        'selected' => false,
                    ],
                    [
                        'name' => 'J',
                        'selected' => false,
                    ],
                ],
            ],
            [
                'column' => 'answer',
                'type' => PARAM_TEXT,
                'text' => true,
                'visible' => true,
                'canedit' => true,
                'label' => 'Text',
                'columnid' => 4,
                'length' => 1000,
                'field' => 'select',
                'sample_value' => '...',
            ],
            [
                'column' => 'feedback',
                'type' => PARAM_TEXT,
                'text' => true,
                'visible' => true,
                'canedit' => true,
                'label' => 'Feedback',
                'columnid' => 5,
                'length' => 1000,
                'field' => 'select',
                'sample_value' => '...',
            ],
        ];
        return $columns;
    }

    /**
     * Get the column structure for the custom field
     * @return array $columns
     */
    public static function get_column_structure(): array {
        $table = self::get_table_structure();
        return array_values($table);
    }

    /**
     * Get the data for a given course
     * @param int $questionid
     * @return array $data
     */
    public static function get_data(int $questionid): array {
        if ($questionid == 0) {
            return self::dummy_data();
        }
        $modules = answersheet_module::get_all_records_for_question($questionid);
        $columns = self::get_column_structure();
        $data = [];
        foreach ($modules as $module) {
            $records = answersheet_answers::get_all_records_for_module($module->get('id'));
            $modulerows = [];
            foreach ($records as $record) {
                $row = [];
                foreach ($columns as $column) {
                    $row[] = [
                        'column' => $column['column'],
                        'value' => $record->get($column['column']),
                        'type' => $column['type'],
                        'visible' => $column['visible'],
                    ];
                }
                $modulerows[] = [
                    'id' => $record->get('id'),
                    'sortorder' => $record->get('sortorder'),
                    'cells' => $row,
                    'answerid' => $record->get('answerid'),
                ];
            }
            $data[] = [
                'moduleid' => $module->get('id'),
                'modulename' => $module->get('name'),
                'modulesortorder' => $module->get('sortorder'),
                'numoptions' => $module->get('numoptions'),
                'type' => $module->get('type'),
                'class' => $module->get_class(),
                'indicator' => $module->get_indicator(),
                'rows' => $modulerows,
                'columns' => $columns,
            ];
        }
        return $data;
    }

    /**
     * Get a dummy data record with 1 module and 1 row.
     * @return array $data
     */
    public static function dummy_data(): array {
        $columns = self::get_column_structure();
        $data = [
            [
                'moduleid' => 0,
                'modulename' => '',
                'modulesortorder' => 0,
                'numoptions' => 0,
                'type' => 1,
                'class' => answersheet_module::TYPES[1],
                'indicator' => 1,
                'rows' => [
                    [
                        'id' => 1,
                        'sortorder' => 0,
                        'cells' => array_map(function($column) {
                            return [
                                'column' => $column['column'],
                                'value' => '',
                                'type' => $column['type'],
                                'visible' => $column['visible'],
                            ];
                        }, $columns),
                        'answerid' => 0,
                    ],
                ],
                'columns' => $columns,
            ],
        ];
        return $data;
    }

    /**
     * Set the data.
     * @param int $questionid
     * @param array $data
     */
    public static function set_records(int $questionid, array $data): void {
        foreach ($data as $module) {
            $moduleid = $module['id'];
            $rows = $module['rows'];
            $mod = answersheet_module::get_record(['id' => $moduleid]);
            if (!$mod) {
                $mod = new answersheet_module();
            }
            $mod->set('name', $module['name']);
            $mod->set('type', $module['type']);
            $mod->set('sortorder', $module['sortorder']);
            $mod->set('questionid', $questionid);
            $mod->set('numoptions', $module['numoptions']);
            $mod->save();
            $moduleid = $mod->get('id');
            $records = answersheet_answers::get_all_records_for_module($moduleid);
            foreach ($rows as $row) {
                $updated = false;
                foreach ($records as $record) {
                    if ($record->get('id') == $row['id']) {
                        self::update_record($record, $row);
                        $updated = true;
                    }
                }
                if (!$updated) {
                    $record = new answersheet_answers();
                    $record->set('questionid', $questionid);
                    $record->set('moduleid', $moduleid);
                    $record->set('answerid', 0);
                    $record->set('sortorder', $row['sortorder']);
                    self::update_record($record, $row);
                }
            }
        }

    }

    /**
     * Update a record
     * @param answersheet_answers $record
     * @param array $row
     */
    private static function update_record(answersheet_answers $record, array $row): void {
        $columns = self::get_column_structure();
        $fields = array_map(function($column) {
            return $column['column'];
        }, $columns);
        foreach ($fields as $field) {
            if (!isset($row['cells'])) {
                continue;
            }
            foreach ($row['cells'] as $cell) {
                if ($cell['column'] == $field) {
                    if ($cell['type'] == PARAM_INT) {
                        $value = $cell['value'] ? (int)$cell['value'] : null;
                        $record->set($field, $value);
                        continue;
                    } else if ($cell['type'] == PARAM_FLOAT) {
                        $value = $cell['value'] ? (float)$cell['value'] : null;
                        $record->set($field, $value);
                        continue;
                    } else {
                        $record->set($field, $cell['value']);
                    }
                }
            }
        }
        $record->save();
    }

    /**
     * Get or create a module
     * @param string $name
     * @param int $questionid
     * @param int $sortorder
     * @return int $moduleid
     */
    public static function get_or_create_module($name, $questionid, $sortorder): int {
        $module = answersheet_module::get_record(['questionid' => $questionid, 'name' => $name]);
        if ($module) {
            return $module->get('id');
        }
        return self::create_module($name, $questionid, $sortorder, 1, 0);
    }

    /**
     * Create a new module
     * @param string $name
     * @param int $questionid
     * @param int $sortorder
     * @param int $type
     * @param int $numoptions
     * @return int $moduleid
     */
    public static function create_module($name, $questionid, $sortorder, $type, $numoptions): int {
        $module = new answersheet_module();
        $module->set('questionid', $questionid);
        $module->set('name', $name);
        $module->set('sortorder', $sortorder);
        $module->set('type', $type);
        $module->set('numoptions', $numoptions);
        $module->save();
        return $module->get('id');
    }

    /**
     * Delete a programme for a given course
     * @param int $questionid
     */
    public static function delete_programme($questionid): void {
        $modules = answersheet_module::get_all_records_for_question($questionid);
        foreach ($modules as $module) {
            self::delete_module($questionid, $module->get('id'));
        }
    }

    /**
     * Delete a module
     * @param int $questionid
     * @param int $moduleid
     * return bool
     */
    public static function delete_module($questionid, $moduleid): bool {
        $module = answersheet_module::get_record(['id' => $moduleid]);
        if ($module->get('questionid') == $questionid) {
            // Delete all rows in this module.
            $records = answersheet_answers::get_all_records_for_module($moduleid);
            foreach ($records as $record) {
                $record->delete();
            }
            $module->delete();
            return true;
        }
        return false;
    }

    /**
     * Create a new row
     * @param int $questionid
     * @param int $moduleid
     * @param int $prevrowid
     * @return int $sortorder
     */
    public static function create_row($questionid, $moduleid, $prevrowid): int {
        if (!$moduleid) {
            $moduleid = self::get_or_create_module('Module', $questionid, 0);
        }
        $record = new answersheet_answers();
        $record->set('answerid', 0);
        $record->set('moduleid', $moduleid);
        $record->set('questionid', $questionid);
        $record->set('sortorder', 0);
        // Set all other fields to null or ''.
        $fields = self::get_table_structure();
        foreach ($fields as $field) {
            if ($field['field'] == 'int') {
                $record->set($field['column'], null);
            } else if ($field['field'] == 'float') {
                $record->set($field['column'], null);
            } else {
                $record->set($field['column'], '');
            }
        }
        $record->save();
        self::update_sort_order('row', $moduleid, $record->get('id'), $prevrowid);
        return $record->get('id');
    }

    /**
     * Delete a row
     * @param int $questionid
     * @param int $rowid
     * @return bool
     */
    public static function delete_row($questionid, $rowid): bool {
        $record = answersheet_answers::get_record(['id' => $rowid]);
        if ($record->get('questionid') == $questionid) {
            $record->delete();
            return true;
        }
        return false;
    }

    /**
     * Update the sort order
     * @param string $type
     * @param int $moduleid
     * @param int $id
     * @param int $previd
     */
    public static function update_sort_order($type, $moduleid, $id, $previd): void {
        if ($type == 'row') {
            $newrecord = answersheet_answers::get_record(['id' => $id]);
            // In case the row is moved to the top.
            if (!$previd) {
                $newrecord->set('sortorder', 0);
                $newrecord->set('moduleid', $moduleid);
                $newrecord->save();
                $records = answersheet_answers::get_all_records_for_module($moduleid);
                $sortorder = 0;
                foreach ($records as $record) {
                    if ($record->get('id') == $id) {
                        continue;
                    }
                    $sortorder++;
                    $record->set('sortorder', $sortorder);
                    $record->save();
                }
                return;
            }
            $prevrecord = answersheet_answers::get_record(['id' => $previd]);
            $records = answersheet_answers::get_all_records_for_module($prevrecord->get('moduleid'));
            $sortorder = 0;
            foreach ($records as $record) {
                if ($record->get('id') == $id) {
                    continue;
                }
                if ($record->get('id') == $previd) {
                    $sortorder = $record->get('sortorder');
                    // Update the remaining records, depending on the action.
                    $sortorder++;
                    $newrecord->set('sortorder', $sortorder);
                    $newrecord->set('moduleid', $moduleid);
                    $newrecord->save();
                    continue;
                }
                if ($sortorder) {
                    $sortorder++;
                    $record->set('sortorder', $sortorder);
                    $record->save();
                }
            }
        }
    }
}
