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
use mod_adaptivequiz\local\attempt\cat_model_params;
use mod_adaptivequiz\local\catalgorithm\catalgo;
use mod_adaptivequiz\local\fetchquestion;
use mod_adaptivequiz\local\question\difficulty_questions_mapping;
use mod_adaptivequiz\local\question\question_answer_evaluation_result;
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

    public function test_it_can_perform_evaluation_when_fresh_attempt_has_started(): void {
        self::resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $course = $this->getDataGenerator()->create_course();

        $questioncategory = $questiongenerator->create_question_category(
            ['contextid' => context_course::instance($course->id)->id]
        );

        $startinglevel = 5;

        $itemtoadminister = $questiongenerator->create_question('truefalse', null, [
            'name' => 'True/false question',
            'category' => $questioncategory->id,
        ]);

        $questiongenerator->create_question_tag([
            'questionid' => $itemtoadminister->id,
            'tag' => 'adpq_'. $startinglevel,
        ]);

        $adaptivequiz = $this->getDataGenerator()
            ->get_plugin_generator('mod_adaptivequiz')
            ->create_instance([
                'highestlevel' => 10,
                'lowestlevel' => 1,
                'startinglevel' => $startinglevel,
                'standarderror' => 5,
                'course' => $course->id,
                'questionpool' => [$questioncategory->id],
            ]);

        $user = $this->getDataGenerator()->create_user();
        $attempt = attempt::create($adaptivequiz, $user->id);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id);
        $context = context_module::instance($cm->id);

        $quba = question_engine::make_questions_usage_by_activity('mod_adaptivequiz', $context);
        $quba->set_preferred_behaviour(attempt::ATTEMPTBEHAVIOUR);

        $algorithm = new catalgo(false);

        $fetchquestion = new fetchquestion($adaptivequiz, 1, $adaptivequiz->lowestlevel, $adaptivequiz->highestlevel);

        $administration = new item_administration($quba, $algorithm, $fetchquestion);

        // Given no questions had been answered previously.

        // And no answer was submitted during the current attempt.
        $attempteddifficultylevel = 0;
        $questionanswerevaluationresult = null;

        // When performing item administration evaluation.
        $result = $administration->evaluate_ability_to_administer_next_item(
            $attempt,
            $adaptivequiz,
            $attempteddifficultylevel,
            $questionanswerevaluationresult
        );

        // Then the result of evaluation is next item with particular properties will be administered.
        $expectation = new next_item($startinglevel, 1);
        self::assertEquals($expectation, $result->next_item());
    }

    public function test_it_can_perform_evaluation_when_continuing_previously_started_attempt(): void {
        global $SESSION;

        self::resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $course = $this->getDataGenerator()->create_course();

        $questioncategory = $questiongenerator->create_question_category(
            ['contextid' => context_course::instance($course->id)->id]
        );

        $startinglevel = 5;

        $attemptedquestion = $questiongenerator->create_question('truefalse', null, [
            'name' => 'True/false question 1',
            'category' => $questioncategory->id,
        ]);

        $questiongenerator->create_question_tag([
            'questionid' => $attemptedquestion->id,
            'tag' => 'adpq_'. $startinglevel,
        ]);

        $itemtoadministerlevel = $startinglevel + 1;

        $itemtoadminister = $questiongenerator->create_question('truefalse', null, [
            'name' => 'True/false question',
            'category' => $questioncategory->id,
        ]);

        $questiongenerator->create_question_tag([
            'questionid' => $itemtoadminister->id,
            'tag' => 'adpq_'. $itemtoadministerlevel,
        ]);

        $adaptivequiz = $this->getDataGenerator()
            ->get_plugin_generator('mod_adaptivequiz')
            ->create_instance([
                'highestlevel' => 10,
                'lowestlevel' => 1,
                'startinglevel' => $startinglevel,
                'standarderror' => 5,
                'course' => $course->id,
                'questionpool' => [$questioncategory->id],
            ]);

        $user = $this->getDataGenerator()->create_user();
        $attempt = attempt::create($adaptivequiz, $user->id);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id);
        $context = context_module::instance($cm->id);

        $quba = question_engine::make_questions_usage_by_activity('mod_adaptivequiz', $context);
        $quba->set_preferred_behaviour(attempt::ATTEMPTBEHAVIOUR);

        $algorithm = new catalgo(false);

        $fetchquestion = new fetchquestion($adaptivequiz, 1, $adaptivequiz->lowestlevel, $adaptivequiz->highestlevel);

        $administration = new item_administration($quba, $algorithm, $fetchquestion);

        // Given the starting question was previously attempted.
        $slot = $quba->add_question(question_bank::load_question($attemptedquestion->id));
        $quba->start_question($slot);

        $time = time();
        $responses = [$slot => 'True'];
        $quba->process_all_actions($time,
            $questiongenerator->get_simulated_post_data_for_questions_in_usage($quba, $responses, false));
        $quba->finish_all_questions($time);

        question_engine::save_questions_usage_by_activity($quba);

        // Initialize difficulty-questions mapping by setting a value directly in global session.
        // This is a bad practice and leads to fragile tests. Normally, operating on global session should be removed from
        // the fetching questions class.
        $SESSION->adpqtagquestsum = difficulty_questions_mapping::create_empty()
            ->add_to_questions_number_for_difficulty($startinglevel, 1)
            ->add_to_questions_number_for_difficulty($itemtoadministerlevel, 1)
            ->as_array();

        // And no answer was submitted during the current attempt.
        $attempteddifficultylevel = 0;
        $questionanswerevaluationresult = null;

        // When performing item administration evaluation.
        $result = $administration->evaluate_ability_to_administer_next_item(
            $attempt,
            $adaptivequiz,
            $attempteddifficultylevel,
            $questionanswerevaluationresult
        );

        // Then the result of evaluation is next item with particular properties will be administered.
        $expectation = new next_item($itemtoadministerlevel, 2);
        self::assertEquals($expectation, $result->next_item());
    }

    public function test_it_stops_administering_items_when_number_of_questions_attempted_has_reached_the_maximum(): void {
        global $SESSION;

        self::resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $course = $this->getDataGenerator()->create_course();

        $questioncategory = $questiongenerator->create_question_category(
            ['contextid' => context_course::instance($course->id)->id]
        );

        $maximumquestions = 5;

        $i = 1;
        do {
            $questiongenerator->create_question('truefalse', null, [
                'name' => 'True/false question 1',
                'category' => $questioncategory->id,
            ]);

            $i++;
        } while ($i < $maximumquestions);

        $adaptivequiz = $this->getDataGenerator()
            ->get_plugin_generator('mod_adaptivequiz')
            ->create_instance([
                'highestlevel' => 10,
                'lowestlevel' => 1,
                'standarderror' => 5,
                'maximumquestions' => $maximumquestions,
                'course' => $course->id,
            ]);

        $user = $this->getDataGenerator()->create_user();

        $attempt = attempt::create($adaptivequiz, $user->id);
        cat_model_params::create_new_for_attempt($attempt->read_attempt_data()->id);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id);
        $context = context_module::instance($cm->id);

        $quba = question_engine::make_questions_usage_by_activity('mod_adaptivequiz', $context);
        $quba->set_preferred_behaviour(attempt::ATTEMPTBEHAVIOUR);

        $algorithm = new catalgo(false);
        $fetchquestion = new fetchquestion($adaptivequiz, 1, $adaptivequiz->lowestlevel, $adaptivequiz->highestlevel);

        $administration = new item_administration($quba, $algorithm, $fetchquestion);

        // Given certain amount of questions have been answered previously.
        $questionids = question_bank::get_finder()->get_questions_from_categories($questioncategory->id, '');
        foreach ($questionids as $questionid) {
            $slot = $quba->add_question(question_bank::load_question($questionid));
            $quba->start_question($slot);

            $time = time();
            $responses = [$slot => 'True'];
            $quba->process_all_actions($time,
                $questiongenerator->get_simulated_post_data_for_questions_in_usage($quba, $responses, false));
            $quba->finish_all_questions($time);

            question_engine::save_questions_usage_by_activity($quba);
        }

        // And the last attempted difficulty is within the boundaries.
        $attempteddifficultylevel = 8;

        // And the next question was answered.
        $questionanswerevaluationresult = question_answer_evaluation_result::when_answer_is_correct();
        // And the amount of questions attempted has reached the maximum.
        $i = 1;
        do {
            // Set data randomly here, it does not matter.
            $attempt->update_after_question_answered(time());
            $i++;
        } while ($i <= $maximumquestions);

        // Initialize difficulty-questions mapping by setting a value directly in global session.
        // This is a bad practice and leads to fragile tests. Normally, operating on global session should be removed from
        // the fetching questions class.
        $SESSION->adpqtagquestsum = difficulty_questions_mapping::create_empty()
            ->add_to_questions_number_for_difficulty($attempteddifficultylevel, 1)
            ->as_array();

        // When performing item administration evaluation.
        $result = $administration->evaluate_ability_to_administer_next_item(
            $attempt,
            $adaptivequiz,
            $attempteddifficultylevel,
            $questionanswerevaluationresult
        );

        // Then the result of evaluation is to stop item administration.
        self::assertTrue($result->item_administration_is_to_stop());
        // And the stoppage reason is some specific message.
        self::assertEquals(get_string('maxquestattempted', 'adaptivequiz'), $result->stoppage_reason());
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
        $attempt = attempt::create($adaptivequiz, $user->id);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id);
        $context = context_module::instance($cm->id);

        $quba = question_engine::make_questions_usage_by_activity('mod_adaptivequiz', $context);
        $quba->set_preferred_behaviour(attempt::ATTEMPTBEHAVIOUR);

        $algorithm = new catalgo(false);

        $fetchquestion = new fetchquestion($adaptivequiz, 1, $adaptivequiz->lowestlevel, $adaptivequiz->highestlevel);

        $administration = new item_administration($quba, $algorithm, $fetchquestion);

        // Random, doesn't matter.
        $attempteddifficultylevel = 1;

        // Given no questions were attempted.
        // And a question has been displayed previously to the user, but not submitted.
        $slot = $quba->add_question(question_bank::load_question($displayedquestion->id));
        $quba->start_question($slot);

        $questionanswerevaluationresult = null;

        // When performing item administration evaluation.
        $result = $administration->evaluate_ability_to_administer_next_item(
            $attempt,
            $adaptivequiz,
            $attempteddifficultylevel,
            $questionanswerevaluationresult
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

        $attempt = attempt::create($adaptivequiz, $user->id);
        cat_model_params::create_new_for_attempt($attempt->read_attempt_data()->id);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id);
        $context = context_module::instance($cm->id);

        $quba = question_engine::make_questions_usage_by_activity('mod_adaptivequiz', $context);
        $quba->set_preferred_behaviour(attempt::ATTEMPTBEHAVIOUR);

        // Given several questions were attempted previously.
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

        $i = 1;
        do {
            // Set data randomly here, it does not matter.
            $attempt->update_after_question_answered(time());
            $i++;
        } while ($i <= 3);
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

        // And an answer was submitted for the next question.
        $questionanswerevaluationresult = question_answer_evaluation_result::when_answer_is_correct();

        // When performing item administration evaluation.
        $algorithm = new catalgo(false);
        $fetchquestion = new fetchquestion($adaptivequiz, 1, $adaptivequiz->lowestlevel, $adaptivequiz->highestlevel);
        $administration = new item_administration($quba, $algorithm, $fetchquestion);

        $result = $administration->evaluate_ability_to_administer_next_item(
            $attempt,
            $adaptivequiz,
            $attempteddifficultylevel,
            $questionanswerevaluationresult
        );

        // Then the result of evaluation is next item with particular properties will be administered.
        $expectation = new next_item($notattemptedquestion2difficulty, count($slots) + 1);
        self::assertEquals($expectation, $result->next_item());
    }

    public function test_it_stops_administration_when_no_question_with_the_required_difficulty_can_be_fetched(): void {
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

        $attempt = attempt::create($adaptivequiz, $user->id);
        cat_model_params::create_new_for_attempt($attempt->read_attempt_data()->id);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id);
        $context = context_module::instance($cm->id);

        $quba = question_engine::make_questions_usage_by_activity('mod_adaptivequiz', $context);
        $quba->set_preferred_behaviour(attempt::ATTEMPTBEHAVIOUR);

        // Given several questions were attempted previously.
        $slot = $quba->add_question(question_bank::load_question($attemptedquestion1->id));
        $quba->start_question($slot);

        $time = time();
        $responses = [$slot => 'True'];
        $quba->process_all_actions($time,
            $questiongenerator->get_simulated_post_data_for_questions_in_usage($quba, $responses, false));
        $quba->finish_all_questions($time);

        question_engine::save_questions_usage_by_activity($quba);

        $slot = $quba->add_question(question_bank::load_question($attemptedquestion2->id));
        $quba->start_question($slot);

        $time = time();
        $responses = [$slot => 'True'];
        $quba->process_all_actions($time,
            $questiongenerator->get_simulated_post_data_for_questions_in_usage($quba, $responses, false));
        $quba->finish_all_questions($time);

        question_engine::save_questions_usage_by_activity($quba);

        $slot = $quba->add_question(question_bank::load_question($attemptedquestion3->id));
        $quba->start_question($slot);

        $time = time();
        $responses = [$slot => 'True'];
        $quba->process_all_actions($time,
            $questiongenerator->get_simulated_post_data_for_questions_in_usage($quba, $responses, false));
        $quba->finish_all_questions($time);

        question_engine::save_questions_usage_by_activity($quba);

        $i = 1;
        do {
            // Set data randomly here, it does not matter.
            $attempt->update_after_question_answered(time());
            $i++;
        } while ($i <= 3);
        $attempteddifficultylevel = $attemptedquestion3difficulty;

        // Initialize difficulty-questions mapping by setting a value directly in global session.
        // This is a bad practice and leads to fragile tests. Normally, operating on global session should be removed from
        // the fetching questions class.
        $SESSION->adpqtagquestsum = difficulty_questions_mapping::create_empty()
            ->add_to_questions_number_for_difficulty($attemptedquestion1difficulty, 1)
            ->add_to_questions_number_for_difficulty($attemptedquestion2difficulty, 1)
            ->add_to_questions_number_for_difficulty($attemptedquestion3difficulty, 1)
            ->as_array();

        // And an answer was submitted for the next question.
        $questionanswerevaluationresult = question_answer_evaluation_result::when_answer_is_correct();

        // When performing item administration evaluation.
        $algorithm = new catalgo(false);
        $fetchquestion = new fetchquestion($adaptivequiz, 1, $adaptivequiz->lowestlevel, $adaptivequiz->highestlevel);
        $administration = new item_administration($quba, $algorithm, $fetchquestion);

        $result = $administration->evaluate_ability_to_administer_next_item(
            $attempt,
            $adaptivequiz,
            $attempteddifficultylevel,
            $questionanswerevaluationresult
        );

        // Then the result of evaluation is to stop the attempt due to no questions for the next difficulty level.
        $expectation = item_administration_evaluation::with_stoppage_reason(
            get_string('errorfetchingquest', 'adaptivequiz', $attempteddifficultylevel + 1)
        );
        self::assertEquals($expectation, $result);
    }
}
