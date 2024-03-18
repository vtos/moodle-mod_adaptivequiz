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

namespace mod_adaptivequiz\local\itemadministration;

use advanced_testcase;
use context_course;
use context_module;
use mod_adaptivequiz\local\attempt\attempt;
use mod_adaptivequiz\local\catalgorithm\determine_next_difficulty_result;
use mod_adaptivequiz\local\fetchquestion;
use mod_adaptivequiz\local\question\difficulty_questions_mapping;
use question_bank;
use question_engine;

/**
 * Unit tests for the item administration class.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_adaptivequiz\local\itemadministration\item_administration
 */
class item_administration_test extends advanced_testcase {

    public function test_it_stops_administering_items_when_next_difficulty_could_not_be_determined(): void {
        self::resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        $questioncategory = $this->getDataGenerator()
            ->get_plugin_generator('core_question')
            ->create_question_category(['name' => 'My category']);

        $adaptivequiz = $this->getDataGenerator()
            ->get_plugin_generator('mod_adaptivequiz')
            ->create_instance([
                'highestlevel' => 10,
                'lowestlevel' => 1,
                'standarderror' => 5,
                'course' => $course->id,
                'questionpool' => [$questioncategory->id],
            ]);

        $user = $this->getDataGenerator()->create_user();
        $adaptiveattempt = attempt::create($adaptivequiz, $user->id);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id);
        $context = context_module::instance($cm->id);

        $quba = question_engine::make_questions_usage_by_activity('mod_adaptivequiz', $context);
        $fetchquestion = new fetchquestion($adaptivequiz, 1, $adaptivequiz->lowestlevel, $adaptivequiz->highestlevel);

        $administration = new item_administration($quba, $fetchquestion);

        // Random, doesn't matter.
        $attempteddifficultylevel = 1;

        // Given there is an attempt stoppage message when determining next difficulty level.
        $determinenextdifficultylevelresult = determine_next_difficulty_result::with_error('_some_message_');

        // When performing item administration evaluation.
        $result = $administration->evaluate_ability_to_administer_next_item(
            $adaptiveattempt,
            $adaptivequiz,
            $adaptiveattempt->read_attempt_data()->questionsattempted,
            $attempteddifficultylevel,
            $determinenextdifficultylevelresult
        );

        // Then the result of evaluation is to stop item administration.
        self::assertTrue($result->item_administration_is_to_stop());
    }

    public function test_it_stops_administering_items_when_next_difficulty_level_is_out_of_bounds(): void {
        global $SESSION;

        self::resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        $questioncategory = $this->getDataGenerator()
            ->get_plugin_generator('core_question')
            ->create_question_category(['name' => 'My category']);

        $adaptivequiz = $this->getDataGenerator()
            ->get_plugin_generator('mod_adaptivequiz')
            ->create_instance([
                'highestlevel' => 10,
                'lowestlevel' => 1,
                'standarderror' => 5,
                'course' => $course->id,
                'questionpool' => [$questioncategory->id],
            ]);

        $user = $this->getDataGenerator()->create_user();
        $adaptiveattempt = attempt::create($adaptivequiz, $user->id);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id);
        $context = context_module::instance($cm->id);

        $quba = question_engine::make_questions_usage_by_activity('mod_adaptivequiz', $context);
        $fetchquestion = new fetchquestion($adaptivequiz, 1, $adaptivequiz->lowestlevel, $adaptivequiz->highestlevel);

        $administration = new item_administration($quba, $fetchquestion);

        // Random, doesn't matter.
        $attempteddifficultylevel = 1;

        // Initialize difficulty-questions mapping by setting a value directly in global session.
        // This is a bad practice and leads to fragile tests. Normally, operating on global session should be removed from
        // the fetching questions class.
        $SESSION->adpqtagquestsum = difficulty_questions_mapping::create_empty()
            ->add_to_questions_number_for_difficulty($attempteddifficultylevel, 1)
            ->as_array();

        // Given the determined difficulty level is out of range, which is set for the quiz.
        $determinenextdifficultylevelresult = determine_next_difficulty_result::with_next_difficulty_level_determined(11);

        // When performing item administration evaluation.
        $result = $administration->evaluate_ability_to_administer_next_item(
            $adaptiveattempt,
            $adaptivequiz,
            $adaptiveattempt->read_attempt_data()->questionsattempted,
            $attempteddifficultylevel,
            $determinenextdifficultylevelresult
        );

        // Then the result of evaluation is to stop item administration.
        self::assertTrue($result->item_administration_is_to_stop());
    }

    public function test_it_stops_administering_items_when_number_of_questions_attempted_exceeds_the_maximum(): void {
        self::resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        $questioncategory = $this->getDataGenerator()
            ->get_plugin_generator('core_question')
            ->create_question_category(['name' => 'My category']);

        $adaptivequiz = $this->getDataGenerator()
            ->get_plugin_generator('mod_adaptivequiz')
            ->create_instance([
                'highestlevel' => 10,
                'lowestlevel' => 1,
                'maximumquestions' => 10,
                'standarderror' => 5,
                'course' => $course->id,
                'questionpool' => [$questioncategory->id],
            ]);

        $user = $this->getDataGenerator()->create_user();
        $adaptiveattempt = attempt::create($adaptivequiz, $user->id);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id);
        $context = context_module::instance($cm->id);

        $quba = question_engine::make_questions_usage_by_activity('mod_adaptivequiz', $context);
        $fetchquestion = new fetchquestion($adaptivequiz, 1, $adaptivequiz->lowestlevel, $adaptivequiz->highestlevel);

        $administration = new item_administration($quba, $fetchquestion);

        // Random, doesn't matter.
        $attempteddifficultylevel = 1;

        // Given the number of questions attempted equals to the maximum number, which is set for the quiz.
        $numberofquestionsattempted = 10;
        // And a question has not been submitted.
        $determinenextdifficultylevelresult = null;

        // When performing item administration evaluation.
        $result = $administration->evaluate_ability_to_administer_next_item(
            $adaptiveattempt,
            $adaptivequiz,
            $numberofquestionsattempted,
            $attempteddifficultylevel,
            $determinenextdifficultylevelresult
        );

        // Then the result of evaluation is to stop item administration.
        self::assertTrue($result->item_administration_is_to_stop());

        // Given the number of questions attempted exceeds the maximum number, which is set for the quiz.
        $numberofquestionsattempted = 11;

        // When performing item administration evaluation.
        $result = $administration->evaluate_ability_to_administer_next_item(
            $adaptiveattempt,
            $adaptivequiz,
            $numberofquestionsattempted,
            $attempteddifficultylevel,
            $determinenextdifficultylevelresult
        );

        // Then the result of evaluation is to stop item administration.
        self::assertTrue($result->item_administration_is_to_stop());
    }

    public function test_it_stops_administration_when_no_questions_were_attempted_but_the_number_provided_is_not_zero(): void {
        self::resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        $questioncategory = $this->getDataGenerator()
            ->get_plugin_generator('core_question')
            ->create_question_category(['name' => 'My category']);

        $adaptivequiz = $this->getDataGenerator()
            ->get_plugin_generator('mod_adaptivequiz')
            ->create_instance([
                'highestlevel' => 10,
                'lowestlevel' => 1,
                'maximumquestions' => 10,
                'standarderror' => 5,
                'course' => $course->id,
                'questionpool' => [$questioncategory->id],
            ]);

        $user = $this->getDataGenerator()->create_user();
        $adaptiveattempt = attempt::create($adaptivequiz, $user->id);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id);
        $context = context_module::instance($cm->id);

        $quba = question_engine::make_questions_usage_by_activity('mod_adaptivequiz', $context);
        $fetchquestion = new fetchquestion($adaptivequiz, 1, $adaptivequiz->lowestlevel, $adaptivequiz->highestlevel);

        $administration = new item_administration($quba, $fetchquestion);

        // Random, doesn't matter.
        $attempteddifficultylevel = 1;

        // Given the number of questions attempted is not zero.
        $numberofquestionsattempted = 2;
        // And a question has not been submitted.
        $determinenextdifficultylevelresult = null;

        // When performing item administration evaluation.
        $result = $administration->evaluate_ability_to_administer_next_item(
            $adaptiveattempt,
            $adaptivequiz,
            $numberofquestionsattempted,
            $attempteddifficultylevel,
            $determinenextdifficultylevelresult
        );

        // Then the result of evaluation is to stop item administration.
        self::assertTrue($result->item_administration_is_to_stop());
    }

    public function test_it_stops_administration_when_no_question_with_the_required_difficulty_can_be_fetched(): void {
        global $SESSION;

        self::resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $course = $this->getDataGenerator()->create_course();

        $questioncategory = $questiongenerator->create_question_category(
            ['contextid' => context_course::instance($course->id)->id]
        );

        $attemptedquestion = $questiongenerator->create_question('truefalse', null, [
            'name' => 'True/false question 1',
            'category' => $questioncategory->id,
        ]);
        $attemptedquestiondifficulty = 4;
        $questiongenerator->create_question_tag([
            'questionid' => $attemptedquestion->id,
            'tag' => 'adpq_'. $attemptedquestiondifficulty,
        ]);

        $adaptivequiz = $this->getDataGenerator()
            ->get_plugin_generator('mod_adaptivequiz')
            ->create_instance([
                'highestlevel' => 10,
                'lowestlevel' => 1,
                'startinglevel' => 5,
                'maximumquestions' => 10,
                'standarderror' => 5,
                'course' => $course->id,
                'questionpool' => [$questioncategory->id],
            ]);

        $user = $this->getDataGenerator()->create_user();
        $adaptiveattempt = attempt::create($adaptivequiz, $user->id);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id);
        $context = context_module::instance($cm->id);

        $quba = question_engine::make_questions_usage_by_activity('mod_adaptivequiz', $context);
        $quba->set_preferred_behaviour(attempt::ATTEMPTBEHAVIOUR);

        // Given a question was attempted.
        $slot = $quba->add_question(question_bank::load_question($attemptedquestion->id));
        $quba->start_question($slot);

        $time = time();
        $responses = [$slot => 'True'];
        $quba->process_all_actions($time,
            $questiongenerator->get_simulated_post_data_for_questions_in_usage($quba, $responses, false));
        $quba->finish_all_questions($time);

        question_engine::save_questions_usage_by_activity($quba);

        $numberofquestionsattempted = 1;
        $attempteddifficultylevel = 4;

        // Initialize difficulty-questions mapping by setting a value directly in global session.
        // This is a bad practice and leads to fragile tests. Normally, operating on global session should be removed from
        // the fetching questions class.
        $SESSION->adpqtagquestsum = difficulty_questions_mapping::create_empty()
            ->add_to_questions_number_for_difficulty($attempteddifficultylevel, 1)
            ->as_array();

        $determinenextdifficultylevelresult =
            determine_next_difficulty_result::with_next_difficulty_level_determined($attempteddifficultylevel + 1);

        // When performing item administration evaluation.
        $fetchquestion = new fetchquestion($adaptivequiz, 1, $adaptivequiz->lowestlevel, $adaptivequiz->highestlevel);
        $administration = new item_administration($quba, $fetchquestion);

        $result = $administration->evaluate_ability_to_administer_next_item(
            $adaptiveattempt,
            $adaptivequiz,
            $numberofquestionsattempted,
            $attempteddifficultylevel,
            $determinenextdifficultylevelresult
        );

        // Then the result of evaluation is to stop the attempt due to no questions for the next difficulty level.
        $expectation = item_administration_evaluation::with_stoppage_reason(
            get_string('errorfetchingquest', 'adaptivequiz', $attempteddifficultylevel + 1)
        );
        self::assertEquals($expectation, $result);
    }

    public function test_it_approves_administering_next_item_for_fresh_attempts(): void {
        self::resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $course = $this->getDataGenerator()->create_course();

        $questioncategory = $questiongenerator->create_question_category(
            ['contextid' => context_course::instance($course->id)->id]
        );
        $itemtoadminister = $questiongenerator->create_question('truefalse', null, [
            'name' => 'True/false question',
            'category' => $questioncategory->id,
        ]);

        $itemtoadministerdifficulty = 5;
        $questiongenerator->create_question_tag([
            'questionid' => $itemtoadminister->id,
            'tag' => 'adpq_'. $itemtoadministerdifficulty,
        ]);

        $adaptivequiz = $this->getDataGenerator()
            ->get_plugin_generator('mod_adaptivequiz')
            ->create_instance([
                'highestlevel' => 10,
                'lowestlevel' => 1,
                'startinglevel' => 5,
                'maximumquestions' => 10,
                'standarderror' => 5,
                'course' => $course->id,
                'questionpool' => [$questioncategory->id],
            ]);

        $user = $this->getDataGenerator()->create_user();
        $adaptiveattempt = attempt::create($adaptivequiz, $user->id);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id);
        $context = context_module::instance($cm->id);

        $quba = question_engine::make_questions_usage_by_activity('mod_adaptivequiz', $context);
        $quba->set_preferred_behaviour(attempt::ATTEMPTBEHAVIOUR);

        $fetchquestion = new fetchquestion($adaptivequiz, 1, $adaptivequiz->lowestlevel, $adaptivequiz->highestlevel);

        $administration = new item_administration($quba, $fetchquestion);

        // Random, doesn't matter.
        $attempteddifficultylevel = 1;

        // Given no questions were attempted.
        $numberofquestionsattempted = 0;
        // And a question has not been submitted.
        $determinenextdifficultylevelresult = null;

        // When performing item administration evaluation.
        $result = $administration->evaluate_ability_to_administer_next_item(
            $adaptiveattempt,
            $adaptivequiz,
            $numberofquestionsattempted,
            $attempteddifficultylevel,
            $determinenextdifficultylevelresult
        );

        // Then the result of evaluation is next item with particular properties will be administered.
        $expectation = new next_item($itemtoadministerdifficulty, 1);
        self::assertEquals($expectation, $result->next_item());
    }

    public function test_it_approves_administering_next_item_when_question_was_viewed_by_user_previously_but_not_answered(): void {
        self::resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $course = $this->getDataGenerator()->create_course();

        $questioncategory = $questiongenerator->create_question_category(
            ['contextid' => context_course::instance($course->id)->id]
        );
        $displayedquestion = $questiongenerator->create_question('truefalse', null, [
            'name' => 'True/false question',
            'category' => $questioncategory->id,
        ]);

        $itemtoadministerdifficulty = 5;
        $questiongenerator->create_question_tag([
            'questionid' => $displayedquestion->id,
            'tag' => 'adpq_'. $itemtoadministerdifficulty,
        ]);

        $adaptivequiz = $this->getDataGenerator()
            ->get_plugin_generator('mod_adaptivequiz')
            ->create_instance([
                'highestlevel' => 10,
                'lowestlevel' => 1,
                'startinglevel' => 5,
                'maximumquestions' => 10,
                'standarderror' => 5,
                'course' => $course->id,
                'questionpool' => [$questioncategory->id],
            ]);

        $user = $this->getDataGenerator()->create_user();
        $adaptiveattempt = attempt::create($adaptivequiz, $user->id);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id);
        $context = context_module::instance($cm->id);

        $quba = question_engine::make_questions_usage_by_activity('mod_adaptivequiz', $context);
        $quba->set_preferred_behaviour(attempt::ATTEMPTBEHAVIOUR);

        $fetchquestion = new fetchquestion($adaptivequiz, 1, $adaptivequiz->lowestlevel, $adaptivequiz->highestlevel);

        $administration = new item_administration($quba, $fetchquestion);

        // Random, doesn't matter.
        $attempteddifficultylevel = 1;

        // Given no questions were attempted.
        $numberofquestionsattempted = 0;
        // And a question has been displayed previously to the user, but not submitted.
        $slot = $quba->add_question(question_bank::load_question($displayedquestion->id));
        $quba->start_question($slot);

        $determinenextdifficultylevelresult = null;

        // When performing item administration evaluation.
        $result = $administration->evaluate_ability_to_administer_next_item(
            $adaptiveattempt,
            $adaptivequiz,
            $numberofquestionsattempted,
            $attempteddifficultylevel,
            $determinenextdifficultylevelresult
        );

        // Then the result of evaluation is next item is the previously displayed question.
        $expectation = new next_item($itemtoadministerdifficulty, $slot);
        self::assertEquals($expectation, $result->next_item());
    }

    public function test_it_approves_administering_next_item_when_previous_question_was_answered(): void {
        global $SESSION;

        self::resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $course = $this->getDataGenerator()->create_course();

        $questioncategory = $questiongenerator->create_question_category(
            ['contextid' => context_course::instance($course->id)->id]
        );

        $attemptedquestion1 = $questiongenerator->create_question('truefalse', null, [
            'name' => 'True/false question 1',
            'category' => $questioncategory->id,
        ]);
        $attemptedquestion1difficulty = 4;
        $questiongenerator->create_question_tag([
            'questionid' => $attemptedquestion1->id,
            'tag' => 'adpq_'. $attemptedquestion1difficulty,
        ]);

        $attemptedquestion2 = $questiongenerator->create_question('truefalse', null, [
            'name' => 'True/false question 2',
            'category' => $questioncategory->id,
        ]);
        $attemptedquestion2difficulty = 5;
        $questiongenerator->create_question_tag([
            'questionid' => $attemptedquestion2->id,
            'tag' => 'adpq_'. $attemptedquestion2difficulty,
        ]);

        $attemptedquestion3 = $questiongenerator->create_question('truefalse', null, [
            'name' => 'True/false question 3',
            'category' => $questioncategory->id,
        ]);
        $attemptedquestion3difficulty = 6;
        $questiongenerator->create_question_tag([
            'questionid' => $attemptedquestion3->id,
            'tag' => 'adpq_'. $attemptedquestion3difficulty,
        ]);

        $notattemptedquestion1 = $questiongenerator->create_question('truefalse', null, [
            'name' => 'True/false question 4',
            'category' => $questioncategory->id,
        ]);
        $notattemptedquestion1difficulty = 3;
        $questiongenerator->create_question_tag([
            'questionid' => $notattemptedquestion1->id,
            'tag' => 'adpq_'. $notattemptedquestion1difficulty,
        ]);

        $notattemptedquestion2 = $questiongenerator->create_question('truefalse', null, [
            'name' => 'True/false question 5',
            'category' => $questioncategory->id,
        ]);
        $notattemptedquestion2difficulty = 7;
        $questiongenerator->create_question_tag([
            'questionid' => $notattemptedquestion2->id,
            'tag' => 'adpq_'. $notattemptedquestion2difficulty,
        ]);

        $adaptivequiz = $this->getDataGenerator()
            ->get_plugin_generator('mod_adaptivequiz')
            ->create_instance([
                'highestlevel' => 10,
                'lowestlevel' => 1,
                'startinglevel' => 5,
                'maximumquestions' => 10,
                'standarderror' => 5,
                'course' => $course->id,
                'questionpool' => [$questioncategory->id],
            ]);

        $user = $this->getDataGenerator()->create_user();
        $adaptiveattempt = attempt::create($adaptivequiz, $user->id);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id);
        $context = context_module::instance($cm->id);

        $quba = question_engine::make_questions_usage_by_activity('mod_adaptivequiz', $context);
        $quba->set_preferred_behaviour(attempt::ATTEMPTBEHAVIOUR);

        // Given several questions were attempted.
        $slots = [];

        $slot = $quba->add_question(question_bank::load_question($attemptedquestion1->id));
        $slots[] = $slot;
        $quba->start_question($slot);

        $time = time();
        $responses = [$slot => 'True'];
        $quba->process_all_actions($time,
            $questiongenerator->get_simulated_post_data_for_questions_in_usage($quba, $responses, false));
        $quba->finish_all_questions($time);

        question_engine::save_questions_usage_by_activity($quba);

        $slot = $quba->add_question(question_bank::load_question($attemptedquestion2->id));
        $slots[] = $slot;
        $quba->start_question($slot);

        $time = time();
        $responses = [$slot => 'True'];
        $quba->process_all_actions($time,
            $questiongenerator->get_simulated_post_data_for_questions_in_usage($quba, $responses, false));
        $quba->finish_all_questions($time);

        question_engine::save_questions_usage_by_activity($quba);

        $slot = $quba->add_question(question_bank::load_question($attemptedquestion3->id));
        $slots[] = $slot;
        $quba->start_question($slot);

        $time = time();
        $responses = [$slot => 'True'];
        $quba->process_all_actions($time,
            $questiongenerator->get_simulated_post_data_for_questions_in_usage($quba, $responses, false));
        $quba->finish_all_questions($time);

        question_engine::save_questions_usage_by_activity($quba);

        $numberofquestionsattempted = 3;
        $attempteddifficultylevel = 6;

        // Initialize difficulty-questions mapping by setting a value directly in global session.
        // This is a bad practice and leads to fragile tests. Normally, operating on global session should be removed from
        // the fetching questions class.
        $SESSION->adpqtagquestsum = difficulty_questions_mapping::create_empty()
            ->add_to_questions_number_for_difficulty($attemptedquestion1difficulty, 1)
            ->add_to_questions_number_for_difficulty($attemptedquestion2difficulty, 1)
            ->add_to_questions_number_for_difficulty($attemptedquestion3difficulty, 1)
            ->add_to_questions_number_for_difficulty($notattemptedquestion2difficulty, 1)
            ->as_array();

        $determinenextdifficultylevelresult =
            determine_next_difficulty_result::with_next_difficulty_level_determined($notattemptedquestion2difficulty);

        // When performing item administration evaluation.
        $fetchquestion = new fetchquestion($adaptivequiz, 1, $adaptivequiz->lowestlevel, $adaptivequiz->highestlevel);
        $administration = new item_administration($quba, $fetchquestion);

        $result = $administration->evaluate_ability_to_administer_next_item(
            $adaptiveattempt,
            $adaptivequiz,
            $numberofquestionsattempted,
            $attempteddifficultylevel,
            $determinenextdifficultylevelresult
        );

        // Then the result of evaluation is next item with particular properties will be administered.
        $expectation = new next_item($notattemptedquestion2difficulty, count($slots) + 1);
        self::assertEquals($expectation, $result->next_item());
    }
}
