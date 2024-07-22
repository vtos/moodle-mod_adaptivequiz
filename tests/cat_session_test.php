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

namespace mod_adaptivequiz;

use advanced_testcase;
use context_course;
use context_module;
use core_tag_tag;
use mod_adaptivequiz\local\attempt;
use mod_adaptivequiz\local\attempt\attempt_state;
use question_bank;
use question_engine;
use question_usage_by_activity;

/**
 * Tests for the adaptive quiz session class.
 *
 * @package    mod_adaptivequiz
 * @copyright  2024 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_adaptivequiz\cat_session
 */
class cat_session_test extends advanced_testcase {

    public function test_item_administration_sets_attempt_to_completed(): void {
        global $DB;

        $this->resetAfterTest();

        $datagenerator = $this->getDataGenerator();
        $questionsgenerator = $datagenerator->get_plugin_generator('core_question');
        $modgenerator = $datagenerator->get_plugin_generator('mod_adaptivequiz');

        $course = $datagenerator->create_course();
        $user = $datagenerator->create_user();

        $qcategory = $questionsgenerator->create_question_category([
            'contextid' => context_course::instance($course->id)->id,
        ]);

        $question1 = $questionsgenerator->create_question('truefalse', null, [
            'category' => $qcategory->id,
        ]);
        $questionsgenerator->create_question_tag([
            'questionid' => $question1->id,
            'tag' => 'adpq_3',
        ]);

        $question2 = $questionsgenerator->create_question('truefalse', null, [
            'category' => $qcategory->id,
        ]);
        $questionsgenerator->create_question_tag([
            'questionid' => $question2->id,
            'tag' => 'adpq_5',
        ]);

        $adaptivequiz = $modgenerator->create_instance([
            'course' => $course->id,
            'questionpool' => [$qcategory->id],
            'lowestlevel' => 1,
            'highestlevel' => 5,
            'startinglevel' => 3,
            'minimumquestions' => 2,
            'maximumquestions' => 3,
            'standarderror' => 5,
        ]);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id, $course->id, false, MUST_EXIST);
        $modcontext = context_module::instance($cm->id);

        $adaptivequiz->context = $modcontext;

        $this->setUser($user);

        $attempt = new attempt($adaptivequiz, $user->id);
        $attemptrecord = $attempt->get_attempt();
        $attempt->initialize_quba($modcontext);

        $quba = $attempt->get_quba();

        $time = time();

        $question = question_bank::load_question($question1->id);
        $slot = $quba->add_question($question);
        $quba->start_question($slot);
        question_engine::save_questions_usage_by_activity($quba);

        // Set quba id for the attempt in a hacky way, no public API for this historically.
        $attemptrecord->uniqueid = $quba->get_id();
        $DB->update_record('adaptivequiz_attempt', $attemptrecord);

        $quba->process_all_actions($time, $quba->prepare_simulated_post_data([
            $slot => ['answer' => true],
        ]));
        $quba->finish_all_questions($time);
        question_engine::save_questions_usage_by_activity($quba);

        $attempt->set_level(5);

        $question = question_bank::load_question($question2->id);
        $slot = $quba->add_question($question);
        $quba->start_question($slot);
        question_engine::save_questions_usage_by_activity($quba);

        $quba->process_all_actions($time, $quba->prepare_simulated_post_data([
            $slot => ['answer' => true],
        ]));
        $quba->finish_all_questions($time);
        question_engine::save_questions_usage_by_activity($quba);

        $attempt->set_level(5);

        cat_session::run_item_administration($attemptrecord->uniqueid, $adaptivequiz, $modcontext, $attempt);

        $completedattemptrecord = $DB->get_record('adaptivequiz_attempt', ['uniqueid' => $attemptrecord->uniqueid],
            'attemptstate, attemptstopcriteria', MUST_EXIST);

        self::assertEquals(attempt_state::COMPLETED, $completedattemptrecord->attemptstate);
        self::assertEquals('Unable to fetch a question for level 5', $completedattemptrecord->attemptstopcriteria);
    }

    public function test_it_administers_next_item(): void {
        global $DB;

        $this->resetAfterTest();

        $datagenerator = $this->getDataGenerator();
        $questionsgenerator = $datagenerator->get_plugin_generator('core_question');
        $modgenerator = $datagenerator->get_plugin_generator('mod_adaptivequiz');

        $course = $datagenerator->create_course();
        $user = $datagenerator->create_user();

        $qcategory = $questionsgenerator->create_question_category([
            'contextid' => context_course::instance($course->id)->id,
        ]);

        $question1 = $questionsgenerator->create_question('truefalse', null, [
            'category' => $qcategory->id,
        ]);
        $questionsgenerator->create_question_tag([
            'questionid' => $question1->id,
            'tag' => 'adpq_3',
        ]);

        $question2 = $questionsgenerator->create_question('truefalse', null, [
            'category' => $qcategory->id,
        ]);
        $questionsgenerator->create_question_tag([
            'questionid' => $question2->id,
            'tag' => 'adpq_4',
        ]);

        $question3 = $questionsgenerator->create_question('truefalse', null, [
            'category' => $qcategory->id,
        ]);
        $questionsgenerator->create_question_tag([
            'questionid' => $question3->id,
            'tag' => 'adpq_5',
        ]);

        $question4 = $questionsgenerator->create_question('truefalse', null, [
            'category' => $qcategory->id,
        ]);
        $questionsgenerator->create_question_tag([
            'questionid' => $question4->id,
            'tag' => 'adpq_6',
        ]);

        $adaptivequiz = $modgenerator->create_instance([
            'course' => $course->id,
            'questionpool' => [$qcategory->id],
            'lowestlevel' => 3,
            'highestlevel' => 6,
            'startinglevel' => 3,
            'minimumquestions' => 3,
            'maximumquestions' => 4,
            'standarderror' => 5,
        ]);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id, $course->id, false, MUST_EXIST);
        $modcontext = context_module::instance($cm->id);

        $adaptivequiz->context = $modcontext;

        $this->setUser($user);

        $attempt = new attempt($adaptivequiz, $user->id);
        $attemptrecord = $attempt->get_attempt();
        $attempt->initialize_quba($modcontext);

        $quba = $attempt->get_quba();

        $time = time();

        $question = question_bank::load_question($question1->id);
        $slot = $quba->add_question($question);
        $quba->start_question($slot);
        question_engine::save_questions_usage_by_activity($quba);

        // Set quba id for the attempt in a hacky way, no public API for this historically.
        $attemptrecord->uniqueid = $quba->get_id();
        $DB->update_record('adaptivequiz_attempt', $attemptrecord);

        $quba->process_all_actions($time, $quba->prepare_simulated_post_data([
            $slot => ['answer' => true],
        ]));
        $quba->finish_all_questions($time);
        question_engine::save_questions_usage_by_activity($quba);

        $attempt->set_level(5);

        $question = question_bank::load_question($question3->id);
        $slot = $quba->add_question($question);
        $quba->start_question($slot);
        question_engine::save_questions_usage_by_activity($quba);

        $quba->process_all_actions($time, $quba->prepare_simulated_post_data([
            $slot => ['answer' => true],
        ]));
        $quba->finish_all_questions($time);
        question_engine::save_questions_usage_by_activity($quba);

        $attempt->set_level(6);

        cat_session::run_item_administration($attemptrecord->uniqueid, $adaptivequiz, $modcontext, $attempt);
        $slot = $attempt->get_question_slot_number();

        self::assertEquals(3, $slot);

        // Important: reload quba.
        $quba = $attempt->initialize_quba($modcontext);

        // Assert difficulty level of the administered item. Fetch actual data from the database directly.

        $question = $quba->get_question($slot);

        $questiontags = core_tag_tag::get_item_tags('core_question', 'question', $question->id);
        $questiontags = array_filter($questiontags, function (core_tag_tag $tag): bool {
            return substr($tag->name, 0, strlen(ADAPTIVEQUIZ_QUESTION_TAG)) === ADAPTIVEQUIZ_QUESTION_TAG;
        });
        $questiontag = array_shift($questiontags);

        $level = substr($questiontag->name, strlen(ADAPTIVEQUIZ_QUESTION_TAG));

        self::assertEquals(6, $level);

        // Ensure the attempt didn't change its state.
        $inprogressattemptrecord = $DB->get_record('adaptivequiz_attempt', ['uniqueid' => $attemptrecord->uniqueid],
            'attemptstate, attemptstopcriteria', MUST_EXIST);

        self::assertEquals(attempt_state::IN_PROGRESS, $inprogressattemptrecord->attemptstate);
        self::assertEmpty($inprogressattemptrecord->attemptstopcriteria);
    }

    public function test_it_sets_attempt_status_when_processing_administered_item_result(): void {
        global $DB;

        $this->resetAfterTest();

        $datagenerator = $this->getDataGenerator();
        $questionsgenerator = $datagenerator->get_plugin_generator('core_question');
        $modgenerator = $datagenerator->get_plugin_generator('mod_adaptivequiz');

        $course = $datagenerator->create_course();
        $user = $datagenerator->create_user();

        $qcategory = $questionsgenerator->create_question_category([
            'contextid' => context_course::instance($course->id)->id,
        ]);

        $question1 = $questionsgenerator->create_question('truefalse', null, [
            'category' => $qcategory->id,
        ]);
        $questionsgenerator->create_question_tag([
            'questionid' => $question1->id,
            'tag' => 'adpq_3',
        ]);

        $question2 = $questionsgenerator->create_question('truefalse', null, [
            'category' => $qcategory->id,
        ]);
        $questionsgenerator->create_question_tag([
            'questionid' => $question2->id,
            'tag' => 'adpq_5',
        ]);

        $adaptivequiz = $modgenerator->create_instance([
            'course' => $course->id,
            'questionpool' => [$qcategory->id],
            'lowestlevel' => 3,
            'highestlevel' => 6,
            'startinglevel' => 3,
            'minimumquestions' => 1,
            'maximumquestions' => 4,

            // The value is extremely high to make sure this will be the stoppage reason.
            'standarderror' => 40,
        ]);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id, $course->id, false, MUST_EXIST);
        $modcontext = context_module::instance($cm->id);

        $adaptivequiz->context = $modcontext;

        $this->setUser($user);

        $attempt = new attempt($adaptivequiz, $user->id);
        $attemptrecord = $attempt->get_attempt();
        $attempt->initialize_quba($modcontext);

        $quba = $attempt->get_quba();

        $time = time();

        $question = question_bank::load_question($question1->id);
        $slot = $quba->add_question($question);
        $quba->start_question($slot);
        question_engine::save_questions_usage_by_activity($quba);

        // Set quba id for the attempt in a hacky way, no public API for this historically.
        $attemptrecord->uniqueid = $quba->get_id();
        $DB->update_record('adaptivequiz_attempt', $attemptrecord);

        $quba->process_all_actions($time, $quba->prepare_simulated_post_data([
            $slot => ['answer' => true],
        ]));
        $quba->finish_all_questions($time);
        question_engine::save_questions_usage_by_activity($quba);

        $attempt->set_level(5);

        adaptivequiz_update_attempt_data($attemptrecord->uniqueid, $adaptivequiz->id, $user->id, 0, 0, 0);

        $question = question_bank::load_question($question2->id);
        $slot = $quba->add_question($question);
        $quba->start_question($slot);
        question_engine::save_questions_usage_by_activity($quba);

        $simulatedresponses = [
            $slot => ['answer' => true],
        ];

        $qubahelper = function (question_usage_by_activity $quba) use ($simulatedresponses): void {
            $simulatedpostdata = $quba->prepare_simulated_post_data($simulatedresponses);

            $time = time();
            $quba->process_all_actions($time, $simulatedpostdata);
            $quba->finish_all_questions($time);
        };

        cat_session::process_administered_item_result($attemptrecord->uniqueid, $adaptivequiz, $attempt, $qubahelper);

        self::assertEquals('Calculated standard error of 34 is within the limits imposed by the activity 40',
            $attempt->get_status());
    }

    public function test_it_processes_administered_item_result(): void {
        global $DB;

        $this->resetAfterTest();

        $datagenerator = $this->getDataGenerator();
        $questionsgenerator = $datagenerator->get_plugin_generator('core_question');
        $modgenerator = $datagenerator->get_plugin_generator('mod_adaptivequiz');

        $course = $datagenerator->create_course();
        $user = $datagenerator->create_user();

        $qcategory = $questionsgenerator->create_question_category([
            'contextid' => context_course::instance($course->id)->id,
        ]);

        $question1 = $questionsgenerator->create_question('truefalse', null, [
            'category' => $qcategory->id,
        ]);
        $questionsgenerator->create_question_tag([
            'questionid' => $question1->id,
            'tag' => 'adpq_3',
        ]);

        $question2 = $questionsgenerator->create_question('truefalse', null, [
            'category' => $qcategory->id,
        ]);
        $questionsgenerator->create_question_tag([
            'questionid' => $question2->id,
            'tag' => 'adpq_5',
        ]);

        $adaptivequiz = $modgenerator->create_instance([
            'course' => $course->id,
            'questionpool' => [$qcategory->id],
            'lowestlevel' => 3,
            'highestlevel' => 6,
            'startinglevel' => 3,
            'minimumquestions' => 1,
            'maximumquestions' => 4,
            'standarderror' => 5,
        ]);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id, $course->id, false, MUST_EXIST);
        $modcontext = context_module::instance($cm->id);

        $adaptivequiz->context = $modcontext;

        $this->setUser($user);

        $attempt = new attempt($adaptivequiz, $user->id);
        $attemptrecord = $attempt->get_attempt();
        $attempt->initialize_quba($modcontext);

        $quba = $attempt->get_quba();

        $question = question_bank::load_question($question2->id);
        $slot = $quba->add_question($question);
        $quba->start_question($slot);
        question_engine::save_questions_usage_by_activity($quba);

        // Set quba id for the attempt in a hacky way, no public API for this historically.
        $attemptrecord->uniqueid = $quba->get_id();
        $DB->update_record('adaptivequiz_attempt', $attemptrecord);

        $simulatedresponses = [
            $slot => ['answer' => true],
        ];

        $qubahelper = function (question_usage_by_activity $quba) use ($simulatedresponses): void {
            $simulatedpostdata = $quba->prepare_simulated_post_data($simulatedresponses);

            $time = time();
            $quba->process_all_actions($time, $simulatedpostdata);
            $quba->finish_all_questions($time);
        };

        cat_session::process_administered_item_result($attemptrecord->uniqueid, $adaptivequiz, $attempt, $qubahelper);

        $inprogressattemptrecord = $DB->get_record('adaptivequiz_attempt', ['uniqueid' => $attemptrecord->uniqueid],
            'attemptstate, attemptstopcriteria', MUST_EXIST);

        self::assertEquals(attempt_state::IN_PROGRESS, $inprogressattemptrecord->attemptstate);
        self::assertEmpty($inprogressattemptrecord->attemptstopcriteria);
    }
}
