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

namespace mod_adaptivequiz\local\attempt;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/mod/adaptivequiz/locallib.php');

use advanced_testcase;
use context_module;
use dml_missing_record_exception;
use mod_adaptivequiz\event\attempt_completed;
use stdClass;

/**
 * Tests for the attempt class.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_adaptivequiz\local\attempt
 */
class attempt_test extends advanced_testcase {
    /** @var stdClass $activityinstance adaptivequiz activity instance object */
    protected $activityinstance = null;

    /** @var stdClass $cm a partially completed course module object */
    protected $cm = null;

    /** @var stdClass $user a user object */
    protected $user = null;

    /**
     * This function loads data into the PHPUnit tables for testing
     */
    protected function setup_test_data_xml() {
        $this->dataset_from_files(
            [__DIR__.'/../../fixtures/mod_adaptivequiz_adaptiveattempt.xml']
        )->to_database();
    }

    /**
     * This function creates a default user and activity instance using generator classes
     * The activity parameters created are are follows:
     * lowest difficulty level: 1
     * highest difficulty level: 10
     * minimum question attempts: 2
     * maximum question attempts: 10
     * standard error: 1.1
     * starting level: 5
     * question category ids: 1
     * course id: 2
     * @return void
     */
    protected function setup_generator_data() {
        // Create test user.
        $this->user = $this->getDataGenerator()->create_user();
        $this->setUser($this->user);
        $this->setAdminUser();

        // Create activity.
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');
        $options = array(
            'highestlevel' => 10,
            'lowestlevel' => 1,
            'minimumquestions' => 2,
            'maximumquestions' => 10,
            'standarderror' => 1.1,
            'startinglevel' => 5,
            'questionpool' => array(1),
            'course' => 1
        );

        $this->activityinstance = $generator->create_instance($options);

        $this->cm = new stdClass();
        $this->cm->id = $this->activityinstance->cmid;
    }

    /**
     * This function tests results returned from get_question_mark().
     */
    public function test_get_question_mark_with_quba_return_float() {
        $this->resetAfterTest();

        // Test quba returning a mark of 1.0.
        $mockquba = $this->createMock('question_usage_by_activity');

        $mockquba->expects($this->once())
            ->method('get_question_mark')
            ->will($this->returnValue(1.0));

        $adaptivequiz = new stdClass();
        $adaptivequiz->id = 220;

        $userid = 1;

        $attempt = attempt::create($adaptivequiz, $userid);

        $result = $attempt->get_question_mark($mockquba, 1);
        $this->assertEquals(1.0, $result);
    }

    /**
     * This function tests results returned from get_question_mark()
     */
    public function test_get_question_mark_with_quba_return_non_float() {
        $this->resetAfterTest();

        // Test quba returning a non float value.
        $mockqubatwo = $this->createMock('question_usage_by_activity');

        $mockqubatwo->expects($this->once())
            ->method('get_question_mark')
            ->will($this->returnValue(0));

        $adaptivequiz = new stdClass();
        $adaptivequiz->id = 220;

        $userid = 1;

        $attempt = attempt::create($adaptivequiz, $userid);

        $result = $attempt->get_question_mark($mockqubatwo, 1);
        $this->assertEquals(0, $result);
    }

    /**
     * This function tests results returned from get_question_mark().
     */
    public function test_get_question_mark_with_quba_return_non_null() {
        $this->resetAfterTest(true);

        // Test quba returning null.
        $mockqubathree = $this->createMock('question_usage_by_activity');

        $mockqubathree->expects($this->once())
            ->method('get_question_mark')
            ->will($this->returnValue(null));

        $adaptivequiz = new stdClass();
        $adaptivequiz->id = 220;

        $userid = 1;

        $attempt = attempt::create($adaptivequiz, $userid);

        $result = $attempt->get_question_mark($mockqubathree, 1);
        $this->assertEquals(0, $result);
    }

    public function test_it_can_check_if_a_user_has_a_completed_attempt_on_a_quiz(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $adaptivequizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');
        $adaptivequiz = $adaptivequizgenerator->create_instance(['course' => $course->id]);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id, $course->id);

        $attempt = attempt::create($adaptivequiz, $user->id);
        $attempt->complete(context_module::instance($cm->id), '_some_reason_to_stop_the_attempt', time());

        $this->assertTrue(attempt::user_has_completed_on_quiz($adaptivequiz->id, $user->id));
    }

    public function test_it_finds_an_in_progress_attempt_for_a_user(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $adaptivequizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');
        $adaptivequiz = $adaptivequizgenerator->create_instance(['course' => $course->id]);

        // No attempt exists for the user at the moment.
        $this->assertNull(attempt::find_in_progress_for_user($adaptivequiz, $user->id));

        $attempt = attempt::create($adaptivequiz, $user->id);

        $this->assertEquals($attempt, attempt::find_in_progress_for_user($adaptivequiz, $user->id));
    }

    public function test_it_gets_an_attempt_by_its_id(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $adaptivequizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');
        $adaptivequiz = $adaptivequizgenerator->create_instance(['course' => $course->id]);

        $attempt = attempt::create($adaptivequiz, $user->id);

        self::assertEquals($attempt, attempt::get_by_id($attempt->read_attempt_data()->id, $adaptivequiz));
    }

    public function test_it_throws_an_exception_when_getting_an_attempt_with_unknown_id(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $adaptivequizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');
        $adaptivequiz = $adaptivequizgenerator->create_instance(['course' => $course->id]);

        self::expectException(dml_missing_record_exception::class);
        attempt::get_by_id(458, $adaptivequiz);
    }

    public function test_it_creates_an_attempt(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $adaptivequizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');
        $adaptivequiz = $adaptivequizgenerator->create_instance(['course' => $course->id]);

        attempt::create($adaptivequiz, $user->id);

        // Check it inserted a record for the attempt.
        $expectedfields = new stdClass();
        $expectedfields->instance = $adaptivequiz->id;
        $expectedfields->userid = $user->id;
        $expectedfields->uniqueid = '0';
        $expectedfields->attemptstate = attempt_state::IN_PROGRESS;
        $expectedfields->questionsattempted = '0';

        $attemptfields = $DB->get_record('adaptivequiz_attempt',
            ['instance' => $adaptivequiz->id, 'userid' => $user->id, 'attemptstate' => attempt_state::IN_PROGRESS],
            'id, instance, userid, uniqueid, attemptstate, questionsattempted', MUST_EXIST
        );
        $expectedfields->id = $attemptfields->id;

        $this->assertEquals($expectedfields, $attemptfields);
    }

    public function test_it_updates_an_attempt_after_question_is_answered(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $adaptivequizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');
        $adaptivequiz = $adaptivequizgenerator->create_instance(['course' => $course->id]);

        $attempt = attempt::create($adaptivequiz, $user->id);

        $attempt->update_after_question_answered(1658759115);

        $expectedfields = new stdClass();
        $expectedfields->questionsattempted = '1';
        $expectedfields->timemodified = '1658759115';

        $attemptfields = $DB->get_record('adaptivequiz_attempt',
            ['instance' => $adaptivequiz->id, 'userid' => $user->id, 'attemptstate' => attempt_state::IN_PROGRESS],
            'id, questionsattempted, timemodified', MUST_EXIST
        );

        $attemptid = $attemptfields->id;
        $expectedfields->id = $attemptid;

        $this->assertEquals($expectedfields, $attemptfields);

        $calcresults = cat_calculation_steps_result::from_floats(1.1422792, 0.70711, 1.79982);
        $attempt->update_after_question_answered(1658759315);

        $expectedfields = new stdClass();
        $expectedfields->id = $attemptid;
        $expectedfields->questionsattempted = '2';
        $expectedfields->timemodified = '1658759315';

        $attemptfields = $DB->get_record('adaptivequiz_attempt', ['id' => $attemptid], 'id, questionsattempted, timemodified',
            MUST_EXIST);

        $this->assertEquals($expectedfields, $attemptfields);
    }

    public function test_attempt_can_be_completed(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $adaptivequizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');
        $adaptivequiz = $adaptivequizgenerator->create_instance(['course' => $course->id]);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id, $course->id);

        $attempt = attempt::create($adaptivequiz, $user->id);

        $message = '_some_reason_to_stop_the_attempt';
        $attempt->complete(context_module::instance($cm->id), $message, time());

        $expectedfields = new stdClass();
        $expectedfields->attemptstate = attempt_state::COMPLETED;
        $expectedfields->attemptstopcriteria = $message;

        $attemptfields = $DB->get_record('adaptivequiz_attempt',
            ['instance' => $adaptivequiz->id, 'userid' => $user->id, 'attemptstate' => attempt_state::COMPLETED],
            'id, attemptstate, attemptstopcriteria', MUST_EXIST
        );

        $expectedfields->id = $attemptfields->id;

        $this->assertEquals($expectedfields, $attemptfields);
    }

    public function test_event_is_triggered_on_attempt_completion(): void {
        $this->resetAfterTest();
        $eventsink = $this->redirectEvents();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $adaptivequizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');
        $adaptivequiz = $adaptivequizgenerator->create_instance(['course' => $course->id]);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id, $course->id);

        $context = context_module::instance($cm->id);

        $attempt = attempt::create($adaptivequiz, $user->id);
        $attempt->complete($context, '_some_reason_to_stop_the_attempt', time());

        $events = $eventsink->get_events();

        $attemptcompletedevent = null;
        foreach ($events as $event) {
            if ($event instanceof attempt_completed) {
                $attemptcompletedevent = $event;
                break;
            }
        }

        $this->assertNotNull($attemptcompletedevent,
            sprintf('Failed asserting that event %s was triggered.', attempt_completed::class));
        $this->assertEquals($attempt->read_attempt_data()->id, $attemptcompletedevent->objectid);
        $this->assertEquals($context, $attemptcompletedevent->get_context());
        $this->assertEquals($user->id, $attemptcompletedevent->userid);
    }
}
