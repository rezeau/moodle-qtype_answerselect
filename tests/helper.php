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
 * Test helper code for the Random select answers question type.
 *
 * @package    qtype_answersselect
 * @copyright 2021 Joseph Rézeau <joseph@rezeau.org>
 * @copyright based on work by 2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Test helper class for the Random select answers question type.
 *
 * @copyright 2021 Joseph Rézeau <joseph@rezeau.org>
 * @copyright based on work by 2010 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_answersselect_test_helper extends question_test_helper {
    /**
     * This function extends question_test_helper.
     * @return array of example question names that can be passed as the $which
     * argument of test_question_maker::make_question when $qtype is
     * this question type.
     */
    public function get_test_questions() {
        return ['mammals_two_of_four', 'letters_two_of_five'];
    }

    /**
     * Get an example answersselect question to use for testing. This examples has 2 correct and 2 incorrect answers.
     * @return qtype_answersselect_question
     */
    public static function make_answersselect_question_mammals_two_of_four() {

        question_bank::load_question_definition_classes('answersselect');
        $mc = new qtype_answersselect_question();

        test_question_maker::initialise_a_question($mc);

        $mc->name = 'Random select answers question';
        $mc->questiontext = 'Which of these animals are mammals?';
        $mc->generalfeedback = 'The cat and the whale are mammals.';
        $mc->qtype = question_bank::get_qtype('answersselect');
        $mc->shuffleanswers = 1;
        $mc->answernumbering = '123';
        $mc->showstandardinstruction = 0;
        $mc->answersselectmode = 0;
        $mc->randomselectcorrect = 0;
        $mc->randomselectincorrect = 0;
        $mc->correctchoicesseparator = 0;

        test_question_maker::set_standard_combined_feedback_fields($mc);

        $mc->answers = [
            13 => new question_answer(13, 'the cat', 1, 'Yes, the cat is a mammal.', FORMAT_HTML),
            14 => new question_answer(14, 'the shark', 0, 'No, the shark is a fish.', FORMAT_HTML),
            15 => new question_answer(15, 'the whale', 1, 'Yes, the whale is a mammal.', FORMAT_HTML),
            16 => new question_answer(16, 'the tortoise', 0, 'No, the tortoise is a reptile.', FORMAT_HTML),
        ];

        $mc->hints = [
            new qtype_answersselect_hint(1, 'Hint 1.', FORMAT_HTML, true, false, false),
            new qtype_answersselect_hint(2, 'Hint 2.', FORMAT_HTML, true, true, true),
        ];

        return $mc;
    }

    /**
     * Get the question data, as it would be loaded by get_question_options, for
     * the question returned by make_an_answersselect_mammals_two_of_four().
     * @return object
     */
    public static function get_answersselect_question_data_mammals_two_of_four() {
        global $USER;

        $qdata = new stdClass();
        $qdata->id = 0;
        $qdata->contextid = 0;
        $qdata->category = 0;
        $qdata->parent = 0;
        $qdata->stamp = make_unique_id_code();
        $qdata->version = make_unique_id_code();
        $qdata->timecreated = time();
        $qdata->timemodified = time();
        $qdata->createdby = $USER->id;
        $qdata->modifiedby = $USER->id;
        $qdata->qtype = 'answersselect';
        $qdata->name = 'Random select answers question';
        $qdata->questiontext = 'Which of these animals are mammals?';
        $qdata->questiontextformat = FORMAT_HTML;
        $qdata->generalfeedback = 'The cat and the whale are mammals.';
        $qdata->generalfeedbackformat = FORMAT_HTML;
        $qdata->defaultmark = 1;
        $qdata->length = 1;
        $qdata->penalty = 0.3333333;
        $qdata->hidden = 0;
        $qdata->idnumber = '';

        $qdata->options = new stdClass();
        $qdata->options->shuffleanswers = 1;
        $qdata->options->answernumbering = '123';
        $qdata->options->showstandardinstruction = 0;
        $qdata->options->answersselectmode = 0;
        $qdata->options->randomselectcorrect = 0;
        $qdata->options->randomselectincorrect = 0;
        $qdata->options->correctchoicesseparator = 0;
        $qdata->options->correctfeedback =
                test_question_maker::STANDARD_OVERALL_CORRECT_FEEDBACK;
        $qdata->options->correctfeedbackformat = FORMAT_HTML;
        $qdata->options->partiallycorrectfeedback =
                test_question_maker::STANDARD_OVERALL_PARTIALLYCORRECT_FEEDBACK;
        $qdata->options->partiallycorrectfeedbackformat = FORMAT_HTML;
        $qdata->options->shownumcorrect = 1;
        $qdata->options->incorrectfeedback =
                test_question_maker::STANDARD_OVERALL_INCORRECT_FEEDBACK;
        $qdata->options->incorrectfeedbackformat = FORMAT_HTML;

        $qdata->options->answers = [
            13 => (object) [
                'id' => 13,
                'answer' => 'the cat',
                'answerformat' => FORMAT_PLAIN,
                'fraction' => 1,
                'feedback' => 'Yes, the cat is a mammal.',
                'feedbackformat' => FORMAT_HTML,
            ],
            14 => (object) [
                'id' => 14,
                'answer' => 'the shark',
                'answerformat' => FORMAT_PLAIN,
                'fraction' => 0,
                'feedback' => 'No, the shark is a fish.',
                'feedbackformat' => FORMAT_HTML,
            ],
            15 => (object) [
                'id' => 15,
                'answer' => 'the whale',
                'answerformat' => FORMAT_PLAIN,
                'fraction' => 1,
                'feedback' => 'Yes, the whale is a mammal.',
                'feedbackformat' => FORMAT_HTML,
            ],
            16 => (object) [
                'id' => 16,
                'answer' => 'the tortoise',
                'answerformat' => FORMAT_PLAIN,
                'fraction' => 0,
                'feedback' => 'No, the tortoise is a reptile.',
                'feedbackformat' => FORMAT_HTML,
            ],
        ];

        $qdata->hints = [
            1 => (object) [
                'id' => 1,
                'hint' => 'Hint 1.',
                'hintformat' => FORMAT_HTML,
                'shownumcorrect' => 1,
                'clearwrong' => 0,
                'options' => 0,
            ],
            2 => (object) [
                'id' => 2,
                'hint' => 'Hint 2.',
                'hintformat' => FORMAT_HTML,
                'shownumcorrect' => 1,
                'clearwrong' => 1,
                'options' => 1,
            ],
        ];

        return $qdata;
    }

    /**
     * Get an example answersselect question to use for testing. This examples has 2 correct and 3 incorrect answers.
     * @return qtype_answersselect_question
     */
    public static function make_answersselect_question_letters_two_of_five() {
        question_bank::load_question_definition_classes('answersselect');
        $mc = new qtype_answersselect_question();

        test_question_maker::initialise_a_question($mc);

        $mc->name = 'Random select answers two of five';
        $mc->questiontext = 'The answer is A and B';
        $mc->generalfeedback = '';
        $mc->qtype = question_bank::get_qtype('answersselect');

        $mc->shuffleanswers = false;
        $mc->answernumbering = 'none';
        $mc->showstandardinstruction = 0;

        test_question_maker::set_standard_combined_feedback_fields($mc);

        $mc->answers = [
            13 => new question_answer(13, 'A', 1, '', FORMAT_HTML),
            14 => new question_answer(14, 'B', 1, '', FORMAT_HTML),
            15 => new question_answer(15, 'C', 0, '', FORMAT_HTML),
            16 => new question_answer(16, 'D', 0, '', FORMAT_HTML),
            17 => new question_answer(17, 'E', 0, '', FORMAT_HTML),
        ];

        $mc->hints = [
            1 => new qtype_answersselect_hint(1, 'Hint 1.', FORMAT_HTML, true, false, false),
            2 => new qtype_answersselect_hint(2, 'Hint 2.', FORMAT_HTML, true, true, true),
        ];

        return $mc;
    }

    /**
     * Get data required to save an answersselect question with 2 correct & 2 incorrect answers.
     * @return stdClass data to create an answersselect question.
     */
    public function get_answersselect_question_form_data_mammals_two_of_four() {
        $fromform = new stdClass();

        $fromform->name = 'Random select answers response question';
        $fromform->questiontext = ['text' => 'Which of these animals are mammals?', 'format' => FORMAT_HTML];
        $fromform->defaultmark = 1.0;
        $fromform->generalfeedback = ['text' => 'The cat and the whale are mammals.', 'format' => FORMAT_HTML];
        $fromform->shuffleanswers = 0;
        $fromform->answernumbering = 'abc';
        $fromform->showstandardinstruction = 0;
        $fromform->answer = [
                0 => ['text' => 'the cat', 'format' => FORMAT_PLAIN],
                1 => ['text' => 'the shark', 'format' => FORMAT_PLAIN],
                2 => ['text' => 'the whale', 'format' => FORMAT_PLAIN],
                3 => ['text' => 'the tortoise', 'format' => FORMAT_PLAIN],
        ];
        $fromform->correctanswer = [
                0 => 1,
                1 => 0,
                2 => 1,
                3 => 0,
        ];
        $fromform->feedback = [0 => ['text' => 'Yes, the cat is a mammal.', 'format' => FORMAT_HTML],
                1 => ['text' => 'No, the shark is a fish.', 'format' => FORMAT_HTML],
                2 => ['text' => 'Yes, the whale is a mammal.', 'format' => FORMAT_HTML],
                3 => ['text' => 'No, the tortoise is a reptile.', 'format' => FORMAT_HTML],
        ];
        test_question_maker::set_standard_combined_feedback_form_data($fromform);
        $fromform->shownumcorrect = 0;
        $fromform->penalty = 0.3333333;

        return $fromform;
    }

}
