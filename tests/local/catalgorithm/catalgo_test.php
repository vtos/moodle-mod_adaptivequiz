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
 * PHPUnit tests for catalgo class.
 *
 * @copyright  2013 Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_adaptivequiz\local\catalgorithm;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/adaptivequiz/locallib.php');

use advanced_testcase;
use coding_exception;
use mod_adaptivequiz\local\activityinstance\questions_difficulty_range;
use question_usage_by_activity;
use stdClass;

/**
 * @group mod_adaptivequiz
 * @covers \mod_adaptivequiz\local\catalgo
 */
class catalgo_test extends advanced_testcase {
    /**
     * This function loads data into the PHPUnit tables for testing
     *
     * @throws coding_exception
     */
    protected function setup_test_data_xml() {
        $this->dataset_from_files(
            [__DIR__.'/../fixtures/mod_adaptivequiz_catalgo.xml']
        )->to_database();
    }

    /**
     * This function tests instantiating the catalgo class without an instance of question_usage_by_activity.
     */
    public function test_init_catalgo_no_quba_object_instance() {
        $this->resetAfterTest();

        $this->expectException('coding_exception');
        new catalgo(new stdClass(), true, 1);
    }

    /**
     * This function tests instantiating the catalgo class with a non-positive integer and throwing an exception.
     */
    public function test_init_catalgo_negative_int_throw_except() {
        $this->resetAfterTest();

        $mockquba = $this->createMock('question_usage_by_activity');

        $this->expectException('coding_exception');
        new catalgo($mockquba);
    }

    /**
     * This function tests instantiating the catalgo class without setting the level argument.
     */
    public function test_init_catalgo_no_level_throw_except() {
        $this->resetAfterTest();

        $mockquba = $this->createMock('question_usage_by_activity');

        $this->expectException('coding_exception');
        new catalgo($mockquba, true);
    }

    /**
     * This function tests was_answer_submitted_to_question() returning a false instead of a true
     */
    public function test_quest_was_marked_correct_no_submit_prev_quest_fail() {
        $this->resetAfterTest(true);

        $mockcatalgo = $this->createPartialMock(catalgo::class,
            ['find_last_quest_used_by_attempt', 'was_answer_submitted_to_question', 'get_question_mark']);
        $mockcatalgo->expects($this->once())
            ->method('find_last_quest_used_by_attempt')
            ->willReturn(99);
        $mockcatalgo->expects($this->once())
            ->method('was_answer_submitted_to_question')
            ->with(99)
            ->willReturn(false);
        $mockcatalgo->expects($this->never())
            ->method('get_question_mark');

        $result = $mockcatalgo->question_was_marked_correct();
        $this->assertFalse($result);
    }

    /**
     * This function tests was_answer_submitted_to_question() returning a 0 instead of a slot number
     */
    public function test_quest_was_marked_correct_zero_slot_number_fail() {
        $this->resetAfterTest(true);

        $mockcatalgo = $this->createPartialMock(catalgo::class,
            ['find_last_quest_used_by_attempt', 'get_question_mark']);
        $mockcatalgo->expects($this->once())
            ->method('find_last_quest_used_by_attempt')
            ->willReturn(0);
        $mockcatalgo->expects($this->never())
            ->method('get_question_mark');

        $result = $mockcatalgo->question_was_marked_correct();
        $this->assertNull($result);
    }

    /**
     * This function tests get_question_mark() returning null
     */
    public function test_quest_was_marked_correct_mark_is_null_fail() {
        $this->resetAfterTest(true);

        $mockcatalgo = $this->createPartialMock(catalgo::class,
            ['find_last_quest_used_by_attempt', 'was_answer_submitted_to_question', 'get_question_mark']);
        $mockcatalgo->expects($this->once())
            ->method('find_last_quest_used_by_attempt')
            ->willReturn(99);
        $mockcatalgo->expects($this->once())
            ->method('was_answer_submitted_to_question')
            ->with(99)
            ->willReturn(true);
        $mockcatalgo->expects($this->once())
            ->method('get_question_mark')
            ->withAnyParameters()
            ->willReturn(null);

        $result = $mockcatalgo->question_was_marked_correct();
        $this->assertNull($result);
    }

    /**
     * This function tests get_question_mark() returning a mark of zero
     */
    public function test_quest_was_marked_correct_mark_zero() {
        $this->resetAfterTest(true);

        $mockcatalgo = $this->createPartialMock(catalgo::class,
            ['find_last_quest_used_by_attempt', 'was_answer_submitted_to_question', 'get_question_mark']);
        $mockcatalgo->expects($this->once())
            ->method('find_last_quest_used_by_attempt')
            ->willReturn(99);
        $mockcatalgo->expects($this->once())
            ->method('was_answer_submitted_to_question')
            ->with(99)
            ->willReturn(true);
        $mockcatalgo->expects($this->once())
            ->method('get_question_mark')
            ->withAnyParameters()
            ->willReturn(0.00);

        $result = $mockcatalgo->question_was_marked_correct();
        $this->assertFalse($result);
    }

    /**
     * This function tests get_question_mark() returning a mark of greater than zero
     */
    public function test_quest_was_marked_correct_mark_non_zero() {
        $this->resetAfterTest(true);

        $mockcatalgo = $this->createPartialMock(catalgo::class,
            ['find_last_quest_used_by_attempt', 'was_answer_submitted_to_question', 'get_question_mark']);
        $mockcatalgo->expects($this->once())
            ->method('find_last_quest_used_by_attempt')
            ->willReturn(99);
        $mockcatalgo->expects($this->once())
            ->method('was_answer_submitted_to_question')
            ->with(99)
            ->willReturn(true);
        $mockcatalgo->expects($this->once())
            ->method('get_question_mark')
            ->withAnyParameters()
            ->willReturn(0.10);

        $result = $mockcatalgo->question_was_marked_correct();
        $this->assertTrue($result);
    }

    public function test_it_can_define_current_difficulty_level(): void {
        $adaptivequiz = new stdClass();
        $adaptivequiz->lowestlevel = 0;
        $adaptivequiz->highestlevel = 100;

        // In case the quba object cannot get any slots.
        $quba = $this->createPartialMock(question_usage_by_activity::class, ['get_slots']);
        $quba->expects($this->once())
            ->method('get_slots')
            ->willReturn([]);

        $catalgo = new catalgo($quba, true, 1);
        $difficultylevel = $catalgo->get_current_diff_level(
            $quba,
            1,
            questions_difficulty_range::from_activity_instance($adaptivequiz)
        );

        $this->assertEquals(0, $difficultylevel);

        // In case compute_next_difficulty() method does the job.
        $quba = $this->getMockBuilder(question_usage_by_activity::class)
            ->disableOriginalConstructor()
            ->getMock();

        $quba->expects($this->once())
            ->method('get_slots')
            ->willReturn([1, 2, 3, 4, 5]);

        $questionstate = $this->createMock('question_state_gradedright');
        $quba->expects($this->once())
            ->method('get_question_state')
            ->with(5)
            ->will($this->returnValue($questionstate));

        $quba->expects($this->exactly(5))
            ->method('get_question_mark')
            ->will($this->returnValue(1.0));

        $catalgo = $this->createPartialMock(catalgo::class, ['compute_next_difficulty']);
        $catalgo->expects($this->exactly(5))
            ->method('compute_next_difficulty')
            ->willReturn(5);

        $difficultylevel = $catalgo->get_current_diff_level(
            $quba,
            1,
            questions_difficulty_range::from_activity_instance($adaptivequiz)
        );

        $this->assertEquals(5, $difficultylevel);
    }

    /**
     * This function tests compute_next_difficulty().
     * Setting 0 as the lowest level and 100 as the highest level.
     */
    public function test_compute_next_difficulty_zero_min_one_hundred_max() {
        $this->resetAfterTest();

        $mockquba = $this->createMock(question_usage_by_activity::class);

        $catalgo = new catalgo($mockquba, true, 1);

        $adaptivequiz = new stdClass();
        $adaptivequiz->lowestlevel = 0;
        $adaptivequiz->highestlevel = 100;

        $questionsdifficultyrange = questions_difficulty_range::from_activity_instance($adaptivequiz);

        // Test the next difficulty level shown to the student if the student got a level 30 question wrong,
        // having attempted 1 question.
        $result = $catalgo->compute_next_difficulty(30, 1, false, $questionsdifficultyrange);
        $this->assertEquals(5, $result);

        // Test the next difficulty level shown to the student if the student got a level 30 question right,
        // having attempted 1 question.
        $result = $catalgo->compute_next_difficulty(30, 1, true, $questionsdifficultyrange);
        $this->assertEquals(76, $result);

        // Test the next difficulty level shown to the student if the student got a level 80 question wrong,
        // having attempted 2 questions.
        $result = $catalgo->compute_next_difficulty(80, 2, false, $questionsdifficultyrange);
        $this->assertEquals(60, $result);

        // Test the next difficulty level shown to the student if the student got a level 80 question right,
        // having attempted 2 question.
        $result = $catalgo->compute_next_difficulty(80, 2, true, $questionsdifficultyrange);
        $this->assertEquals(92, $result);
    }

    /**
     * This function tests compute_next_difficulty().
     * Setting 1 as the lowest level and 10 as the highest level.
     */
    public function test_compute_next_difficulty_one_min_ten_max_compute_infinity() {
        $this->resetAfterTest();

        $mockquba = $this->createMock(question_usage_by_activity::class);

        $catalgo = new catalgo($mockquba, true, 1);

        $adaptivequiz = new stdClass();
        $adaptivequiz->lowestlevel = 1;
        $adaptivequiz->highestlevel = 10;

        $questionsdifficultyrange = questions_difficulty_range::from_activity_instance($adaptivequiz);

        $result = $catalgo->compute_next_difficulty(1, 2, false, $questionsdifficultyrange);
        $this->assertEquals(1, $result);

        $result = $catalgo->compute_next_difficulty(10, 2, true, $questionsdifficultyrange);
        $this->assertEquals(10, $result);
    }

    /**
     * This function tests results returned from get_question_mark().
     */
    public function test_get_question_mark() {
        $this->resetAfterTest();

        // Test quba returning a mark of 1.0.
        $mockquba = $this->createMock(question_usage_by_activity::class);

        $mockquba->expects($this->once())
            ->method('get_question_mark')
            ->will($this->returnValue(1.0));

        $catalgo = new catalgo($mockquba, true, 1);
        $result = $catalgo->get_question_mark($mockquba, 1);
        $this->assertEquals(1.0, $result);

        // Test quba returning a non float value.
        $mockqubatwo = $this->createMock('question_usage_by_activity');

        $mockqubatwo->expects($this->once())
            ->method('get_question_mark')
            ->will($this->returnValue(1));

        $catalgo = new catalgo($mockqubatwo, true, 1);
        $result = $catalgo->get_question_mark($mockqubatwo, 1);
        $this->assertNull($result);
    }

    /**
     * This function tests the return data from compute_right_answers().
     */
    public function test_compute_right_answers() {
        $this->resetAfterTest();

        // Test use case where user got all 5 question correct.
        $mockquba = $this->createPartialMock('question_usage_by_activity', ['get_slots', 'get_question_mark']);
        $mockquba->expects($this->exactly(5))
            ->method('get_question_mark')
            ->willReturn(1.0);
        $mockquba->expects($this->once())
            ->method('get_slots')
            ->willReturn([1, 2, 3, 4, 5]);

        $catalgo = new catalgo($mockquba, true, 1);

        $result = $catalgo->compute_right_answers($mockquba);
        $this->assertEquals(5, $result);
    }

    /**
     * This function tests the return data from compute_right_answers().
     */
    public function test_compute_right_answers_none_correct() {
        $this->resetAfterTest();

        // Test use case where user got all 5 question incorrect.
        $mockquba = $this->createMock('question_usage_by_activity');

        $mockquba->expects($this->exactly(5))
            ->method('get_question_mark')
            ->will($this->returnValue(0));

        $mockquba->expects($this->once())
            ->method('get_slots')
            ->will($this->returnValue(array(1, 2, 3, 4, 5)));

        $catalgo = new catalgo($mockquba, true, 1);
        $result = $catalgo->compute_right_answers($mockquba);
        $this->assertEquals(0, $result);
    }

    /**
     * This function tests the return data from compute_right_answers() when get_question_mark() returns a null.
     */
    public function test_compute_right_answers_null_return_from_get_question_mark() {
        $this->resetAfterTest();

        $mockquba = $this->createMock('question_usage_by_activity');

        $mockquba->expects($this->once())
            ->method('get_question_mark')
            ->will($this->returnValue(null));

        $mockquba->expects($this->once())
            ->method('get_slots')
            ->will($this->returnValue(array(1)));

        $catalgo = new catalgo($mockquba, true, 1);
        $result = $catalgo->compute_right_answers($mockquba);
        $this->assertEquals(0, $result);
    }

    /**
     * This function tests the return data from compute_right_answers().
     */
    public function test_compute_wrong_answers() {
        $this->resetAfterTest();

        // Test use case where user got all 5 question incorrect.
        $mockquba = $this->createMock('question_usage_by_activity');

        $mockquba->expects($this->exactly(5))
            ->method('get_question_mark')
            ->will($this->returnValue(0));

        $mockquba->expects($this->once())
            ->method('get_slots')
            ->will($this->returnValue(array(1, 2, 3, 4, 5)));

        $catalgo = new catalgo($mockquba, true, 1);
        $result = $catalgo->compute_wrong_answers($mockquba);
        $this->assertEquals(5, $result);
    }

    /**
     * This function tests the return data from compute_right_answers().
     */
    public function test_compute_wrong_answers_all_correct() {
        $this->resetAfterTest();

        // Test use case where user got all 5 question correct.
        $mockquba = $this->createMock('question_usage_by_activity');

        $mockquba->expects($this->exactly(5))
            ->method('get_question_mark')
            ->will($this->returnValue(1.0));

        $mockquba->expects($this->once())
            ->method('get_slots')
            ->will($this->returnValue(array(1, 2, 3, 4, 5)));

        $catalgo = new catalgo($mockquba, true, 1);
        $result = $catalgo->compute_wrong_answers($mockquba);
        $this->assertEquals(0, $result);
    }

    /**
     * This function tests the return data from compute_wrong_answers() when get_question_mark() returns a null.
     */
    public function test_compute_wrong_answers_null_return_from_get_question_mark() {
        $this->resetAfterTest();

        $mockquba = $this->createMock('question_usage_by_activity');

        $mockquba->expects($this->once())
            ->method('get_question_mark')
            ->will($this->returnValue(null));

        $mockquba->expects($this->once())
            ->method('get_slots')
            ->will($this->returnValue(array(1)));

        $catalgo = new catalgo($mockquba, true, 1);
        $result = $catalgo->compute_wrong_answers($mockquba);
        $this->assertEquals(1, $result);
    }

    /**
     * This function tests the return data from estimate_measure().
     */
    public function test_estimate_measure() {
        $this->resetAfterTest();

        $mockquba = $this->createMock('question_usage_by_activity', array(), array(), '', false);

        // Test an attempt with the following details:
        // sum of difficulty - 20, number of questions attempted - 10, number of correct answers - 7,
        // number of incorrect answers - 3.
        $catalgo = new catalgo($mockquba, true, 1);
        $result = $catalgo->estimate_measure(20, 10, 7, 3);
        $this->assertEquals(2.8473, $result);
    }

    /**
     * This function tests the return data from estimate_standard_error().
     */
    public function test_estimate_standard_error() {
        $this->resetAfterTest();

        $mockquba = $this->createMock('question_usage_by_activity', array(), array(), '', false);

        // Test an attempt with the following details;
        // sum of questions attempted - 10, number of correct answers - 7, number of incorrect answers - 3.
        $catalgo = new catalgo($mockquba, true, 1);
        $result = $catalgo->estimate_standard_error(10, 7, 3);
        $this->assertEquals(0.69007, $result);
    }

    /**
     * This function tests the return data from perform_calculation_steps(), where question_was_marked_correct() returns null.
     */
    public function test_perform_calc_steps_marked_correct_return_null_fail() {
        $this->resetAfterTest();

        $mockcatalgo = $this->createPartialMock(catalgo::class, ['question_was_marked_correct', 'compute_next_difficulty',
            'compute_right_answers', 'compute_wrong_answers', 'estimate_measure', 'estimate_standard_error',
            'standard_error_within_parameters']);

        $adaptivequiz = new stdClass();
        $adaptivequiz->lowestlevel = 1;
        $adaptivequiz->highestlevel = 10;
        $adaptivequiz->standarderror = 5;

        $mockcatalgo->expects($this->once())
            ->method('question_was_marked_correct')
            ->willReturn(null); // Questions marked correctly returns null.
        $mockcatalgo->expects($this->never())
            ->method('compute_next_difficulty');
        $mockcatalgo->expects($this->never())
            ->method('compute_right_answers');
        $mockcatalgo->expects($this->never())
            ->method('compute_wrong_answers');
        $mockcatalgo->expects($this->never())
            ->method('estimate_measure');
        $mockcatalgo->expects($this->never())
            ->method('estimate_standard_error');
        $mockcatalgo->expects($this->never())
            ->method('standard_error_within_parameters');

        $result = $mockcatalgo->perform_calculation_steps(
            20,
            10,
            questions_difficulty_range::from_activity_instance($adaptivequiz),
            $adaptivequiz->standarderror
        );

        $this->assertEquals(0, $result);
    }

    /**
     * This function tests the return data from perform_calculation_steps(), where compute_right_answers() returns 0,
     * but the next difficulty number is returned.
     */
    public function test_perform_calc_steps_right_ans_ret_zero_but_return_non_zero() {
        $this->resetAfterTest(true);

        $mockcatalgo = $this->createPartialMock(catalgo::class, ['question_was_marked_correct', 'compute_next_difficulty',
            'compute_right_answers', 'compute_wrong_answers']);

        $adaptivequiz = new stdClass();
        $adaptivequiz->lowestlevel = 1;
        $adaptivequiz->highestlevel = 10;
        $adaptivequiz->standarderror = 1.45095;

        $mockcatalgo->expects($this->once())
            ->method('question_was_marked_correct')
            ->willReturn(true);
        $mockcatalgo->expects($this->once())
            ->method('compute_next_difficulty')
            ->willReturn(30);
        $mockcatalgo->expects($this->once())
            ->method('compute_right_answers')
            ->willReturn(0); // Right answers is set to zero.
        $mockcatalgo->expects($this->once())
            ->method('compute_wrong_answers')
            ->willReturn(10); // Wrong answers is set to 10.

        $result = $mockcatalgo->perform_calculation_steps(
            20,
            10,
            questions_difficulty_range::from_activity_instance($adaptivequiz),
            $adaptivequiz->standarderror
        );

        $this->assertNotEquals(0, $result);
    }

    /**
     * This function tests the return data from perform_calculation_steps(), where compute_wrong_answers() returns 0,
     * but the next difficulty number is returned.
     */
    public function test_perform_calc_steps_wrong_ans_return_zero_but_return_non_zero() {
        $this->resetAfterTest(true);

        $mockcatalgo = $this->createPartialMock(catalgo::class, ['question_was_marked_correct', 'compute_next_difficulty',
            'compute_right_answers', 'compute_wrong_answers']);

        $adaptivequiz = new stdClass();
        $adaptivequiz->lowestlevel = 1;
        $adaptivequiz->highestlevel = 10;
        $adaptivequiz->standarderror = 1.45095;

        $mockcatalgo->expects($this->any())
            ->method('question_was_marked_correct')
            ->willReturn(true);
        $mockcatalgo->expects($this->once())
            ->method('compute_next_difficulty')
            ->willReturn(30);
        $mockcatalgo->expects($this->once())
            ->method('compute_right_answers')
            ->willReturn(10); // Right answers is set to 10.
        $mockcatalgo->expects($this->once())
            ->method('compute_wrong_answers')
            ->willReturn(0); // Wrong answers is set to zero.

        $result = $mockcatalgo->perform_calculation_steps(
            20,
            10,
            questions_difficulty_range::from_activity_instance($adaptivequiz),
            $adaptivequiz->standarderror
        );

        $this->assertNotEquals(0, $result);
    }

    /**
     * This function tests the return data from perform_calculation_steps(), where questions attempted is set to 0.
     */
    public function test_perform_calc_steps_quest_attempted_return_zero_fail() {
        $this->resetAfterTest();

        $mockcatalgo = $this->createPartialMock(catalgo::class, ['question_was_marked_correct', 'compute_next_difficulty',
            'compute_right_answers', 'compute_wrong_answers', 'estimate_measure', 'estimate_standard_error',
            'standard_error_within_parameters']);

        $adaptivequiz = new stdClass();
        $adaptivequiz->lowestlevel = 1;
        $adaptivequiz->highestlevel = 10;
        $adaptivequiz->standarderror = 5;

        $mockcatalgo->expects($this->once())
            ->method('question_was_marked_correct')
            ->willReturn(true);
        $mockcatalgo->expects($this->once())
            ->method('compute_next_difficulty')
            ->willReturn(30);
        $mockcatalgo->expects($this->once())
            ->method('compute_right_answers')
            ->willReturn(1);
        $mockcatalgo->expects($this->once())
            ->method('compute_wrong_answers')
            ->willReturn(1);
        $mockcatalgo->expects($this->never())
            ->method('estimate_measure');
        $mockcatalgo->expects($this->never())
            ->method('estimate_standard_error');
        $mockcatalgo->expects($this->never())
            ->method('standard_error_within_parameters');

        $result = $mockcatalgo->perform_calculation_steps(
            20,
            10,
            questions_difficulty_range::from_activity_instance($adaptivequiz),
            $adaptivequiz->standarderror
        );

        $this->assertEquals(0, $result);
    }

    /**
     * This function tests the return data from perform_calculation_steps(), where the sum of correct and incorrect answers
     * does not equal the sum of questions attempted.
     */
    public function test_perform_calc_steps_sum_corr_and_incorr_not_equl_sum_quest_attempt_fail() {
        $this->resetAfterTest();

        $mockcatalgo = $this->createPartialMock(catalgo::class, ['question_was_marked_correct', 'compute_next_difficulty',
            'compute_right_answers', 'compute_wrong_answers', 'estimate_measure', 'estimate_standard_error',
            'standard_error_within_parameters']);

        $adaptivequiz = new stdClass();
        $adaptivequiz->lowestlevel = 1;
        $adaptivequiz->highestlevel = 10;
        $adaptivequiz->standarderror = 5;

        $mockcatalgo->expects($this->once())
            ->method('question_was_marked_correct')
            ->willReturn(true);
        $mockcatalgo->expects($this->once())
            ->method('compute_next_difficulty')
            ->willReturn(30);
        $mockcatalgo->expects($this->once())
            ->method('compute_right_answers')
            ->willReturn(1); // Sum of right answers.
        $mockcatalgo->expects($this->once())
            ->method('compute_wrong_answers')
            ->willReturn(1); // Sum of wrong answers.
        $mockcatalgo->expects($this->never())
            ->method('estimate_measure');
        $mockcatalgo->expects($this->never())
            ->method('estimate_standard_error');
        $mockcatalgo->expects($this->never())
            ->method('standard_error_within_parameters');

        $result = $mockcatalgo->perform_calculation_steps(
            20,
            10,
            questions_difficulty_range::from_activity_instance($adaptivequiz),
            $adaptivequiz->standarderror
        );

        $this->assertEquals(0, $result);
    }

    /**
     * This function tests the return data from perform_calculation_steps(), where the user answered
     * the last question correctly and the attempt has not met the minimum stopping criteria.
     */
    public function test_perform_calculation_steps_nostop_correct_answer() {
        $this->resetAfterTest();

        $mockquba = $this->createMock(question_usage_by_activity::class);

        // Minimum stopping criteria has not been met.
        $mockcatalgo = $this
            ->getMockBuilder(catalgo::class)
            ->onlyMethods(
                ['question_was_marked_correct', 'compute_next_difficulty', 'compute_right_answers', 'compute_wrong_answers',
                    'estimate_measure', 'estimate_standard_error', 'standard_error_within_parameters']
            )
            ->setConstructorArgs(
                [$mockquba, false, 50]
            )
            ->getMock();

        $adaptivequiz = new stdClass();
        $adaptivequiz->lowestlevel = 1;
        $adaptivequiz->highestlevel = 10;
        $adaptivequiz->standarderror = 5;

        $mockcatalgo->expects($this->once())
            ->method('question_was_marked_correct')
            ->willReturn(true); // Last attempted question marked correctly.
        $mockcatalgo->expects($this->once())
            ->method('compute_next_difficulty')
            ->willReturn(52);
        $mockcatalgo->expects($this->never())
            ->method('compute_right_answers');
        $mockcatalgo->expects($this->never())
            ->method('compute_wrong_answers');
        $mockcatalgo->expects($this->never())
            ->method('estimate_measure');
        $mockcatalgo->expects($this->never())
            ->method('estimate_standard_error');
        $mockcatalgo->expects($this->never())
            ->method('standard_error_within_parameters');

        $result = $mockcatalgo->perform_calculation_steps(
            50,
            1,
            questions_difficulty_range::from_activity_instance($adaptivequiz),
            $adaptivequiz->standarderror
        );

        $this->assertEquals(52, $result);
    }

    /**
     * This function tests the return data from perform_calculation_steps(), where the user answered
     * the last question incorrectly and the attempt has not met the minimum stopping criteria.
     */
    public function test_perform_calculation_steps_nostop_incorrect_answer() {
        $this->resetAfterTest();

        $mockquba = $this->createMock(question_usage_by_activity::class);

        // Minimum stopping criteria has not been met.
        $mockcatalgo = $this
            ->getMockBuilder(catalgo::class)
            ->onlyMethods(
                ['question_was_marked_correct', 'compute_next_difficulty', 'compute_right_answers', 'compute_wrong_answers',
                    'estimate_measure', 'estimate_standard_error', 'standard_error_within_parameters']
            )
            ->setConstructorArgs(
                [$mockquba, false, 50]
            )
            ->getMock();

        $adaptivequiz = new stdClass();
        $adaptivequiz->lowestlevel = 1;
        $adaptivequiz->highestlevel = 10;
        $adaptivequiz->standarderror = 5;

        $mockcatalgo->expects($this->once())
            ->method('question_was_marked_correct')
            ->willReturn(false); // Last attempted question marked incorrectly.
        $mockcatalgo->expects($this->once())
            ->method('compute_next_difficulty')
            ->willReturn(48);
        $mockcatalgo->expects($this->never())
            ->method('compute_right_answers');
        $mockcatalgo->expects($this->never())
            ->method('compute_wrong_answers');
        $mockcatalgo->expects($this->never())
            ->method('estimate_measure');
        $mockcatalgo->expects($this->never())
            ->method('estimate_standard_error');
        $mockcatalgo->expects($this->never())
            ->method('standard_error_within_parameters');

        $result = $mockcatalgo->perform_calculation_steps(
            50,
            1,
            questions_difficulty_range::from_activity_instance($adaptivequiz),
            $adaptivequiz->standarderror
        );

        $this->assertEquals(48, $result);
    }

    /**
     * This function tests the return data from perform_calculation_steps(), where the attempt has met
     * all the criteria to determine the standard error and the function runs from beginning to end.
     */
    public function test_perform_calculation_steps_stop_no_fail() {
        $this->resetAfterTest();

        $mockquba = $this->createMock(question_usage_by_activity::class);

        // Minimum stopping criteria has been met.
        $mockcatalgo = $this
            ->getMockBuilder(catalgo::class)
            ->onlyMethods(
                ['question_was_marked_correct', 'compute_next_difficulty', 'compute_right_answers', 'compute_wrong_answers',
                    'standard_error_within_parameters']
            )
            ->setConstructorArgs(
                [$mockquba, true, 50]
            )
            ->getMock();

        $adaptivequiz = new stdClass();
        $adaptivequiz->lowestlevel = 1;
        $adaptivequiz->highestlevel = 10;
        $adaptivequiz->standarderror = 5;

        $questionsdifficultyrange = questions_difficulty_range::from_activity_instance($adaptivequiz);

        $mockcatalgo->expects($this->once())
            ->method('question_was_marked_correct')
            ->willReturn(true);
        $mockcatalgo->expects($this->once())
            ->method('compute_next_difficulty')
            ->with(50, 2, true, $questionsdifficultyrange)
            ->willReturn(48);
        $mockcatalgo->expects($this->once())
            ->method('compute_right_answers')
            ->withAnyParameters()
            ->willReturn(1);
        $mockcatalgo->expects($this->once())
            ->method('compute_wrong_answers')
            ->withAnyParameters()
            ->willReturn(1);
        $mockcatalgo->expects($this->once())
            ->method('standard_error_within_parameters')
            ->withAnyParameters() // Second parameter (is not rounded) and has decimal with many decimal places.
            ->willReturn(1.0);

        $result = $mockcatalgo->perform_calculation_steps(50, 2, $questionsdifficultyrange, $adaptivequiz->standarderror);

        $this->assertEquals(48, $result);
    }

    /**
     * This function tests the return value from standard_error_within_parameters().
     */
    public function test_standard_error_within_parameters_return_true_then_false() {
        $this->resetAfterTest();

        $mockquba = $this->createMock('question_usage_by_activity');

        $catalgo = new catalgo($mockquba, true, 1);
        $result = $catalgo->standard_error_within_parameters(0.02, 0.1);
        $this->assertTrue($result);

        $result = $catalgo->standard_error_within_parameters(0.01, 0.002);
        $this->assertFalse($result);
    }

    /**
     * This function tests the output from convert_percent_to_logit()
     */
    public function test_convert_percent_to_logit_using_param_less_than_zero() {
        $this->resetAfterTest(true);

        $this->expectException('coding_exception');
        $result = catalgo::convert_percent_to_logit(-1);
    }

    /**
     * This function tests the output from convert_percent_to_logit()
     */
    public function test_convert_percent_to_logit_using_param_greater_than_decimal_five() {
        $this->resetAfterTest(true);

        $this->expectException('coding_exception');
        catalgo::convert_percent_to_logit(0.51);
    }

    /**
     * This function tests the output from convert_percent_to_logit()
     */
    public function test_convert_percent_to_logit() {
        $this->resetAfterTest(true);
        $result = catalgo::convert_percent_to_logit(0.05);
        $result = round($result, 1);
        $this->assertEquals(0.2, $result);
    }

    /**
     * This function tests the output from convert_logit_to_percent()
     */
    public function test_convert_logit_to_percent_using_param_less_than_zero() {
        $this->resetAfterTest(true);

        $this->expectException('coding_exception');
        catalgo::convert_logit_to_percent(-1);
    }

    /**
     * This function tests the output from convert_logit_to_percent()
     */
    public function test_convert_logit_to_percent() {
        $this->resetAfterTest(true);
        $result = catalgo::convert_logit_to_percent(0.2);
        $result = round($result, 2);
        $this->assertEquals(0.05, $result);
    }

    /**
     * This function tests the output from map_logit_to_scale()
     */
    public function test_map_logit_to_scale() {
        $this->resetAfterTest(true);
        $result = catalgo::map_logit_to_scale(-0.6, 16, 1);
        $result = round($result, 1);
        $this->assertEquals(6.3, $result);
    }
}
