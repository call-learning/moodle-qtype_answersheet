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

        // Modules
        $modules = new backup_nested_element('modules');
        $module = new backup_nested_element('module', ['id'],
            ['name', 'type', 'sortorder', 'numoptions', 'usermodified', 'timecreated', 'timemodified']);
        $pluginwrapper->add_child($modules);
        $modules->add_child($module);
        $module->set_source_table("qtype_{$qtypename}_module",
            array('questionid' => backup::VAR_PARENTID));

        // AnswersheetAnswers
        $answers = new backup_nested_element('aanswers');
        $answer = new backup_nested_element('aanswer', ['id'],
            ['name', 'options', 'answer', 'feedback', 'sortorder', 'usermodified', 'timecreated', 'timemodified']);
        $pluginwrapper->add_child($answers);
        $answers->add_child($answer);
        $answer->set_source_table("qtype_{$qtypename}_answers",
            array('questionid' => backup::VAR_PARENTID));

        // Docs.
        $docs = new backup_nested_element('docs');
        $doc = new backup_nested_element('doc', array('id'),
            array('name', 'type', 'sortorder', 'usermodified', 'timecreated', 'timemodified'));
        $pluginwrapper->add_child($docs);
        $docs->add_child($doc);
        $doc->set_source_table("qtype_{$qtypename}_docs",
            array('questionid' => backup::VAR_PARENTID));
        return $plugin;
    }
    /**
     * Returns one array with filearea => mappingname elements for the qtype
     *
     * @return array
     */
    public static function get_qtype_fileareas() {
        $basic = array_fill_keys(utils::get_basic_fileareas(), 'question_created');
        return array_merge($basic,
            ['audio' => 'qtype_answersheet_docs', 'document' => 'qtype_answersheet_docs']
        );
    }
}
