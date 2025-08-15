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

defined('MOODLE_INTERNAL') || die();

/**
 * Restore plugin class that provides the necessary information needed to restore one answersheet question type plugin
 *
 * @package    qtype_answersheet
 * @subpackage backup-moodle2
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_qtype_answersheet_plugin extends restore_qtype_extrafields_plugin {
    /**
     * Returns the paths to be handled by the plugin at question level.
     */
    protected function define_question_plugin_structure() {
        $paths = parent::define_question_plugin_structure();
        // Add own qtype stuff.
        $elename = 'module';
        $elepath = $this->get_pathfor('/modules/module');
        $paths[] = new restore_path_element($elename, $elepath);
        $elename = 'asanswer';
        $elepath = $this->get_pathfor('/asanswers/asanswer');
        $paths[] = new restore_path_element($elename, $elepath);
        $elename = 'doc';
        $elepath = $this->get_pathfor('/docs/doc');
        $paths[] = new restore_path_element($elename, $elepath);
        return $paths;
    }
    /**
     * Process the qtype/answersheet element
     *
     * @param mixed $data
     */
    public function process_answersheet($data) {
        $this->really_process_extra_question_fields($data);
    }

    /**
     * Process the module element.
     *
     * @param array|object $data Data related to the module being restored.
     */
    public function process_module($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->questionid = $this->get_new_parentid('question');
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        $newitemid = $DB->insert_record('qtype_answersheet_module', $data);
        $this->set_mapping('qtype_answersheet_module', $oldid, $newitemid);
    }

    /**
     * Processes the extra answer data
     *
     * @param array $data extra answer data
     */
    public function process_extraanswerdata($data) {
        global $DB;
        $data = (object)$data;
        $extra = $this->qtypeobj->extra_answer_fields();
        $tablename = array_shift($extra);

        $oldquestionid = $this->get_old_parentid('question');
        $questioncreated = $this->get_mappingid('question_created', $oldquestionid) ? true : false;
        $data->moduleid = $this->get_mappingid('module', $data->moduleid);
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        if ($questioncreated) {
            $data->answerid = $this->get_mappingid('question_answer', $data->id);
            $DB->insert_record($tablename, $data);
        } else {
            $DB->update_record($tablename, $data);
        }
    }
    /**
     * Process the answer element.
     *
     * @param array|object $data Drag and drop drops data to work with.
     */
    public function process_asanswer($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->questionid = $this->get_new_parentid('question');
        $data->answerid = $this->get_mappingid('question_answers', $data->answerid);
        $data->moduleid = $this->get_mappingid('module', $data->moduleid);
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        $newitemid = $DB->insert_record('qtype_answersheet_answers', $data);
        $this->set_mapping('qtype_answersheet_answers', $oldid, $newitemid);
    }

    /**
     * Process the docs element.
     *
     * @param array|object $data Drag and drop drops data to work with.
     */
    public function process_doc($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->questionid = $this->get_new_parentid('question');
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        $newitemid = $DB->insert_record('qtype_answersheet_docs', $data);
        $this->set_mapping('qtype_answersheet_docs', $oldid, $newitemid);
    }
}
