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

namespace qtype_answersheet\output;
use qtype_answersheet\local\api\answersheet as answersheet_api;
use question_bank;
use renderable;
use templatable;
use renderer_base;
use stdClass;

/**
 * Renderable for programme
 *
 * @package    qtype_answersheet
 * @copyright  2024 CALL Learning <Laurent David>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class answersheet implements renderable, templatable {
    /**
     * @var int $questionid.
     */
    private $questionid;

    /**
     * Construct this renderable.
     *
     * @param int $questionid The course id.
     */
    public function __construct(int $questionid) {
        $this->questionid = $questionid;
    }

    /**
     * Export data for the template
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        global $CFG;
        require_once($CFG->libdir . '/questionlib.php');
        $question = question_bank::load_question($this->questionid);
        $modules = answersheet_api::get_data($question);
        $data = [
            'modules' => $modules,
            'debug' => $CFG->debugdisplay ? json_encode($modules, JSON_PRETTY_PRINT) : '',
            'cssurl' => new \moodle_url('/question/type/answersheet/scss/styles.css', ['cache' => time()]),
            'questionid' => $this->questionid,
        ];
        return $data;
    }
}
