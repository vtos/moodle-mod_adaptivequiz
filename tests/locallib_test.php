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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot .'/mod/adaptivequiz/locallib.php');

use advanced_testcase;
use context_course;
use context_module;
use mod_adaptivequiz\event\attempt_completed;
use mod_adaptivequiz\local\attempt;
use mod_adaptivequiz\local\attempt\attempt_state;
use question_engine;

/**
 * Adaptive locallib.php PHPUnit tests.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class locallib_test extends advanced_testcase {

    /**
     * Provide input data to the parameters of the test_allowed_attempt_fail() method.
     */
    public function attempts_allowed_data_fail(): array {
        return [
            [99, 100],
            [99, 99],
        ];
    }

    /**
     * Provide input data to the parameters of the test_allowed_attempt() method.
     */
    public function attempts_allowed_data(): array {
        return [
            [99, 98],
            [0, 99],
        ];
    }

    /**
     * Provide input data to the parameters of the test_adaptivequiz_construct_view_report_orderby() method.
     */
    public function view_reports_data(): array {
        return [
            ['firstname', 'ASC'],
            ['firstname', 'DESC'],
            ['lastname', 'ASC'],
            ['lastname', 'DESC'],
            ['attempts', 'ASC'],
            ['attempts', 'DESC'],
            ['stderror', 'ASC'],
            ['stderror', 'DESC'],
        ];
    }

    /**
     * Test retrieving an array of question categories.
     *
     * @covers ::adaptivequiz_get_question_categories
     */
    public function test_get_question_categories() {
        $this->resetAfterTest();

        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $coursecontext = context_course::instance($course->id);

        adaptivequiz_make_default_categories($coursecontext);

        // Test it returns data for both course and activity contexts.
        $data = adaptivequiz_get_question_categories($coursecontext);
        $this->assertEquals(1, count($data));

        $questioncategory = $this->getDataGenerator()
            ->get_plugin_generator('core_question')
            ->create_question_category(['name' => 'My category']);

        $adaptivequiz = $this->getDataGenerator()
            ->get_plugin_generator('mod_adaptivequiz')
            ->create_instance([
                'course' => $course->id,
                'questionpool' => [$questioncategory->id],
            ]);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id);
        $activitycontext = context_module::instance($cm->id);

        $data = adaptivequiz_get_question_categories($activitycontext);
        $this->assertEquals(2, count($data));
    }

    /**
     * Test retrieving question categories used by the activity instance.
     *
     * @covers ::adaptivequiz_get_selected_question_cateogires
     */
    public function test_get_selected_question_cateogires(): void {
        $this->resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $modgenerator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');

        $course = $this->getDataGenerator()->create_course();

        $questioncategory1 = $questiongenerator->create_question_category([
            'name' => 'My category 1',
        ]);

        $questioncategory2 = $questiongenerator->create_question_category([
            'name' => 'My category 2',
        ]);

        $questioncategory3 = $questiongenerator->create_question_category([
            'name' => 'My category 3',
        ]);

        $questioncategory4 = $questiongenerator->create_question_category([
            'name' => 'My category 4',
        ]);

        $adaptivequiz1 = $modgenerator->create_instance([
            'course' => $course->id,
            'questionpool' => [
                $questioncategory1->id,
                $questioncategory2->id,
            ],
        ]);

        $adaptivequiz2 = $modgenerator->create_instance([
            'course' => $course->id,
            'questionpool' => [
                $questioncategory3->id,
                $questioncategory4->id,
            ],
        ]);

        // End of setup.

        $idlist = adaptivequiz_get_selected_question_cateogires($adaptivequiz1->id);

        $this->assertEquals(2, count($idlist));
        $this->assertEquals([
            $questioncategory1->id,
            $questioncategory2->id,
        ], $idlist);

        $idlist = adaptivequiz_get_selected_question_cateogires($adaptivequiz2->id);

        $this->assertEquals(2, count($idlist));
        $this->assertEquals([
            $questioncategory3->id,
            $questioncategory4->id,
        ], $idlist);
    }

    /**
     * This function tests failing conditions for counting user's previous attempts that have been marked as completed.
     *
     * @covers ::adaptivequiz_count_user_previous_attempts
     */
    public function test_count_user_previous_attempts_fail(): void {
        $this->resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $modgenerator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');

        $course = $this->getDataGenerator()->create_course();

        $questioncategory = $questiongenerator->create_question_category([
            'name' => 'My category',
        ]);

        $question = $questiongenerator->create_question('shortanswer', null, [
            'category' => $questioncategory->id,
        ]);

        $startinglevel = 1;

        $questiongenerator->create_question_tag([
            'questionid' => $question->id,
            'tag' => "adpq_$startinglevel",
        ]);

        $adaptivequiz = $modgenerator->create_instance([
            'course' => $course->id,
            'startinglevel' => $startinglevel,
            'lowestlevel' => 1,
            'highestlevel' => 10,
            'questionpool' => [
                $questioncategory->id,
            ],
        ]);

        $context = context_module::instance($adaptivequiz->cmid);

        $adaptivequizforattempt = clone($adaptivequiz);
        $adaptivequizforattempt->context = $context;

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // End of setup.

        $this->assertEquals(0, adaptivequiz_count_user_previous_attempts($adaptivequiz->id, $user1->id));
        $this->assertEquals(0, adaptivequiz_count_user_previous_attempts($adaptivequiz->id, $user2->id));

        $attempt = new attempt($adaptivequizforattempt, $user1->id);
        $attempt->set_level($adaptivequiz->startinglevel);
        $attempt->start_attempt();

        $uniqueid = $attempt->get_quba()->get_id();
        adaptivequiz_complete_attempt(uniqueid: $uniqueid, adaptivequiz: $adaptivequiz, context: $context, userid: $user1->id,
            standarderror: '', statusmessage: '');

        $this->assertEquals(1, adaptivequiz_count_user_previous_attempts($adaptivequiz->id, $user1->id));
        $this->assertEquals(0, adaptivequiz_count_user_previous_attempts($adaptivequiz->id, $user2->id));

        $attempt = new attempt($adaptivequizforattempt, $user1->id);
        $attempt->set_level($adaptivequiz->startinglevel);
        $attempt->start_attempt();

        $uniqueid = $attempt->get_quba()->get_id();
        adaptivequiz_complete_attempt(uniqueid: $uniqueid, adaptivequiz: $adaptivequiz, context: $context, userid: $user1->id,
            standarderror: '', statusmessage: '');

        $attempt = new attempt($adaptivequizforattempt, $user2->id);
        $attempt->set_level($adaptivequiz->startinglevel);
        $attempt->start_attempt();

        $uniqueid = $attempt->get_quba()->get_id();
        adaptivequiz_complete_attempt(uniqueid: $uniqueid, adaptivequiz: $adaptivequiz, context: $context, userid: $user2->id,
            standarderror: '', statusmessage: '');

        $this->assertEquals(2, adaptivequiz_count_user_previous_attempts($adaptivequiz->id, $user1->id));
        $this->assertEquals(1, adaptivequiz_count_user_previous_attempts($adaptivequiz->id, $user2->id));
    }

    /**
     * This function tests a non-failing conditions for counting user's previous attempts that have been marked as completed.
     *
     * @covers ::adaptivequiz_count_user_previous_attempts
     */
    public function test_count_user_previous_attempts_inprogress(): void {
        $this->resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $modgenerator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');

        $course = $this->getDataGenerator()->create_course();

        $questioncategory = $questiongenerator->create_question_category([
            'name' => 'My category',
        ]);

        $question = $questiongenerator->create_question('shortanswer', null, [
            'category' => $questioncategory->id,
        ]);

        $startinglevel = 1;

        $questiongenerator->create_question_tag([
            'questionid' => $question->id,
            'tag' => "adpq_$startinglevel",
        ]);

        $adaptivequiz = $modgenerator->create_instance([
            'course' => $course->id,
            'startinglevel' => $startinglevel,
            'lowestlevel' => 1,
            'highestlevel' => 10,
            'questionpool' => [
                $questioncategory->id,
            ],
        ]);

        $context = context_module::instance($adaptivequiz->cmid);

        $adaptivequizforattempt = clone($adaptivequiz);
        $adaptivequizforattempt->context = $context;

        $user = $this->getDataGenerator()->create_user();

        // End of setup.

        $attempt = new attempt($adaptivequizforattempt, $user->id);
        $attempt->set_level($adaptivequiz->startinglevel);
        $attempt->start_attempt();

        $uniqueid = $attempt->get_quba()->get_id();

        adaptivequiz_complete_attempt(uniqueid: $uniqueid, adaptivequiz: $adaptivequiz, context: $context, userid: $user->id,
            standarderror: '', statusmessage: '');

        $this->assertEquals(1, adaptivequiz_count_user_previous_attempts($adaptivequiz->id, $user->id));

        $attempt = new attempt($adaptivequizforattempt, $user->id);
        $attempt->set_level($adaptivequiz->startinglevel);
        $attempt->start_attempt();

        $uniqueid = $attempt->get_quba()->get_id();

        adaptivequiz_complete_attempt(uniqueid: $uniqueid, adaptivequiz: $adaptivequiz, context: $context, userid: $user->id,
            standarderror: '', statusmessage: '');

        $this->assertEquals(2, adaptivequiz_count_user_previous_attempts($adaptivequiz->id, $user->id));
    }

    /**
     * This function tests failing conditions for determining whether a user is allowed
     * further attemtps at the activity.
     *
     * @dataProvider attempts_allowed_data_fail
     * @param int $maxattempts the maximum number of attempts allowed
     * @param int $attempts the number of attempts taken thus far
     * @covers ::adaptivequiz_allowed_attempt
     */
    public function test_allowed_attempt_no_more_attempts_allowed($maxattempts, $attempts) {
        $data = adaptivequiz_allowed_attempt($maxattempts, $attempts);
        $this->assertFalse($data);
    }

    /**
     * This function tests failing conditions for determining whether a user is allowed
     * further attemtps at the activity.
     *
     * @dataProvider attempts_allowed_data
     * @param int $maxattempts the maximum number of attempts allowed
     * @param int $attempts the number of attempts taken thus far
     * @covers ::adaptivequiz_allowed_attempt
     */
    public function test_allowed_attempt($maxattempts, $attempts) {
        $data = adaptivequiz_allowed_attempt($maxattempts, $attempts);
        $this->assertTrue($data);
    }

    /**
     * This function tests adaptivequiz_uniqueid_part_of_attempt().
     *
     * @covers ::adaptivequiz_uniqueid_part_of_attempt
     */
    public function test_adaptivequiz_uniqueid_part_of_attempt(): void {
        $this->resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $modgenerator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');

        $course = $this->getDataGenerator()->create_course();

        $questioncategory = $questiongenerator->create_question_category([
            'name' => 'My category',
        ]);

        $question = $questiongenerator->create_question('shortanswer', null, [
            'category' => $questioncategory->id,
        ]);

        $startinglevel = 1;

        $questiongenerator->create_question_tag([
            'questionid' => $question->id,
            'tag' => "adpq_$startinglevel",
        ]);

        $adaptivequiz1 = $modgenerator->create_instance([
            'course' => $course->id,
            'startinglevel' => $startinglevel,
            'lowestlevel' => 1,
            'highestlevel' => 10,
            'questionpool' => [
                $questioncategory->id,
            ],
        ]);

        $adaptivequiz2 = $modgenerator->create_instance([
            'course' => $course->id,
            'startinglevel' => $startinglevel,
            'lowestlevel' => 1,
            'highestlevel' => 10,
            'questionpool' => [
                $questioncategory->id,
            ],
        ]);

        $context = context_module::instance($adaptivequiz1->cmid);

        $adaptivequizforattempt = clone($adaptivequiz1);
        $adaptivequizforattempt->context = $context;

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // End of setup.

        $attempt = new attempt($adaptivequizforattempt, $user1->id);
        $attempt->set_level($adaptivequiz1->startinglevel);
        $attempt->start_attempt();

        $uniqueid = $attempt->get_quba()->get_id();

        $this->assertTrue(adaptivequiz_uniqueid_part_of_attempt($uniqueid, $adaptivequiz1->id, $user1->id));
        $this->assertFalse(adaptivequiz_uniqueid_part_of_attempt($uniqueid, $adaptivequiz1->id, $user2->id));

        $this->assertFalse(adaptivequiz_uniqueid_part_of_attempt($uniqueid, $adaptivequiz2->id, $user1->id));
        $this->assertFalse(adaptivequiz_uniqueid_part_of_attempt($uniqueid, $adaptivequiz2->id, $user2->id));
    }

    /**
     * This function tests the updating of the attempt data.
     *
     * @covers ::adaptivequiz_update_attempt_data
     */
    public function test_adaptivequiz_update_attempt_data(): void {
        global $DB;

        $this->resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $modgenerator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');

        $course = $this->getDataGenerator()->create_course();

        $questioncategory = $questiongenerator->create_question_category([
            'name' => 'My category',
        ]);

        $question = $questiongenerator->create_question('shortanswer', null, [
            'category' => $questioncategory->id,
        ]);

        $startinglevel = 1;

        $questiongenerator->create_question_tag([
            'questionid' => $question->id,
            'tag' => "adpq_$startinglevel",
        ]);

        $adaptivequiz = $modgenerator->create_instance([
            'course' => $course->id,
            'startinglevel' => $startinglevel,
            'lowestlevel' => 1,
            'highestlevel' => 10,
            'questionpool' => [
                $questioncategory->id,
            ],
        ]);

        $context = context_module::instance($adaptivequiz->cmid);

        $adaptivequizforattempt = clone($adaptivequiz);
        $adaptivequizforattempt->context = $context;

        $user = $this->getDataGenerator()->create_user();

        // End of setup.

        // 'Answer' the question as the user.

        $attempt = new attempt($adaptivequizforattempt, $user->id);
        $attempt->set_level($adaptivequiz->startinglevel);
        $attempt->start_attempt();
        $attempt->get_attempt();
        $quba = $attempt->initialize_quba();
        $slot = $attempt->find_last_quest_used_by_attempt($quba);

        $time = time();
        $quba->process_all_actions($time, $quba->prepare_simulated_post_data([
            $slot => ['answer' => true],
        ]));
        $quba->finish_all_questions($time);
        question_engine::save_questions_usage_by_activity($quba);

        $attemptrecord = $attempt->get_attempt();

        $this->assertEquals(0, $attemptrecord->difficultysum);
        $this->assertEquals(0, $attemptrecord->questionsattempted);
        $this->assertEquals(999, $attemptrecord->standarderror);
        $this->assertEquals(0, $attemptrecord->measure);

        $uniqueid = $quba->get_id();
        adaptivequiz_update_attempt_data(uniqueid: $uniqueid, instance: $adaptivequiz->id, userid: $user->id, level: 50,
            standarderror: 0.002, measure: 0.99);

        $attemptrecord = $attempt->get_attempt();
        $this->assertEquals(50, $attemptrecord->difficultysum);
        $this->assertEquals(1, $attemptrecord->questionsattempted);
        $this->assertEquals(0.002, $attemptrecord->standarderror);
        $this->assertEquals(0.99, $attemptrecord->measure);
    }

    /**
     * This function tests the updating of the attempt data.
     *
     * @covers ::adaptivequiz_update_attempt_data
     */
    public function test_adaptivequiz_update_attempt_data_using_infinite_value(): void {
        $result = adaptivequiz_update_attempt_data(3, 13, 3, -INF, 0.02, 0.1);
        $this->assertFalse($result);
    }

    /**
     * This function tests completing an attempt.
     *
     * @covers ::adaptivequiz_complete_attempt
     */
    public function test_adaptivequiz_complete_attempt(): void {
        global $DB;

        $this->resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $modgenerator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');

        $course = $this->getDataGenerator()->create_course();

        $questioncategory = $questiongenerator->create_question_category([
            'name' => 'My category',
        ]);

        $question = $questiongenerator->create_question('shortanswer', null, [
            'category' => $questioncategory->id,
        ]);

        $startinglevel = 1;

        $questiongenerator->create_question_tag([
            'questionid' => $question->id,
            'tag' => "adpq_$startinglevel",
        ]);

        $adaptivequiz = $modgenerator->create_instance([
            'course' => $course->id,
            'startinglevel' => $startinglevel,
            'lowestlevel' => 1,
            'highestlevel' => 10,
            'questionpool' => [
                $questioncategory->id,
            ],
        ]);

        $context = context_module::instance($adaptivequiz->cmid);

        $adaptivequizforattempt = clone($adaptivequiz);
        $adaptivequizforattempt->context = $context;

        $user = $this->getDataGenerator()->create_user();

        // End of setup.

        $attempt = new attempt($adaptivequizforattempt, $user->id);
        $attempt->set_level($adaptivequiz->startinglevel);
        $attempt->start_attempt();

        $uniqueid = $attempt->get_quba()->get_id();
        adaptivequiz_complete_attempt(uniqueid: $uniqueid, adaptivequiz: $adaptivequiz, context: $context, userid: $user->id,
            standarderror: '1', statusmessage: 'php unit test');

        $attemptrecord = $DB->get_record('adaptivequiz_attempt', ['uniqueid' => $uniqueid],
            'id, attemptstopcriteria, attemptstate, standarderror', MUST_EXIST);

        $this->assertEquals('php unit test', $attemptrecord->attemptstopcriteria);
        $this->assertEquals(attempt_state::COMPLETED, $attemptrecord->attemptstate);
        $this->assertEquals('1.00000', $attemptrecord->standarderror);
    }

    /**
     * @covers ::adaptivequiz_complete_attempt
     */
    public function test_event_is_triggered_on_attempt_completion(): void {
        global $DB;

        $this->resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $modgenerator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');

        $course = $this->getDataGenerator()->create_course();

        $questioncategory = $questiongenerator->create_question_category([
            'name' => 'My category',
        ]);

        $question = $questiongenerator->create_question('shortanswer', null, [
            'category' => $questioncategory->id,
        ]);

        $startinglevel = 1;

        $questiongenerator->create_question_tag([
            'questionid' => $question->id,
            'tag' => "adpq_$startinglevel",
        ]);

        $adaptivequiz = $modgenerator->create_instance([
            'course' => $course->id,
            'startinglevel' => $startinglevel,
            'lowestlevel' => 1,
            'highestlevel' => 10,
            'questionpool' => [
                $questioncategory->id,
            ],
        ]);

        $context = context_module::instance($adaptivequiz->cmid);

        $adaptivequizforattempt = clone($adaptivequiz);
        $adaptivequizforattempt->context = $context;

        $user = $this->getDataGenerator()->create_user();

        // End of setup.

        $attempt = new attempt($adaptivequizforattempt, $user->id);
        $attempt->set_level($adaptivequiz->startinglevel);
        $attempt->start_attempt();

        $uniqueid = $attempt->get_quba()->get_id();

        $attemptrecord = $DB->get_record('adaptivequiz_attempt', ['uniqueid' => $uniqueid], '*', MUST_EXIST);

        $eventsink = $this->redirectEvents();
        adaptivequiz_complete_attempt($uniqueid, $adaptivequiz, $context, $user->id, '1', 'php unit test');
        $events = $eventsink->get_events();
        $eventsink->close();

        $attemptcompletedevent = null;
        foreach ($events as $event) {
            if ($event instanceof attempt_completed) {
                $attemptcompletedevent = $event;
                break;
            }
        }

        $this->assertNotNull($attemptcompletedevent,
            sprintf('Failed asserting that event %s was triggered.', attempt_completed::class));
        $this->assertEquals($attemptrecord->id, $event->objectid);
        $this->assertEquals($context, $attemptcompletedevent->get_context());
        $this->assertEquals($user->id, $event->userid);
        $this->assertEquals($attemptrecord, $event->get_record_snapshot('adaptivequiz_attempt', $attemptrecord->id));
        $this->assertEquals($adaptivequiz, $event->get_record_snapshot('adaptivequiz', $adaptivequiz->id));
    }

    /**
     * This function tests checking if the minimum number of questions have been attempted.
     *
     * @covers ::adaptivequiz_min_attempts_reached
     */
    public function test_adaptivequiz_min_attempts_reached(): void {
        $this->resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $modgenerator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');

        $course = $this->getDataGenerator()->create_course();

        $questioncategory = $questiongenerator->create_question_category([
            'name' => 'My category',
        ]);

        $question = $questiongenerator->create_question('shortanswer', null, [
            'category' => $questioncategory->id,
        ]);

        $startinglevel = 1;

        $questiongenerator->create_question_tag([
            'questionid' => $question->id,
            'tag' => "adpq_$startinglevel",
        ]);

        $adaptivequiz = $modgenerator->create_instance([
            'course' => $course->id,
            'startinglevel' => $startinglevel,
            'lowestlevel' => 1,
            'highestlevel' => 10,
            'minimumquestions' => 3,
            'maximumquestions' => 10,
            'questionpool' => [
                $questioncategory->id,
            ],
        ]);

        $context = context_module::instance($adaptivequiz->cmid);

        $adaptivequizforattempt = clone($adaptivequiz);
        $adaptivequizforattempt->context = $context;

        $user = $this->getDataGenerator()->create_user();

        // End of setup.

        $attempt = new attempt($adaptivequizforattempt, $user->id);
        $attempt->set_level($adaptivequiz->startinglevel);
        $attempt->start_attempt();

        $uniqueid = $attempt->get_quba()->get_id();

        // These are not relevant for this test when updating an attempt.
        $difflogit = 0;
        $standarderror = 0;
        $measure = 0;

        adaptivequiz_update_attempt_data($uniqueid, $adaptivequiz->id, $user->id, $difflogit, $standarderror, $measure);
        $this->assertFalse(adaptivequiz_min_attempts_reached($uniqueid, $adaptivequiz->id, $user->id));

        adaptivequiz_update_attempt_data($uniqueid, $adaptivequiz->id, $user->id, $difflogit, $standarderror, $measure);
        $this->assertFalse(adaptivequiz_min_attempts_reached($uniqueid, $adaptivequiz->id, $user->id));

        adaptivequiz_update_attempt_data($uniqueid, $adaptivequiz->id, $user->id, $difflogit, $standarderror, $measure);
        $this->assertTrue(adaptivequiz_min_attempts_reached($uniqueid, $adaptivequiz->id, $user->id));
    }
}
