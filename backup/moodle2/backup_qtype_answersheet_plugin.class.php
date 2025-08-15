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

use qtype_answersheet\utils;

defined('MOODLE_INTERNAL') || die();

/**
 * Provides the information to backup answersheet questions
 *
 * @package    qtype_answersheet
 * @subpackage backup-moodle2
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_qtype_answersheet_plugin extends backup_qtype_extrafields_plugin {
    /**
     * Returns the qtype information to attach to question element.
     */
    protected function define_question_plugin_structure() {
        $plugin = parent::define_question_plugin_structure();
        $pluginwrapper = $plugin->get_child($this->get_recommended_name());
        $qtypeobj = question_bank::get_qtype($this->pluginname);
        $qtypename = $qtypeobj->name();

        // Modules.
        $modules = new backup_nested_element('modules');
        $module = new backup_nested_element(
            'module',
            ['id'],
            [
                'questionid',
                'sortorder',
                'name',
                'type',
                'numoptions',
                'usermodified',
                'timecreated',
                'timemodified',
            ]
        );
        $pluginwrapper->add_child($modules);
        $modules->add_child($module);
        $module->set_source_table(
            "qtype_{$qtypename}_module",
            ['questionid' => backup::VAR_PARENTID]
        );

        // Answersheet Answers.
        $answers = $pluginwrapper->get_child('answers');
        $answer = $answers->get_child('answer');
        $extraanswers = $answer->get_child('extraanswerdata'); // This is the element that will contain the extra answers data.
        // It is the table/fields of qtype->extra_answer_fields
        $extraanswers->annotate_ids('module', 'moduleid');
        $extraanswers->annotate_ids('user', 'usermodified');

        // Docs.
        $docs = new backup_nested_element('docs');
        $doc = new backup_nested_element(
            'doc',
            ['id'],
            ['name', 'type', 'sortorder', 'usermodified', 'timecreated', 'timemodified']
        );
        $pluginwrapper->add_child($docs);
        $docs->add_child($doc);
        $doc->set_source_table(
            "qtype_{$qtypename}_docs",
            ['questionid' => backup::VAR_PARENTID]
        );
        return $plugin;
    }

    /**
     * Returns one array with filearea => mappingname elements for the qtype
     *
     * @return array
     */
    public static function get_qtype_fileareas() {
        $basic = array_fill_keys(utils::get_basic_fileareas(), 'question_created');
        return array_merge(
            $basic,
            ['audio' => 'qtype_answersheet_docs', 'document' => 'qtype_answersheet_docs']
        );
    }
}
