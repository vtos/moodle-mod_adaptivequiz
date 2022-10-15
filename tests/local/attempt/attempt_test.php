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
 * @copyright  2013 Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_adaptivequiz\local\attempt;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/mod/adaptivequiz/locallib.php');

use advanced_testcase;
use coding_exception;
use context_module;
use stdClass;

/**
 * @group mod_adaptivequiz
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
     * This function test that one value is returned or an empty array is returned
     */
    public function test_return_random_question() {
        $this->resetAfterTest(true);

        $dummy = new stdClass();
        $dummy->id = 220;
        $dummy->minimumquestions = 21;
        $userid = 2;

        $adaptiveattempt = new attempt($dummy, $userid);

        $result = $adaptiveattempt->return_random_question(array());
        $this->assertEquals(0, $result);

        $result = (string) $adaptiveattempt->return_random_question(
            [1 => 'quest 1', 2 => 'quest 2', 3 => 'quest 3', 4 => 'quest 4']
        );
        $this->assertMatchesRegularExpression('/[1-4]/', $result);
    }

    /**
     * This function tests the creation of a question_usage_by_activity object for an attempt.
     */
    public function test_initialize_quba() {
        $this->resetAfterTest();
        $this->setup_generator_data();

        $context = context_module::instance($this->cm->id);

        $adaptiveattempt = attempt::create($this->activityinstance, $this->user->id);
        $quba = $adaptiveattempt->initialize_quba($context);

        $this->assertInstanceOf('question_usage_by_activity', $quba);
    }

    /**
     * This function tests the re-using of the question_usage_by_activity object, due to an incomplete attempt.
     */
    public function test_initialize_quba_with_existing_attempt_uniqueid() {
        global $DB;

        $this->resetAfterTest();
        $this->setup_test_data_xml();

        $cmid = 5;

        $activityinstance = $DB->get_record('adaptivequiz', ['id' => 330]);

        $context = context_module::instance($cmid);

        $adaptiveattempt = attempt::find_in_progress_for_user($activityinstance, 2);
        $quba = $adaptiveattempt->initialize_quba($context);

        $this->assertInstanceOf('question_usage_by_activity', $quba);

        // Check if the uniqueid of the adaptive attempt equals the id of the object loaded by quba class.
        $this->assertEquals(330, $quba->get_id());
    }

    /**
     * This function tests retrieving the last question viewed by the student for a given attempt.
     */
    public function test_find_last_quest_used_by_attempt() {
        global $DB;

        $this->resetAfterTest();
        $this->setup_test_data_xml();

        $cmid = 5;

        $activityinstance = $DB->get_record('adaptivequiz', ['id' => 330]);

        $context = context_module::instance($cmid);

        $adaptiveattempt = attempt::find_in_progress_for_user($activityinstance, 2);
        $quba = $adaptiveattempt->initialize_quba($context);

        $result = $adaptiveattempt->find_last_quest_used_by_attempt($quba);

        $this->assertEquals(2, $result);
    }

    public function test_it_fails_to_find_the_last_question_used_by_attempt_with_an_invalid_argument() {
        $this->resetAfterTest();

        $dummy = new stdClass();

        $adaptiveattempt = new attempt($dummy, 2);

        $this->expectException('coding_exception');
        $adaptiveattempt->find_last_quest_used_by_attempt($dummy);
    }

    /**
     * This function tests retrieving the last question viewed by the student for a given attempt, but using failing data
     */
    public function test_find_last_umarked_question_using_bad_data() {
        $this->resetAfterTest(true);

        $dummy = new stdClass();

        $adaptiveattempt = $this->getMockBuilder(attempt::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Test passing an invalid object instance.
        $result = $adaptiveattempt->find_last_quest_used_by_attempt($dummy);
        $this->assertEquals(0, $result);

        // Test getting an empty slot value.
        $mockquba = $this->getMockBuilder('question_usage_by_activity')
            ->disableOriginalConstructor()
            ->getMock();

        $result = $adaptiveattempt->find_last_quest_used_by_attempt($mockquba);

        $this->assertEquals(0, $result);
    }

    /**
     * This function tests whether the user submitted an answer to the question
     */
    public function test_was_answer_submitted_to_question_with_graded_right() {
        $this->resetAfterTest(true);
        $dummy = new stdClass();
        $adaptiveattempt = new attempt($dummy, 1);

        $mockquba = $this->getMockBuilder('question_usage_by_activity')
            ->disableOriginalConstructor()
            ->getMock();

        $mockstate = $this->createMock('question_state_gradedright', array(), array(), '', false);

        $mockquba->expects($this->once())
            ->method('get_question_state')
            ->with(1)
            ->will($this->returnValue($mockstate));

        $mockquba->expects($this->never())
            ->method('get_id');

        $result = $adaptiveattempt->was_answer_submitted_to_question($mockquba, 1);
        $this->assertTrue($result);
    }

    /**
     * This function tests whether the user submitted an answer to the question
     */
    public function test_was_answer_submitted_to_question_with_graded_wrong() {
        $this->resetAfterTest(true);
        $dummy = new stdClass();
        $adaptiveattempt = new attempt($dummy, 1);

        $mockquba = $this->getMockBuilder('question_usage_by_activity')
            ->disableOriginalConstructor()
            ->getMock();

        $mockstate = $this->createMock('question_state_gradedwrong', array(), array(), '', false);

        $mockquba->expects($this->once())
            ->method('get_question_state')
            ->with(1)
            ->will($this->returnValue($mockstate));

        $mockquba->expects($this->never())
            ->method('get_id');

        $result = $adaptiveattempt->was_answer_submitted_to_question($mockquba, 1);
        $this->assertTrue($result);
    }

    /**
     * This function tests whether the user submitted an answer to the question
     */
    public function test_was_answer_submitted_to_question_with_graded_partial() {
        $this->resetAfterTest(true);
        $dummy = new stdClass();
        $adaptiveattempt = new attempt($dummy, 1);

        $mockquba = $this->getMockBuilder('question_usage_by_activity')
            ->disableOriginalConstructor()
            ->getMock();

        $mockstate = $this->createMock('question_state_gradedpartial', array(), array(), '', false);

        $mockquba->expects($this->once())
            ->method('get_question_state')
            ->with(1)
            ->will($this->returnValue($mockstate));

        $mockquba->expects($this->never())
            ->method('get_id');

        $result = $adaptiveattempt->was_answer_submitted_to_question($mockquba, 1);
        $this->assertTrue($result);
    }

    /**
     * This function tests whether the user submitted an answer to the question
     */
    public function test_was_answer_submitted_to_question_with_graded_gaveup() {
        $this->resetAfterTest(true);
        $dummy = new stdClass();
        $adaptiveattempt = new attempt($dummy, 1);

        $mockquba = $this->getMockBuilder('question_usage_by_activity')
            ->disableOriginalConstructor()
            ->getMock();

        $mockstate = $this->createMock('question_state_gaveup', array(), array(), '', false);

        $mockquba->expects($this->once())
            ->method('get_question_state')
            ->with(1)
            ->will($this->returnValue($mockstate));

        $mockquba->expects($this->never())
            ->method('get_id');

        $result = $adaptiveattempt->was_answer_submitted_to_question($mockquba, 1);
        $this->assertTrue($result);
    }

    /**
     * This function tests whether the user submitted an answer to the question
     */
    public function test_was_answer_submitted_to_question_with_graded_todo() {
        $this->resetAfterTest(true);
        $dummy = new stdClass();
        $adaptiveattempt = new attempt($dummy, 1);

        $mockquba = $this->getMockBuilder('question_usage_by_activity')
            ->disableOriginalConstructor()
            ->getMock();

        $mockstate = $this->createMock('question_state_todo', array(), array(), '', false);

        $mockquba->expects($this->once())
            ->method('get_question_state')
            ->with(1)
            ->will($this->returnValue($mockstate));

        $mockquba->expects($this->once())
            ->method('get_id')
            ->will($this->returnValue(1));

        $result = $adaptiveattempt->was_answer_submitted_to_question($mockquba, 1);
        $this->assertFalse($result);
    }

    /**
     * This function tests the accessor method for $slot
     */
    public function test_set_get_question_slot_number() {
        $this->resetAfterTest(true);

        $dummy = new stdClass();
        $userid = 1;
        $adaptiveattempt = new attempt($dummy, $userid);

        $adaptiveattempt->set_question_slot_number(2);
        $result = $adaptiveattempt->get_question_slot_number();

        $this->assertEquals(2, $result);
    }

    /**
     * This function tests the accessor method for $slot
     */
    public function test_set_get_question_level() {
        $this->resetAfterTest(true);

        $dummy = new stdClass();
        $userid = 1;
        $adaptiveattempt = new attempt($dummy, $userid);

        $adaptiveattempt->set_level(2);
        $result = $adaptiveattempt->get_level();

        $this->assertEquals(2, $result);
    }

    /**
     * This function tests results returned from get_question_mark()
     */
    public function test_get_question_mark_with_quba_return_float() {
        $this->resetAfterTest(true);

        // Test quba returning a mark of 1.0.
        $mockquba = $this->createMock('question_usage_by_activity', array(), array(), '', false);

        $mockquba->expects($this->once())
            ->method('get_question_mark')
            ->will($this->returnValue(1.0));

        $dummy = new stdClass();
        $userid = 1;
        $adaptiveattempt = new attempt($dummy, $userid);
        $result = $adaptiveattempt->get_question_mark($mockquba, 1);
        $this->assertEquals(1.0, $result);
    }

    /**
     * This function tests results returned from get_question_mark()
     */
    public function test_get_question_mark_with_quba_return_non_float() {
        $this->resetAfterTest(true);

        // Test quba returning a non float value.
        $mockqubatwo = $this->createMock('question_usage_by_activity', array(), array(), '', false);

        $mockqubatwo->expects($this->once())
            ->method('get_question_mark')
            ->will($this->returnValue(0));

        $dummy = new stdClass();
        $userid = 1;
        $adaptiveattempttwo = new attempt($dummy, $userid);
        $result = $adaptiveattempttwo->get_question_mark($mockqubatwo, 1);
        $this->assertEquals(0, $result);
    }

    /**
     * This function tests results returned from get_question_mark()
     */
    public function test_get_question_mark_with_quba_return_non_null() {
        $this->resetAfterTest(true);

        // Test quba returning null.
        $mockqubathree = $this->createMock('question_usage_by_activity', array(), array(), '', false);

        $mockqubathree->expects($this->once())
            ->method('get_question_mark')
            ->will($this->returnValue(null));

        $dummy = new stdClass();
        $userid = 1;
        $adaptiveattemptthree = new attempt($dummy, $userid);
        $result = $adaptiveattemptthree->get_question_mark($mockqubathree, 1);
        $this->assertEquals(0, $result);
    }

    /**
     * This function tests what happens when the maximum number of questions have been answered.
     */
    public function test_start_attempt_max_num_of_quest_answered() {
        global $DB;

        $this->resetAfterTest();
        $this->setup_test_data_xml();

        $adaptivequizid = 330;
        $userid = 3;
        $cmid = 5;

        $adaptivequiz = $DB->get_record('adaptivequiz', ['id' => $adaptivequizid]);
        $attempt = attempt::create($adaptivequiz, $userid);

        $context = context_module::instance($cmid);
        $this->assertFalse($attempt->start_attempt($context, $attempt->number_of_questions_attempted()));
    }

    /**
     * This function tests what happens when a question slot number is not found, but the number of submitted answers is greater
     * than zero.
     */
    public function test_start_attempt_quest_slot_empty_quest_submit_greater_than_one() {
        $this->markTestSkipped('skipped unless managing quba is extracted from the attempt class');

        $dummyadaptivequiz = new stdClass();
        $dummyadaptivequiz->lowestlevel = 1;
        $dummyadaptivequiz->highestlevel = 100;

        $cmid = 5;

        $mockattemptthree = $this
            ->getMockBuilder(attempt::class)
            ->onlyMethods(['record', 'initialize_quba', 'find_last_quest_used_by_attempt', 'level_in_bounds']
            )
            ->setConstructorArgs(
                [$dummyadaptivequiz, 1]
            )
            ->getMock();

        $dummyattempt = new stdClass();
        $dummyattempt->questionsattempted = 1;

        $mockattemptthree->expects($this->once())
            ->method('record')
            ->will($this->returnValue($dummyattempt));
        $mockattemptthree->expects($this->once())
            ->method('level_in_bounds')
            ->will($this->returnValue(true));
        $mockattemptthree->expects($this->once())
            ->method('initialize_quba');
        $mockattemptthree->expects($this->once())
            ->method('find_last_quest_used_by_attempt')
            ->will($this->returnValue(0));

        $context = context_module::instance($cmid);

        $this->assertFalse($mockattemptthree->start_attempt($context, $dummyattempt->questionsattempted));
    }

    /**
     * This function tests the return values for level_in_bounds()
     */
    public function test_level_in_bounds() {
        $this->resetAfterTest(true);

        $dummy = new stdClass();
        $dummy->lowestlevel = 5;
        $dummy->highestlevel = 10;

        $userid = 1;
        $adaptiveattempt = new attempt($dummy, $userid);

        $adaptivequiz = $adaptiveattempt->get_adaptivequiz();

        $result = $adaptiveattempt->level_in_bounds(6, $adaptivequiz);

        $this->assertTrue($result);
    }

    /**
     * This function tests retrieving of question ids
     */
    public function test_get_all_questions_in_attempt() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $dummy = new stdClass();
        $userid = 1;
        $adaptiveattempt = new attempt($dummy, $userid);
        $questids = $adaptiveattempt->get_all_questions_in_attempt(330);

        $this->assertEquals(2, count($questids));
        $this->assertEquals(array('1' => 1, '2' => 2), $questids);
    }

    public function test_it_can_check_if_a_user_has_a_completed_attempt_on_a_quiz(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setup_test_data_xml();

        $uniqueid = 330;
        $adaptivequizid = 330;
        $cmid = 5;
        $userid = 2;

        $adaptivequiz = $DB->get_record('adaptivequiz', ['id' => $adaptivequizid]);
        $context = context_module::instance($cmid);

        adaptivequiz_complete_attempt($uniqueid, $adaptivequiz, $context, $userid, '', '');

        $this->assertTrue(attempt::user_has_completed_on_quiz($adaptivequizid, $userid));
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
        $expectedfields->standarderror = '999.00000';

        $attemptfields = $DB->get_record('adaptivequiz_attempt',
            ['instance' => $adaptivequiz->id, 'userid' => $user->id, 'attemptstate' => attempt_state::IN_PROGRESS],
            'id, instance, userid, uniqueid, attemptstate, questionsattempted, standarderror', MUST_EXIST
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

        $calcresults = cat_calculation_steps_result::from_floats(-10.7435883, 0.73030, -0.83212);
        $attempt->update_after_question_answered($calcresults, 1658759115);

        $expectedfields = new stdClass();
        $expectedfields->difficultysum = '-10.7435883';
        $expectedfields->questionsattempted = '1';
        $expectedfields->standarderror = '0.73030';
        $expectedfields->measure = '-0.83212';
        $expectedfields->timemodified = '1658759115';

        $attemptfields = $DB->get_record('adaptivequiz_attempt',
            ['instance' => $adaptivequiz->id, 'userid' => $user->id, 'attemptstate' => attempt_state::IN_PROGRESS],
            'id, questionsattempted, difficultysum, standarderror, measure, timemodified', MUST_EXIST
        );

        $attemptid = $attemptfields->id;
        $expectedfields->id = $attemptid;

        $this->assertEquals($expectedfields, $attemptfields);

        $calcresults = cat_calculation_steps_result::from_floats(1.1422792, 0.70711, 1.79982);
        $attempt->update_after_question_answered($calcresults, 1658759315);

        $expectedfields = new stdClass();
        $expectedfields->id = $attemptid;
        $expectedfields->difficultysum = '-9.6013091';
        $expectedfields->questionsattempted = '2';
        $expectedfields->standarderror = '0.70711';
        $expectedfields->measure = '1.79982';
        $expectedfields->timemodified = '1658759315';

        $attemptfields = $DB->get_record('adaptivequiz_attempt', ['id' => $attemptid],
            'id, questionsattempted, difficultysum, standarderror, measure, timemodified', MUST_EXIST
        );

        $this->assertEquals($expectedfields, $attemptfields);
    }

    public function test_it_fails_to_update_an_attempt_when_question_is_answered_if_attempt_record_is_not_set(): void {
        $attempt = new attempt(new stdClass(), 333);

        $this->expectException(coding_exception::class);
        $attempt->update_after_question_answered(cat_calculation_steps_result::from_floats(0, 0, 0), time());
    }
}
