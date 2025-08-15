<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * The answersheet question renderer class is defined here.
 *
 * @package     qtype_answersheet
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_answersheet\output;

use qtype_renderer;
use qtype_with_combined_feedback_renderer;
use question_attempt;
use question_display_options;
use qtype_answersheet\local\persistent\answersheet_answers;

defined('MOODLE_INTERNAL') || die();

/**
 * Generates the output for answersheet questions.
 *
 * You should override functions as necessary from the parent class located at
 * /question/type/rendererbase.php.
 *
 * @package     qtype_answersheet
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends qtype_with_combined_feedback_renderer {
    /**
     * @var array $aanwers
     */
    public $answers = null;

    /**
     * Generates the display of the formulation part of the question. This is the
     * area that contains the question text, and the controls for students to
     * input their answers. Some question types also embed bits of feedback, for
     * example ticks and crosses, in this area.
     *
     * @param question_attempt $qa the question attempt to display.
     * @param question_display_options $options controls what should and should not be displayed.
     * @return string HTML fragment.
     */
    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
        $truefalsedisplayinfo = [];
        foreach ([true, false] as $value) {
            $truefalsedisplayinfo[$value] = (object) [
                'image' => $this->feedback_image((int) $value),
                'additionalclass' => ' ' . $this->feedback_class((int) $value),
            ];
        }

        return $this->render(new answersheet_question($qa, $options, $truefalsedisplayinfo));
    }

    /**
     * Render specific feedback
     *
     * @param question_attempt $qa
     * @return string
     */
    public function specific_feedback(question_attempt $qa) {
        return $this->combined_feedback($qa);
    }

    /**
     * Generates an automatic description of the correct response to this question.
     * Not all question types can do this. If it is not possible, this method
     * should just return an empty string.
     *
     * @param question_attempt $qa the question attempt to display.
     * @return string HTML fragment.
     */
    protected function correct_response(question_attempt $qa) {
        $textresponses = [];
        $index = 1;
        foreach ($qa->get_question()->answers as $answerkey => $answerinfo) {
            $answer = $this->get_answer($qa, $answerinfo->id);

            $name = $answer->get('name');
            $questionname = ($name != '') ? $name : $index;
            $currentresponse = $answerinfo->answer;

            $answertypetext = get_string('option', 'qtype_answersheet', $currentresponse);
            $textresponses[] = "{$questionname} -> $answertypetext";
            $index++;
        }
        return get_string(
            'correctansweris',
            'qtype_shortanswer',
            s(join(', ', $textresponses))
        );
    }

    /**
     * Find the answer for this answer
     * @param question_attempt $qa
     * @param int $answerid
     * @return object
     */
    public function get_answer(question_attempt $qa, int $answerid) {
        if (is_null($this->answers)) {
            $this->answers = answersheet_answers::get_all_records_for_question($qa->get_question()->id);
        }
        foreach ($this->answers as $answer) {
            if ($answer->get('answerid') == $answerid) {
                return $answer;
            }
        }
        return null;
    }
}
