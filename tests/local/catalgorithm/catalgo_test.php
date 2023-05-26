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

namespace mod_adaptivequiz\local\catalgorithm;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/adaptivequiz/locallib.php');

use advanced_testcase;
use coding_exception;
use mod_adaptivequiz\local\question\question_answer_evaluation_result;
use mod_adaptivequiz\local\question\questions_answered_summary;
use mod_adaptivequiz\local\report\questions_difficulty_range;
use stdClass;

/**
 * Unit tests for the catalgo class.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_adaptivequiz\local\catalgorithm\catalgo
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
     * This function tests compute_next_difficulty().
     * Setting 0 as the lowest level and 100 as the highest level.
     */
    public function test_compute_next_difficulty_zero_min_one_hundred_max() {
        $catalgo = new catalgo(true);

        $adaptivequiz = new stdClass();
        $adaptivequiz->lowestlevel = 0;
        $adaptivequiz->highestlevel = 100;

        $questionsdifficultyrange = questions_difficulty_range::from_activity_instance($adaptivequiz);

        // Test the next difficulty level shown to the student if the student got a level 30 question wrong,
        // having attempted 1 question.
        $questionsattempted = 1;

        $lastdifficultylevel = 30;
        $questionsdifficultyrange = questions_difficulty_range::from_activity_instance($adaptivequiz);
        $logit = catalgo::convert_linear_to_logit($lastdifficultylevel, $questionsdifficultyrange);

        $lastquestionansweredcorrectly = false;

        $result = $catalgo->compute_next_difficulty($questionsattempted, $lastquestionansweredcorrectly, $questionsdifficultyrange,
            $logit);
        $this->assertEquals(5, $result);

        // Test the next difficulty level shown to the student if the student got a level 30 question right,
        // having attempted 1 question.
        $questionsattempted = 1;

        $lastdifficultylevel = 30;
        $questionsdifficultyrange = questions_difficulty_range::from_activity_instance($adaptivequiz);
        $logit = catalgo::convert_linear_to_logit($lastdifficultylevel, $questionsdifficultyrange);

        $lastquestionansweredcorrectly = true;

        $result = $catalgo->compute_next_difficulty($questionsattempted, $lastquestionansweredcorrectly, $questionsdifficultyrange,
            $logit);
        $this->assertEquals(76, $result);

        // Test the next difficulty level shown to the student if the student got a level 80 question wrong,
        // having attempted 2 questions.
        $questionsattempted = 2;

        $lastdifficultylevel = 80;
        $questionsdifficultyrange = questions_difficulty_range::from_activity_instance($adaptivequiz);
        $logit = catalgo::convert_linear_to_logit($lastdifficultylevel, $questionsdifficultyrange);

        $lastquestionansweredcorrectly = false;

        $result = $catalgo->compute_next_difficulty($questionsattempted, $lastquestionansweredcorrectly, $questionsdifficultyrange,
            $logit);
        $this->assertEquals(60, $result);

        // Test the next difficulty level shown to the student if the student got a level 80 question right,
        // having attempted 2 question.
        $questionsattempted = 2;

        $lastdifficultylevel = 80;
        $questionsdifficultyrange = questions_difficulty_range::from_activity_instance($adaptivequiz);
        $logit = catalgo::convert_linear_to_logit($lastdifficultylevel, $questionsdifficultyrange);

        $lastquestionansweredcorrectly = true;

        $result = $catalgo->compute_next_difficulty($questionsattempted, $lastquestionansweredcorrectly, $questionsdifficultyrange,
            $logit);
        $this->assertEquals(92, $result);
    }

    /**
     * This function tests compute_next_difficulty().
     *
     * Setting 1 as the lowest level and 10 as the highest level.
     */
    public function test_compute_next_difficulty_one_min_ten_max_compute_infinity() {
        $catalgo = new catalgo(true);

        $adaptivequiz = new stdClass();
        $adaptivequiz->lowestlevel = 1;
        $adaptivequiz->highestlevel = 10;

        $questionsattempted = 2;

        $lastdifficultylevel = 1;
        $questionsdifficultyrange = questions_difficulty_range::from_activity_instance($adaptivequiz);
        $logit = catalgo::convert_linear_to_logit($lastdifficultylevel, $questionsdifficultyrange);

        $lastquestionansweredcorrectly = false;

        $result = $catalgo->compute_next_difficulty($questionsattempted, $lastquestionansweredcorrectly, $questionsdifficultyrange,
            $logit);
        $this->assertEquals(1, $result);

        $questionsattempted = 2;

        $lastdifficultylevel = 10;
        $questionsdifficultyrange = questions_difficulty_range::from_activity_instance($adaptivequiz);
        $logit = catalgo::convert_linear_to_logit($lastdifficultylevel, $questionsdifficultyrange);

        $lastquestionansweredcorrectly = true;

        $result = $catalgo->compute_next_difficulty($questionsattempted, $lastquestionansweredcorrectly, $questionsdifficultyrange,
            $logit);
        $this->assertEquals(10, $result);
    }

    /**
     * This function tests the return data from estimate_measure().
     */
    public function test_estimate_measure() {
        // Test an attempt with the following details:
        // sum of difficulty - 20, number of questions attempted - 10, number of correct answers - 7,
        // number of incorrect answers - 3.
        $catalgo = new catalgo(true);
        $result = $catalgo->estimate_measure(20, 10, 7, 3);
        $this->assertEquals(2.8473, $result);
    }

    /**
     * This function tests the return data from estimate_standard_error().
     */
    public function test_estimate_standard_error() {
        // Test an attempt with the following details;
        // sum of questions attempted - 10, number of correct answers - 7, number of incorrect answers - 3.
        $catalgo = new catalgo(true);
        $result = $catalgo->estimate_standard_error(10, 7, 3);
        $this->assertEquals(0.69007, $result);
    }

    public function test_it_determines_next_difficulty_as_with_error_when_question_was_not_answered(): void {
        self::resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $adaptivequiz = $this->getDataGenerator()
            ->get_plugin_generator('mod_adaptivequiz')
            ->create_instance([
                'highestlevel' => 10,
                'lowestlevel' => 1,
                'standarderror' => 5,
                'course' => $course->id
            ]);

        $catalgo = new catalgo(false);

        // Random, does not matter in this test.
        $lastdifficultylevel = 1;

        $determinenextdifficultylevelresult = $catalgo->determine_next_difficulty_level(
            5,
            questions_difficulty_range::from_activity_instance($adaptivequiz),
            $adaptivequiz->standarderror,
            question_answer_evaluation_result::when_answer_was_not_given(),
            questions_answered_summary::from_integers(2, 4),
            0,
            0
        );

        self::assertEquals(
            determine_next_difficulty_result::with_error(get_string('errorlastattpquest', 'adaptivequiz')),
            $determinenextdifficultylevelresult
        );
    }

    public function test_it_determines_next_difficulty_as_with_error_when_number_of_questions_attempted_is_not_valid(): void {
        self::resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $adaptivequiz = $this->getDataGenerator()
            ->get_plugin_generator('mod_adaptivequiz')
            ->create_instance([
                'highestlevel' => 10,
                'lowestlevel' => 1,
                'standarderror' => 5,
                'course' => $course->id
            ]);

        $catalgo = new catalgo(true);

        $determinenextdifficultylevelresult = $catalgo->determine_next_difficulty_level(
            10,
            questions_difficulty_range::from_activity_instance($adaptivequiz),
            $adaptivequiz->standarderror,
            question_answer_evaluation_result::when_answer_is_correct(),
            questions_answered_summary::from_integers(1, 1),
            0,
            0
        );

        $this->assertEquals(
            determine_next_difficulty_result::with_error(get_string('errorsumrightwrong', 'adaptivequiz')),
            $determinenextdifficultylevelresult
        );
    }

    public function test_it_determines_next_difficulty_when_answer_is_given_and_stopping_criteria_is_not_met(): void {
        self::resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $adaptivequiz = $this->getDataGenerator()
            ->get_plugin_generator('mod_adaptivequiz')
            ->create_instance([
                'highestlevel' => 10,
                'lowestlevel' => 1,
                'standarderror' => 5,
                'course' => $course->id
            ]);

        $catalgo = new catalgo(false);

        // When answer is correct.
        $determinenextdifficultylevelresult = $catalgo->determine_next_difficulty_level(
            5,
            questions_difficulty_range::from_activity_instance($adaptivequiz),
            $adaptivequiz->standarderror,
            question_answer_evaluation_result::when_answer_is_correct(),
            questions_answered_summary::from_integers(3, 2),
            -10.8052819,
            0
        );

        self::assertEquals(
            determine_next_difficulty_result::with_next_difficulty_level_determined(1),
            $determinenextdifficultylevelresult
        );

        // When answer is not correct.
        $determinenextdifficultylevelresult = $catalgo->determine_next_difficulty_level(
            6,
            questions_difficulty_range::from_activity_instance($adaptivequiz),
            $adaptivequiz->standarderror,
            question_answer_evaluation_result::when_answer_is_incorrect(),
            questions_answered_summary::from_integers(4, 1),
            -13.3702313,
            0
        );

        self::assertEquals(
            determine_next_difficulty_result::with_next_difficulty_level_determined(1),
            $determinenextdifficultylevelresult
        );
    }

    public function test_it_determines_next_difficulty_when_answer_is_given_and_stopping_criteria_is_met(): void {
        self::resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $adaptivequiz = $this->getDataGenerator()
            ->get_plugin_generator('mod_adaptivequiz')
            ->create_instance([
                'highestlevel' => 10,
                'lowestlevel' => 1,
                'standarderror' => 5,
                'course' => $course->id
            ]);

        $catalgo = new catalgo(true);

        $determinenextdifficultylevelresult = $catalgo->determine_next_difficulty_level(
            5,
            questions_difficulty_range::from_activity_instance($adaptivequiz),
            $adaptivequiz->standarderror,
            question_answer_evaluation_result::when_answer_is_incorrect(),
            questions_answered_summary::from_integers(3, 2),
            -10.8052819,
            0.86603
        );

        self::assertEquals(
            determine_next_difficulty_result::with_next_difficulty_level_determined(1),
            $determinenextdifficultylevelresult
        );
    }

    public function test_it_determines_next_difficulty_as_with_error_standard_error_is_within_configured_parameters(): void {
        self::resetAfterTest();

        $settingsstandarderror = 14;

        $course = $this->getDataGenerator()->create_course();
        $adaptivequiz = $this->getDataGenerator()
            ->get_plugin_generator('mod_adaptivequiz')
            ->create_instance([
                'highestlevel' => 10,
                'lowestlevel' => 1,
                'standarderror' => $settingsstandarderror,
                'course' => $course->id
            ]);

        $catalgo = new catalgo(true);

        $determinenextdifficultylevelresult = $catalgo->determine_next_difficulty_level(
            5,
            questions_difficulty_range::from_activity_instance($adaptivequiz),
            $adaptivequiz->standarderror,
            question_answer_evaluation_result::when_answer_is_incorrect(),
            questions_answered_summary::from_integers(3, 2),
            -14.4012234,
            0.53229
        );

        $expectedstringplaceholder = new stdClass();
        $expectedstringplaceholder->calerror = 13;
        $expectedstringplaceholder->definederror = $settingsstandarderror;

        $this->assertEquals(
            determine_next_difficulty_result::with_error(
                get_string('calcerrorwithinlimits', 'adaptivequiz', $expectedstringplaceholder)
            ),
            $determinenextdifficultylevelresult
        );
    }

    /**
     * This function tests the return value from standard_error_within_parameters().
     */
    public function test_standard_error_within_parameters_return_true_then_false() {
        $catalgo = new catalgo(true);
        $result = $catalgo->standard_error_within_parameters(0.02, 0.1);
        $this->assertTrue($result);

        $result = $catalgo->standard_error_within_parameters(0.01, 0.002);
        $this->assertFalse($result);
    }

    /**
     * This function tests the output from convert_percent_to_logit()
     */
    public function test_convert_percent_to_logit_using_param_less_than_zero() {
        $this->expectException('coding_exception');
        $result = catalgo::convert_percent_to_logit(-1);
    }

    /**
     * This function tests the output from convert_percent_to_logit()
     */
    public function test_convert_percent_to_logit_using_param_greater_than_decimal_five() {
        $this->expectException('coding_exception');
        catalgo::convert_percent_to_logit(0.51);
    }

    /**
     * This function tests the output from convert_percent_to_logit()
     */
    public function test_convert_percent_to_logit() {
        $result = catalgo::convert_percent_to_logit(0.05);
        $result = round($result, 1);
        $this->assertEquals(0.2, $result);
    }

    /**
     * This function tests the output from convert_logit_to_percent()
     */
    public function test_convert_logit_to_percent_using_param_less_than_zero() {
        $this->expectException('coding_exception');
        catalgo::convert_logit_to_percent(-1);
    }

    /**
     * This function tests the output from convert_logit_to_percent()
     */
    public function test_convert_logit_to_percent() {
        $result = catalgo::convert_logit_to_percent(0.2);
        $result = round($result, 2);
        $this->assertEquals(0.05, $result);
    }

    /**
     * This function tests the output from map_logit_to_scale()
     */
    public function test_map_logit_to_scale() {
        $result = catalgo::map_logit_to_scale(-0.6, 16, 1);
        $result = round($result, 1);
        $this->assertEquals(6.3, $result);
    }
}
