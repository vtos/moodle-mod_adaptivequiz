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

namespace mod_adaptivequiz\local\question;

use advanced_testcase;
use context_course;
use context_module;
use mod_adaptivequiz\local\attempt\attempt;
use question_bank;
use question_engine;

/**
 * Test class.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_adaptivequiz\local\question\question_answer_evaluation
 */
class question_answer_evaluation_test extends advanced_testcase {

    public function test_it_gives_no_evaluation_when_no_answer_was_given_to_question(): void {
        self::resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        $adaptivequiz = $this->getDataGenerator()
            ->get_plugin_generator('mod_adaptivequiz')
            ->create_instance([
                'highestlevel' => 10,
                'lowestlevel' => 1,
                'standarderror' => 14,
                'course' => $course->id,
            ]);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id);
        $context = context_module::instance($cm->id);

        $quba = question_engine::make_questions_usage_by_activity('mod_adaptivequiz', $context);

        $questionanswerevaluation = new question_answer_evaluation($quba);
        self::assertNull($questionanswerevaluation->perform());
    }

    public function test_it_gives_proper_answer_evaluation(): void {
        self::resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        $adaptivequiz = $this->getDataGenerator()
            ->get_plugin_generator('mod_adaptivequiz')
            ->create_instance([
                'highestlevel' => 10,
                'lowestlevel' => 1,
                'standarderror' => 14,
                'course' => $course->id,
            ]);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id);
        $context = context_module::instance($cm->id);

        $quba = question_engine::make_questions_usage_by_activity('mod_adaptivequiz', $context);
        $quba->set_preferred_behaviour(attempt::ATTEMPTBEHAVIOUR);

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $questioncategory = $questiongenerator->create_question_category(
            ['contextid' => context_course::instance($course->id)->id]
        );

        $question1 = $questiongenerator->create_question('truefalse', null, [
            'name' => 'True/false question 1',
            'category' => $questioncategory->id,
        ]);
        $question2 = $questiongenerator->create_question('truefalse', null, [
            'name' => 'True/false question 2',
            'category' => $questioncategory->id,
        ]);
        $question3 = $questiongenerator->create_question('truefalse', null, [
            'name' => 'True/false question 3',
            'category' => $questioncategory->id,
        ]);

        // Given the question was displayed, but no answer was given.
        $slot = $quba->add_question(question_bank::load_question($question1->id));
        $quba->start_question($slot);

        // When performing answer evaluation.
        $questionanswerevaluation = new question_answer_evaluation($quba);
        $evaluationresult = $questionanswerevaluation->perform();

        // Then no evaluation is expected.
        self::assertNull($evaluationresult);

        // Given the question was answered correctly.
        $slot = $quba->add_question(question_bank::load_question($question2->id));
        $quba->start_question($slot);
        $time = time();
        $responses = [$slot => 'True'];
        $quba->process_all_actions($time,
            $questiongenerator->get_simulated_post_data_for_questions_in_usage($quba, $responses, false));
        $quba->finish_all_questions($time);

        // When performing answer evaluation.
        $questionanswerevaluation = new question_answer_evaluation($quba);
        $evaluationresult = $questionanswerevaluation->perform();

        // Then the evaluation result should match the one expected for a correct question answer.
        self::assertEquals($evaluationresult, question_answer_evaluation_result::when_answer_is_correct());

        // Given the question was answered incorrectly.
        $slot = $quba->add_question(question_bank::load_question($question3->id));
        $quba->start_question($slot);
        $time = time();
        $responses = [$slot => 'False'];
        $quba->process_all_actions($time,
            $questiongenerator->get_simulated_post_data_for_questions_in_usage($quba, $responses, false));
        $quba->finish_all_questions($time);

        // When performing answer evaluation.
        $questionanswerevaluation = new question_answer_evaluation($quba);
        $evaluationresult = $questionanswerevaluation->perform();

        // Then the evaluation result should match the one expected for a incorrect question answer.
        self::assertEquals($evaluationresult, question_answer_evaluation_result::when_answer_is_incorrect());
    }
}
