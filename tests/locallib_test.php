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
     * This functions loads data via the tests/fixtures/mod_adaptivequiz.xml file
     */
    protected function setup_test_data_xml() {
        $this->dataset_from_files(
            [__DIR__.'/fixtures/mod_adaptivequiz.xml']
        )->to_database();
    }

    /**
     * Provide input data to the parameters of the test_count_user_previous_attempts_fail() method.
     * @return $data an array with arrays of data
     */
    public function fail_attempt_data() {
        $data = array(
            array(99, 99),
            array(99, 3),
            array(13, 99),
        );

        return $data;
    }

    /**
     * Provide input data to the parameters of the test_allowed_attempt_fail() method.
     * @return $data an array with arrays of data
     */
    public function attempts_allowed_data_fail() {
        $data = array(
            array(99, 100),
            array(99, 99),
        );

        return $data;
    }

    /**
     * Provide input data to the parameters of the test_allowed_attempt() method.
     * @return $data an array with arrays of data
     */
    public function attempts_allowed_data() {
        $data = array(
            array(99, 98),
            array(0, 99),
        );

        return $data;
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
    public function test_get_selected_question_cateogires() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $data = adaptivequiz_get_selected_question_cateogires(12);

        $this->assertEquals(6, count($data));
    }

    /**
     * This function tests failing conditions for counting user's previous attempts
     * that have been marked as completed.
     *
     * @dataProvider fail_attempt_data
     * @param int $instanceid activity instance id
     * @param int $userid user id
     * @covers ::adaptivequiz_count_user_previous_attempts
     */
    public function test_count_user_previous_attempts_fail($instanceid, $userid) {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $result = adaptivequiz_count_user_previous_attempts($instanceid, $userid);

        $this->assertEquals(0, $result);
    }

    /**
     * This function tests a non-failing conditions for counting user's previous attempts
     * that have been marked as completed.
     *
     * @covers ::adaptivequiz_count_user_previous_attempts
     */
    public function test_count_user_previous_attempts_inprogress() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $result = adaptivequiz_count_user_previous_attempts(13, 3);

        $this->assertEquals(1, $result);
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
    public function test_adaptivequiz_uniqueid_part_of_attempt() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        // Assert that there exists a record where the uniqueid, activity instance and userid all match up.
        $result = adaptivequiz_uniqueid_part_of_attempt(3, 1, 2);
        $this->assertTrue($result);

        $result = adaptivequiz_uniqueid_part_of_attempt(1, 1, 1);
        $this->assertFalse($result);
    }

    /**
     * This function tests checking if the minimum number of questions have been attempted.
     *
     * @covers ::adaptivequiz_min_attempts_reached
     */
    public function test_adaptivequiz_min_attempts_reached() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $result = adaptivequiz_min_attempts_reached(3, 13, 3);
        $this->assertFalse($result);

        $result = adaptivequiz_min_attempts_reached(4, 13, 4);
        $this->assertTrue($result);
    }
}
