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

// For a complete list of base question classes please examine the file
// /question/type/questionbase.php.
//
// Make sure to implement all the abstract methods of the base class.
use qtype_answersheet\local\api\answersheet;
use qtype_answersheet\local\persistent\answersheet_answers;

/**
 * Question definition class for answersheet.
 *
 * @package     qtype_answersheet
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_answersheet_question extends question_graded_automatically {
    /**
     * @var int $startnumbering
     */
    public int $startnumbering = 1;
    /**
     * @var array $audioname
     */
    public array $audioname = [];
    /**
     * @var array $documentname
     */
    public array $documentname = [];

    /**
     * @var array $extraanswerfields Contains the additional information from qtype_answersheet_answers table, so
     * it can be used in this class.
     */
    public array $extraanswerfields = [];

    /**
     * @var array $modules Contains the modules that are used in this question.
     */
    public array $modules = [];

    /**
     * @var array $answersheets Contain all answersheets keyed by their module id.
     */
    public array $answersheets = [];

    /**
     * Returns data to be included in the form submission.
     *
     * @return array|string.
     */
    public function get_expected_data() {
        $data = [];
        $answersheets = $this->get_answersheets_by_answerid();
        $datatypes = $this->get_answer_datatypes();
        foreach (array_keys($this->answers) as $key) {
            if (!isset($answersheets[$key]) || !isset($datatypes[$key])) {
                continue;
            }
            $data['answer' . $key] = $datatypes[$key]['datatype'];
        }
        return $data;
    }

    /**
     * Return the answsersheets answers indexed by their answerid.
     *
     * @return array|null Null if it is not possible to compute a correct response.
     */
    private function get_answersheets_by_answerid(): array {
        $answersheets = [];
        foreach ($this->answersheets as $moduleid => $answers) {
            foreach ($answers as $answer) {
                // Load the answersheets for each answer.
                $answersheets[$answer->get('answerid')] = $answer;
            }
        }
        return $answersheets;
    }


    /**
     * Return the answsersheets matching a given answerid.
     */
    public function get_answersheets_from_answerid(int $answerid): ?answersheet_answers {
        foreach ($this->answersheets as $moduleid => $answers) {
            foreach ($answers as $answer) {
                if ($answer->get('answerid') == $answerid) {
                    // Load the answersheets for each answer.
                    return $answer;
                }
            }
        }
        return null;
    }


    /**
     * Get the answer datatypes for this question.
     *
     * @return array
     */
    private function get_answer_datatypes(): array {
        $datatypes = [];
        foreach ($this->answersheets as $moduleid => $answersheets) {
            foreach ($answersheets as $answersheet) {
                $datatypes[$answersheet->get('answerid')] = [
                    'datatype' => $answersheet->get_module_data_type(),
                    'type' => $answersheet->get_module_type(),
                ];
            }
        }
        return $datatypes;
    }

    /**
     * Returns the data that would need to be submitted to get a correct answer.
     *
     * @return array|null Null if it is not possible to compute a correct response.
     */
    public function get_correct_response() {
        $response = [];
        foreach ($this->answers as $key => $answer) {
            $answersheet = $this->get_answersheets_from_answerid($answer->id);
            if (!$answersheet) {
                continue; // If there is no answersheet for this answer, skip it.
            }
            $response[$this->field($key)] = answersheet::to_stored_value($answer->answer, $answersheet);
        }
        return $response;
    }

    /**
     * returns string of place key value prepended with p, i.e. p0 or p1 etc
     *
     * @param int $key stem number
     * @return string the question-type variable name.
     */
    public function field($key) {
        return 'answer' . $key;
    }

    /**
     * Checks whether the user is allowed to be served a particular file.
     *
     * @param question_attempt $qa The question attempt being displayed.
     * @param question_display_options $options The options that control display of the question.
     * @param string $component The name of the component we are serving files for.
     * @param string $filearea The name of the file area.
     * @param array $args the Remaining bits of the file path.
     * @param bool $forcedownload Whether the user must be forced to download the file.
     * @return bool True if the user can access this file.
     */
    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
        $isdocument = $component == 'qtype_answersheet' && $filearea == 'document';
        $isaudio = $component == 'qtype_answersheet' && $filearea == 'audio';
        $isadocumentfromthisquestion = \qtype_answersheet\answersheet_docs::record_exists_select(
            'questionid = :questionid AND id = :id',
            ['questionid' => $this->id, 'id' => $args[0]]
        );
        return parent::check_file_access($qa, $options, $component, $filearea, $args, $forcedownload)
            || ($isdocument || $isaudio) && $isadocumentfromthisquestion;
    }

    /**
     * Is the reponse complete ?
     *
     * @param array $responses
     * @return bool
     */
    public function is_complete_response(array $responses) {
        $iscomplete = true;
        foreach (array_keys($this->answers) as $answerkey) {
            $fieldname = $this->field($answerkey);
            $iscomplete = $iscomplete && isset($responses[$fieldname]);
            $iscomplete = $iscomplete && trim($responses[$fieldname] != "");
        }
        return $iscomplete;
    }

    /**
     * Is it the same response ?
     *
     * @param array $prevresponse
     * @param array $newresponse
     * @return bool
     */
    public function is_same_response(array $prevresponse, array $newresponse) {
        $same = true;
        foreach ($this->answers as $answerkey => $answerinfo) {
            $aprevresponse = $this->get_response_value($answerkey, $prevresponse);
            $anewresponse = $this->get_response_value($answerkey, $newresponse);
            $same = $same && $aprevresponse == $anewresponse;
        }
        return $same;
    }

    /**
     * Get response value from key
     *
     * @param string $answerkey
     * @param array $responses
     * @return mixed|null
     */
    protected function get_response_value($answerkey, $responses) {
        $fieldname = $this->field($answerkey);
        return empty($responses[$fieldname]) ? null : $responses[$fieldname];
    }

    /**
     * Summarise response
     *
     * @param array $response
     * @return string
     * @throws coding_exception
     */
    public function summarise_response(array $response) {
        $textresponses = [];
        $index = 1;
        foreach ($this->answers as $answerkey => $answerinfo) {
            $currentresponse = $this->get_response_value($answerkey, $response);
            if (is_null($currentresponse)) {
                continue;
            }
            $answersheet = $this->get_answersheets_from_answerid($answerinfo->id);
            if (!$answersheet) {
                continue; // If there is no answersheet for this answer, skip it.
            }
            $currentresponse = answersheet::to_human_value($currentresponse, $answersheet);
            $answertypetext = get_string('option', 'qtype_answersheet', $currentresponse);
            $textresponses[] = "{$index} -> $answertypetext";
            $index++;
        }
        return implode(', ', $textresponses);
    }

    /**
     * Get validation error
     *
     * @param array $response
     * @return lang_string|string
     * @throws coding_exception
     */
    public function get_validation_error(array $response) {
        if ($this->is_gradable_response($response)) {
            return '';
        }
        return get_string('pleasechoseatleastananswer', 'qtype_answersheet');
    }

    /**
     * A question is gradable if at least one answer
     *
     * @param array $response
     * @return boolean
     */
    public function is_gradable_response(array $response) {
        foreach (array_keys($this->answers) as $answerkey) {
            if (!empty($response[$this->field($answerkey)])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Grade response
     *
     * @param array $responses
     * @return array
     */
    public function grade_response(array $responses) {
        $totalscore = 0.0;
        foreach ($this->answers as $answerkey => $answerinfo) {
            $currentresponse = $this->get_response_value($answerkey, $responses);
            if (is_null($currentresponse)) {
                continue;
            }
            $isrightvalue = $this->compare_response_with_answer($currentresponse, $answerinfo);
            $totalscore += $isrightvalue;
        }
        $fraction = $totalscore / count($this->answers);
        return [$fraction, question_state::graded_state_for_fraction($fraction)];
    }

    /**
     * Check if the response is correct
     *
     * @param array $currentresponse
     * @param question_answer $answerinfo
     * @return int 1 if correct, 0 if not
     */
    public function compare_response_with_answer($currentresponse, question_answer $answerinfo) {
        $answersheet = $this->get_answersheets_from_answerid($answerinfo->id);
        $normalised = qtype_answersheet\local\api\answersheet::to_human_value($currentresponse, $answersheet);
        if ($this->compare_keys($normalised, $answerinfo->answer)) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Compare two keys in a case-insensitive manner and without accents if possible.
     *
     * @param string $key1
     * @param string $key2
     * @return bool
     */
    private function compare_keys(string $key1, string $key2) {
        // Compare keys in a case-insensitive manner.
        // We use trim to remove any leading or trailing whitespace.
        // Can you compare without accents ?
        if (function_exists('iconv')) {
            $key1 = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $key1);
            $key2 = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $key2);
        }
        return strcasecmp(trim($key1), trim($key2)) === 0;
    }

    /**
     * Given a response, reset the parts that are wrong.
     *
     * @param array $response a response
     * @return array a cleaned up response with the wrong bits reset.
     */
    public function clear_wrong_from_response(array $response) {
        foreach ($this->answers as $answerkey => $answerinfo) {
            $currentresponse = $this->get_response_value($answerkey, $response);
            if (is_null($currentresponse)) {
                continue;
            }
            $isrightvalue = $this->compare_response_with_answer($currentresponse, $answerinfo);
            if (!$isrightvalue) {
                $fieldname = $this->field($answerkey);
                unset($response[$fieldname]);
            }
        }
        return $response;
    }

    /**
     * Return the number of subparts of this response that are right.
     *
     * @param array $response a response
     * @return array with two elements, the number of correct subparts, and
     * the total number of subparts.
     */
    public function get_num_parts_right(array $response) {
        $rightcount = 0;
        foreach ($this->answers as $answerkey => $answerinfo) {
            $currentresponse = $this->get_response_value($answerkey, $response);
            if (is_null($currentresponse)) {
                continue;
            }
            $rightcount += $this->compare_response_with_answer($currentresponse, $answerinfo);
        }
        return [$rightcount, count($this->answers)];
    }

    /**
     * Compute final grade
     *
     * @param string $responses
     * @param int $totaltries
     * @return float|int
     */
    public function compute_final_grade($responses, $totaltries) {
        $totalscore = 0;
        foreach ($this->answers as $answerkey => $answerinfo) {
            $lastwrongindex = -1;
            $finallyright = false;
            foreach ($responses as $i => $response) {
                $currentresponse = $this->get_response_value($answerkey, $response);
                if (!$this->compare_response_with_answer($currentresponse, $answerinfo)) {
                    $lastwrongindex = $i;
                    $finallyright = false;
                } else {
                    $finallyright = true;
                }
            }

            if ($finallyright) {
                $totalscore += max(0, 1 - ($lastwrongindex + 1) * $this->penalty);
            }
        }
        return $totalscore / count($this->answers);
    }
}
