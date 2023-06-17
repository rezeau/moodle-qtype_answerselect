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
 * Random select answers question type class.
 *
 * @package    qtype_answersselect
 * @copyright 2021 Joseph Rézeau <joseph@rezeau.org>
 * @copyright based on work by 2008 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/multichoice/questiontype.php');
require_once($CFG->dirroot . '/question/format/xml/format.php');


/**
 * Random select answers question type class.
 *
 * This questions type is like the standard multiplechoice question type, but
 * with these differences:
 *
 * 1. The focus is just on the multiple response case.
 *
 * 2. The correct answer is just indicated on the editing form by a indicating
 * which choices are correct. There is no complex but flexible scoring system.
 *
 * 3.- Correct and incorrect answers are randomly selected from a "pool" at runtime.</p>
 *
 * @copyright 2021 Joseph Rézeau <joseph@rezeau.org>
 * @copyright based on work by 2008 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_answersselect extends question_type {

    /**
     * Does the question_answers.answer field need to have restore_decode_content_links_worker called on it?
     *
     * @return whether the question_answers.answer field needs to have
     * restore_decode_content_links_worker called on it.
     */
    public function has_html_answers() {
        return true;
    }

    /**
     * Loads the question type specific options for the question.
     *
     * This function loads any question type specific options for the
     * question from the database into the question object. This information
     * is placed in the $question->options field. A question type is
     * free, however, to decide on a internal structure of the options field.
     * @return bool            Indicates success or failure.
     * @param object $question The question object for the question. This object
     *                         should be updated to include the question type
     *                         specific information (it is passed by reference).
     */
    public function get_question_options($question) {
        global $DB;
        $question->options = $DB->get_record('question_answersselect',
                array('questionid' => $question->id), '*', MUST_EXIST);
        parent::get_question_options($question);
    }

    /**
     * Saves question-type specific options
     *
     * This is called by save_question() to save the question-type specific data
     * @return object $result->error or $result->notice
     * @param object $question  This holds the information from the editing form,
     *      it is not a standard question object.
     */
    public function save_question_options($question) {
        global $DB;
        $context = $question->context;
        $result = new stdClass();
        $answercount = 0;
        foreach ($question->answer as $key => $answerdata) {
            if (trim($answerdata['text']) !== '') {
                $answercount++;
            }
        }

        $oldanswers = $DB->get_records('question_answers',
                array('question' => $question->id), 'id ASC');

        // NEW FEATURE for Oleg 16/09/2021.
        $incrementcorrect = 0;
        $incrementincorrect = 0;
        $oldanswercount = count($oldanswers);

        // This is NOT an import, it's an edit.
        if ($oldanswercount !== 0) {
            // Check correct answers.
            $oldnbcorrect = 0;
            foreach ($oldanswers as $answer) {
                if ($answer->fraction == 1) {
                    $oldnbcorrect++;
                }
            }
            $incrementcorrect = 0;
            $newnbcorrect = count($question->correctanswer);
            if ($newnbcorrect !== $oldnbcorrect) {
                $incrementcorrect = $newnbcorrect - $oldnbcorrect;
            }

            // Check incorrect answers.
            $oldnbincorrect = $oldanswercount - $oldnbcorrect;
            $incrementincorrect = 0;
            $newnbincorrect = $answercount - $newnbcorrect;

            if ($newnbincorrect !== $oldnbincorrect) {
                $incrementincorrect = $newnbincorrect - $oldnbincorrect;
            }
        }

        // The following hack checks that at least two answers exist.
        if ($answercount < 2) { // Check there are at lest 2 answers for multiple choice.
            $result->notice = get_string('notenoughanswers', 'qtype_multichoice', '2');
            return $result;
        }

        // Insert all the new answers.
        $answers = array();
        foreach ($question->answer as $key => $answerdata) {
            if (trim($answerdata['text']) == '') {
                continue;
            }

            // Update an existing answer if possible.
            $answer = array_shift($oldanswers);
            if (!$answer) {
                $answer = new stdClass();
                $answer->question = $question->id;
                $answer->answer = '';
                $answer->feedback = '';
                $answer->id = $DB->insert_record('question_answers', $answer);
            }

            $answer->answer = $this->import_or_save_files($answerdata,
                    $context, 'question', 'answer', $answer->id);
            $answer->answerformat = $answerdata['format'];
            $answer->fraction = !empty($question->correctanswer[$key]);
            $answer->feedback = $this->import_or_save_files($question->feedback[$key],
                    $context, 'question', 'answerfeedback', $answer->id);
            $answer->feedbackformat = $question->feedback[$key]['format'];

            $DB->update_record('question_answers', $answer);
            $answers[] = $answer->id;
        }

        // Delete any left over old answer records.
        $fs = get_file_storage();
        foreach ($oldanswers as $oldanswer) {
            $fs->delete_area_files($context->id, 'question', 'answerfeedback', $oldanswer->id);
            $DB->delete_records('question_answers', array('id' => $oldanswer->id));
        }

        $options = $DB->get_record('question_answersselect',
                array('questionid' => $question->id));
        if (!$options) {
            $options = new stdClass();
            $options->questionid = $question->id;
            $options->correctfeedback = '';
            $options->partiallycorrectfeedback = '';
            $options->incorrectfeedback = '';
            $options->showstandardinstruction = 0;
            $options->correctchoicesseparator = 0;
            $options->id = $DB->insert_record('question_answersselect', $options);
        }

        $options->answernumbering = $question->answernumbering;
        $options->shuffleanswers = $question->shuffleanswers;
        $options->showstandardinstruction = !empty($question->showstandardinstruction);
        $options->correctchoicesseparator = $question->correctchoicesseparator;
        // Need to check that these options have been set in the edit form because they are not set by default.
        $options->answersselectmode = $question->answersselectmode;
        if (isset($question->randomselectcorrect)) {
            $options->randomselectcorrect = $question->randomselectcorrect + $incrementcorrect;
            $options->randomselectincorrect = $question->randomselectincorrect + $incrementincorrect;
        } else {
            $options->randomselectcorrect = 0;
            $options->randomselectincorrect = 0;
        }
        if (isset($question->hardsetamountofanswers)) {
            $options->hardsetamountofanswers = $question->hardsetamountofanswers;
            $options->hastobeoneincorrectanswer = $question->hastobeoneincorrectanswer;
        } else {
            $options->hardsetamountofanswers = 2;
            $options->hastobeoneincorrectanswer = 0;
        }

        $options = $this->save_combined_feedback_helper($options, $question, $context, true);
        $DB->update_record('question_answersselect', $options);

        $this->save_hints($question, true);
    }

    /**
     * Save all hints.
     *
     * @param stdObject $formdata form data of question
     * @param bool $withparts whether the question has parts
     * @return stdObject
     */
    public function save_hints($formdata, $withparts = false) {
        global $DB;
        $context = $formdata->context;

        $oldhints = $DB->get_records('question_hints',
                array('questionid' => $formdata->id), 'id ASC');

        if (!empty($formdata->hint)) {
            $numhints = max(array_keys($formdata->hint)) + 1;
        } else {
            $numhints = 0;
        }

        if ($withparts) {
            if (!empty($formdata->hintclearwrong)) {
                $numclears = max(array_keys($formdata->hintclearwrong)) + 1;
            } else {
                $numclears = 0;
            }
            if (!empty($formdata->hintshownumcorrect)) {
                $numshows = max(array_keys($formdata->hintshownumcorrect)) + 1;
            } else {
                $numshows = 0;
            }
            $numhints = max($numhints, $numclears, $numshows);
        }

        if (!empty($formdata->hintshowchoicefeedback)) {
            $numshowfeedbacks = max(array_keys($formdata->hintshowchoicefeedback)) + 1;
        } else {
            $numshowfeedbacks = 0;
        }
        $numhints = max($numhints, $numshowfeedbacks);

        for ($i = 0; $i < $numhints; $i += 1) {
            if (html_is_blank($formdata->hint[$i]['text'])) {
                $formdata->hint[$i]['text'] = '';
            }

            if ($withparts) {
                $clearwrong = !empty($formdata->hintclearwrong[$i]);
                $shownumcorrect = !empty($formdata->hintshownumcorrect[$i]);
            }

            $showchoicefeedback = !empty($formdata->hintshowchoicefeedback[$i]);

            if (empty($formdata->hint[$i]['text']) && empty($clearwrong) &&
                    empty($shownumcorrect) && empty($showchoicefeedback)) {
                continue;
            }

            // Update an existing hint if possible.
            $hint = array_shift($oldhints);
            if (!$hint) {
                $hint = new stdClass();
                $hint->questionid = $formdata->id;
                $hint->hint = '';
                $hint->id = $DB->insert_record('question_hints', $hint);
            }

            $hint->hint = $this->import_or_save_files($formdata->hint[$i],
                    $context, 'question', 'hint', $hint->id);
            $hint->hintformat = $formdata->hint[$i]['format'];
            if ($withparts) {
                $hint->clearwrong = $clearwrong;
                $hint->shownumcorrect = $shownumcorrect;
            }
            $hint->options = $showchoicefeedback;
            $DB->update_record('question_hints', $hint);
        }

        // Delete any remaining old hints.
        $fs = get_file_storage();
        foreach ($oldhints as $oldhint) {
            $fs->delete_area_files($context->id, 'question', 'hint', $oldhint->id);
            $DB->delete_records('question_hints', array('id' => $oldhint->id));
        }
    }

    /**
     * Make a hint object.
     *
     * @param stdObject $hint a hint
     * @return stdObject
     */
    protected function make_hint($hint) {
        return qtype_answersselect_hint::load_from_record($hint);
    }

    /**
     * Make an answer.
     *
     * @param stdObject $answer the answer
     * @return stdObject
     */
    public function make_answer($answer) {
        // Overridden just so we can make it public for use by question.php.
        return parent::make_answer($answer);
    }

    /**
     * Initialise the question instance.
     *
     * @param question_definition $question the question_definition we are creating
     * @param stdObject $questiondata the question data
     * @return void
     */
    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        $question->shuffleanswers = $questiondata->options->shuffleanswers;
        $question->answernumbering = $questiondata->options->answernumbering;
        $question->showstandardinstruction = $questiondata->options->showstandardinstruction;
        $question->answersselectmode = $questiondata->options->answersselectmode;
        $question->randomselectcorrect = $questiondata->options->randomselectcorrect;
        $question->randomselectincorrect = $questiondata->options->randomselectincorrect;
        $question->hardsetamountofanswers = $questiondata->options->hardsetamountofanswers;
        $question->hastobeoneincorrectanswer = $questiondata->options->hastobeoneincorrectanswer;
        $question->correctchoicesseparator = $questiondata->options->correctchoicesseparator;
        $this->initialise_combined_feedback($question, $questiondata, true);
        $this->initialise_question_answers($question, $questiondata, false);
    }

    /**
     * Delete the question.
     *
     * @param int $questionid the question ID
     * @param stdObject $contextid the context ID
     * @return stdObject
     */
    public function delete_question($questionid, $contextid) {
        global $DB;
        $DB->delete_records('question_answersselect', array('questionid' => $questionid));
        return parent::delete_question($questionid, $contextid);
    }

    /**
     * Get the number of correct response choices.
     *
     * @param stdObject $questiondata the question data
     * @return int
     */
    protected function get_num_correct_choices($questiondata) {
        $numright = 0;
        foreach ($questiondata->options->answers as $answer) {
            if (!question_state::graded_state_for_fraction($answer->fraction)->is_incorrect()) {
                $numright += 1;
            }
        }
        return $numright;
    }

    /**
     * Get the score if random response chosen.
     *
     * @param stdObject $questiondata the question data
     * @return stdObject
     */
    public function get_random_guess_score($questiondata) {
        // We compute the randome guess score here on the assumption we are using
        // the deferred feedback behaviour, and the question text tells the
        // student how many of the responses are correct.
        // Amazingly, the forumla for this works out to be
        // # correct choices / total # choices in all cases.
        return $this->get_num_correct_choices($questiondata) /
                count($questiondata->options->answers);
    }

    /**
     * Get the possible responses to the question.
     *
     * @param stdObject $questiondata the question data
     * @return array array of question parts
     */
    public function get_possible_responses($questiondata) {
        $numright = $this->get_num_correct_choices($questiondata);
        $parts = array();

        foreach ($questiondata->options->answers as $aid => $answer) {
            $parts[$aid] = array($aid =>
                    new question_possible_response($answer->answer, $answer->fraction / $numright));
        }

        return $parts;
    }

    // IMPORT EXPORT FUNCTIONS.

    /**
     * Provide import functionality for xml format
     *
     * @param mixed $data the segment of data containing the question
     * @param stdObject $question question object processed (so far) by standard import code
     * @param qformat_xml $format the format object so that helper methods can be used (in particular error())
     * @param mixed $extra any additional format specific data that may be passed by the format (see format code for info)
     * @return stdObject question object suitable for save_options() call or false if cannot handle
     */
    public function import_from_xml($data, $question, qformat_xml $format, $extra=null) {
        if (!isset($data['@']['type']) || $data['@']['type'] != 'answersselect') {
            return false;
        }

        $question = $format->import_headers($data);
        $question->qtype = 'answersselect';

        $question->shuffleanswers = $format->trans_single(
                $format->getpath($data, array('#', 'shuffleanswers', 0, '#'), 1));
        $question->answernumbering = $format->getpath($data,
                array('#', 'answernumbering', 0, '#'), 'abc');
        $question->showstandardinstruction = $format->getpath($data,
            array('#', 'showstandardinstruction', 0, '#'), 1);
        $question->answersselectmode = $format->getpath($data,
            array('#', 'answersselectmode', 0, '#'), 1);
        $question->randomselectcorrect = $format->getpath($data,
            array('#', 'randomselectcorrect', 0, '#'), 1);
        $question->randomselectincorrect = $format->getpath($data,
            array('#', 'randomselectincorrect', 0, '#'), 1);
        $question->correctchoicesseparator = $format->getpath($data,
            array('#', 'correctchoicesseparator', 0, '#'), 1);

        $format->import_combined_feedback($question, $data, true);

        // Run through the answers.
        $answers = $data['#']['answer'];
        foreach ($answers as $answer) {
            $ans = $format->import_answer($answer, true,
                    $format->get_format($question->questiontextformat));
            $question->answer[] = $ans->answer;
            $question->correctanswer[] = !empty($ans->fraction);
            $question->feedback[] = $ans->feedback;

            // Backwards compatibility.
            if (array_key_exists('correctanswer', $answer['#'])) {
                $keys = array_keys($question->correctanswer);
                $question->correctanswer[end($keys)] = $format->getpath($answer,
                        array('#', 'correctanswer', 0, '#'), 0);
            }
        }

        $format->import_hints($question, $data, true, true,
                $format->get_format($question->questiontextformat));

        // Get extra choicefeedback setting from each hint.
        if (!empty($question->hintoptions)) {
            foreach ($question->hintoptions as $key => $options) {
                $question->hintshowchoicefeedback[$key] = !empty($options);
            }
        }

        return $question;
    }

    /**
     * Provide export functionality for xml format.
     *
     * @param stdObject $question the question object
     * @param qformat_xml $format the format object so that helper methods can be used
     * @param mixed $extra any additional format specific data that may be passed by the format (see format code for info)
     * @return string the data to append to the output buffer or false if error
     */
    public function export_to_xml($question, qformat_xml $format, $extra = null) {
        $output = '';

        $output .= "    <shuffleanswers>" . $format->get_single(
                $question->options->shuffleanswers) . "</shuffleanswers>\n";
        $output .= "    <answernumbering>{$question->options->answernumbering}</answernumbering>\n";
        $output .= "    <showstandardinstruction>{$question->options->showstandardinstruction}</showstandardinstruction>\n";
        $output .= "    <answersselectmode>{$question->options->answersselectmode}</answersselectmode>\n";
        $output .= "    <randomselectcorrect>{$question->options->randomselectcorrect}</randomselectcorrect>\n";
        $output .= "    <randomselectincorrect>{$question->options->randomselectincorrect}</randomselectincorrect>\n";
        $output .= "    <correctchoicesseparator>{$question->options->correctchoicesseparator}</correctchoicesseparator>\n";
        $output .= $format->write_combined_feedback($question->options,
                                                    $question->id,
                                                    $question->contextid);
        $output .= $format->write_answers($question->options->answers);
        return $output;
    }

    /**
     * Move files from old to new context.
     *
     * @param int $questionid the question ID
     * @param stdObject $oldcontextid the source context ID
     * @param stdObject $newcontextid the destination context ID
     * @return void
     */
    public function move_files($questionid, $oldcontextid, $newcontextid) {
        $fs = get_file_storage();

        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_answers($questionid, $oldcontextid, $newcontextid, true);
        $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);

        $fs->move_area_files_to_new_context($oldcontextid,
                $newcontextid, 'question', 'correctfeedback', $questionid);
        $fs->move_area_files_to_new_context($oldcontextid,
                $newcontextid, 'question', 'partiallycorrectfeedback', $questionid);
        $fs->move_area_files_to_new_context($oldcontextid,
                $newcontextid, 'question', 'incorrectfeedback', $questionid);
    }

    /**
     * Delete any files in the context.
     *
     * @param int $questionid the question ID
     * @param stdObject $contextid the context ID
     * @return void
     */
    protected function delete_files($questionid, $contextid) {
        $fs = get_file_storage();

        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_answers($questionid, $contextid, true);
        $this->delete_files_in_hints($questionid, $contextid);
        $fs->delete_area_files($contextid, 'question', 'correctfeedback', $questionid);
        $fs->delete_area_files($contextid, 'question', 'partiallycorrectfeedback', $questionid);
        $fs->delete_area_files($contextid, 'question', 'incorrectfeedback', $questionid);
    }
}


/**
 * An extension of question_hint_with_parts for answersselect questions.
 *
 * An extension of question_hint_with_parts for answersselect questions
 * with an extra option for whether to show the feedback for each choice.
 *
 * @copyright  2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_answersselect_hint extends question_hint_with_parts {
    /** @var boolean whether to show the feedback for each choice. */
    public $showchoicefeedback;

    /**
     * Constructor.
     *
     * @param int $id Question ID
     * @param string $hint The hint text
     * @param int $hintformat
     * @param bool $shownumcorrect whether the number of right parts should be shown
     * @param bool $clearwrong whether the wrong parts should be reset.
     * @param bool $showchoicefeedback whether to show the feedback for each choice.
     */
    public function __construct($id, $hint, $hintformat, $shownumcorrect,
            $clearwrong, $showchoicefeedback) {
        parent::__construct($id, $hint, $hintformat, $shownumcorrect, $clearwrong);
        $this->showchoicefeedback = $showchoicefeedback;
    }

    /**
     * Create a basic hint from a row loaded from the question_hints table in the database.
     * @param object $row with $row->hint, ->shownumcorrect and ->clearwrong set.
     * @return question_hint_with_parts
     */
    public static function load_from_record($row) {
        return new qtype_answersselect_hint($row->id, $row->hint, $row->hintformat,
                $row->shownumcorrect, $row->clearwrong, !empty($row->options));
    }

    /**
     * Adjust the display options
     *
     * @param question_display_options $options display options
     * @return void
     */
    public function adjust_display_options(question_display_options $options) {
        parent::adjust_display_options($options);
        $options->suppresschoicefeedback = !$this->showchoicefeedback;
    }
}
