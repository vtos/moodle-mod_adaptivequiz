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
require_once($CFG->dirroot.'/mod/adaptivequiz/lib.php');

use advanced_testcase;
use mod_adaptivequiz\local\attempt\attempt_state;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

/**
 * Adaptive lib.php PHPUnit tests.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversFunction('adaptivequiz_delete_instance')]
#[CoversFunction('adaptivequiz_print_recent_mod_activity')]
#[CoversFunction('adaptivequiz_update_item_administration_params')]
class lib_test extends advanced_testcase {
    /**
     * This functions loads data via the tests/fixtures/mod_adaptivequiz.xml file
     * @return void
     */
    protected function setup_test_data_xml() {
        $this->dataset_from_files(
            [__DIR__.'/fixtures/mod_adaptivequiz.xml']
        )->to_database();
    }

    /**
     * Provide input data to the parameters of the test_questioncat_association_insert() method.
     */
    public function questioncat_association_records() {
        $data = array();

        $adaptivequiz = new stdClass();
        $adaptivequiz->questionpool = array(1, 2, 3, 4);
        $data[] = array(1, $adaptivequiz);

        $adaptivequiz = new stdClass();
        $adaptivequiz->questionpool = array(1, 2);
        $data[] = array(2, $adaptivequiz);

        $adaptivequiz = new stdClass();
        $adaptivequiz->questionpool = array(1, 2, 4);
        $data[] = array(3, $adaptivequiz);

        return $data;
    }

    /**
     * This function tests the removal of an activity instance and all related data.
     */
    public function test_adaptivequiz_delete_instance() {
        global $DB;

        $this->resetAfterTest();

        $modgenerator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');

        $course = $this->getDataGenerator()->create_course();

        $adaptivequiz = $modgenerator->create_instance([
            'course' => $course->id,
            'questionpool' => [],
        ]);

        adaptivequiz_delete_instance($adaptivequiz->id);

        $this->assertEquals(0, $DB->count_records('adaptivequiz', ['id' => $adaptivequiz->id]));
        $this->assertEquals(0, $DB->count_records('adaptivequiz_question', ['instance' => $adaptivequiz->id]));
        $this->assertEquals(0, $DB->count_records('adaptivequiz_attempt', ['instance' => $adaptivequiz->id]));
        $this->assertEquals(0, $DB->count_records('question_usages', ['id' => $adaptivequiz->id]));
    }

    /**
     * This function tests the output from adaptivequiz_print_recent_mod_activity().
     */
    public function test_adaptivequiz_print_recent_mod_activity_details_true() {
        $this->resetAfterTest(true);

        $dummy = new stdClass();
        $dummy->user = new stdClass();
        $dummy->user->id = 2;
        $dummy->user->fullname = 'user-phpunit';
        $dummy->user->alternatename = 'user-phpunit';
        $dummy->user->picture = '';
        $dummy->user->firstname = 'user';
        $dummy->user->middlename = '-';
        $dummy->user->lastname = 'phpunit';
        $dummy->user->imagealt = '';
        $dummy->user->email = 'a@a.com';
        $dummy->user->firstnamephonetic = 'user';
        $dummy->user->lastnamephonetic = 'phpunit';
        $dummy->content = new stdClass();
        $dummy->content->attemptstate = attempt_state::IN_PROGRESS;
        $dummy->content->questionsattempted = '12';
        $dummy->timestamp = 1234;
        $dummy->type = 'mod_adaptivequiz';
        $dummy->name = 'my-phpunit-test';
        $dummy->cmid = 99;

        $output = adaptivequiz_print_recent_mod_activity($dummy, 1, true, array('mod_adaptivequiz' => 'adaptivequiz'), true, true);
        $this->assertStringContainsString('<table', $output);
        $this->assertStringContainsString('<tr>', $output);
        $this->assertStringContainsString('<td', $output);
        $this->assertStringContainsString('mod/adaptivequiz/view.php?id=99', $output);
        $this->assertStringContainsString('/user/view.php?id=2', $output);
        $this->assertStringContainsString('user phpunit', $output);
        $this->assertStringContainsString('my-phpunit-test', $output);
    }

    /**
     * This function tests the output from adaptivequiz_print_recent_mod_activity().
     */
    public function test_adaptivequiz_print_recent_mod_activity_details_false() {
        $this->resetAfterTest(true);

        $dummy = new stdClass();
        $dummy->user = new stdClass();
        $dummy->user->id = 2;
        $dummy->user->fullname = 'user-phpunit';
        $dummy->user->alternatename = 'user-phpunit';
        $dummy->user->picture = '';
        $dummy->user->firstname = 'user';
        $dummy->user->middlename = '-';
        $dummy->user->lastname = 'phpunit';
        $dummy->user->imagealt = '';
        $dummy->user->email = 'a@a.com';
        $dummy->user->firstnamephonetic = 'user';
        $dummy->user->lastnamephonetic = 'phpunit';
        $dummy->content = new stdClass();
        $dummy->content->attemptstate = attempt_state::IN_PROGRESS;
        $dummy->content->questionsattempted = '12';
        $dummy->timestamp = 1234;
        $dummy->type = 'mod_adaptivequiz';
        $dummy->name = 'my-phpunit-test';
        $dummy->cmid = 99;

        $output = adaptivequiz_print_recent_mod_activity($dummy, 1, false, array('mod_adaptivequiz' => 'adaptivequiz'), true, true);

        $this->assertStringContainsString('<table', $output);
        $this->assertStringContainsString('<tr>', $output);
        $this->assertStringContainsString('<td', $output);
        $this->assertStringContainsString('/user/view.php?id=2', $output);
        $this->assertStringContainsString('user phpunit', $output);
    }

    /**
     * Tests how fields related to item administration settings are updated with the dedicated function.
     */
    public function test_item_administration_params_can_be_updated_for_an_adaptive_quiz_instance(): void {
        global $DB;

        $this->resetAfterTest();

        $coregenerator = $this->getDataGenerator();
        /** @var \mod_adaptivequiz_generator $adaptivequizgenerator */
        $adaptivequizgenerator = $coregenerator->get_plugin_generator('mod_adaptivequiz');

        $course = $coregenerator->create_course();
        $adaptivequiz = $adaptivequizgenerator->create_instance(['name' => 'Adaptive Quiz', 'course' => $course->id]);

        $updatedata = (object) [
            'id' => $adaptivequiz->id,
            'highestlevel' => 100,
            'lowestlevel' => 1,
            'startinglevel' => 50,
            'minimumquestions' => 20,
            'maximumquestions' => 100,
            'standarderror' => 9,
        ];

        adaptivequiz_update_item_administration_params($updatedata);

        $adaptivequizupdated = $DB->get_record('adaptivequiz', ['id' => $adaptivequiz->id], '*', MUST_EXIST);

        $expected = ['highestlevel' => 100, 'lowestlevel' => 1, 'startinglevel' => 50, 'minimumquestions' => 20,
            'maximumquestions' => 100, 'standarderror' => 9];

        self::assertEquals($expected, [
            'highestlevel' => $adaptivequizupdated->highestlevel,
            'lowestlevel' => $adaptivequizupdated->lowestlevel,
            'startinglevel' => $adaptivequizupdated->startinglevel,
            'minimumquestions' => $adaptivequizupdated->minimumquestions,
            'maximumquestions' => $adaptivequizupdated->maximumquestions,
            'standarderror' => $adaptivequizupdated->standarderror,
        ]);
    }

    /**
     * Tests no other fields are updated by the function related to item administration settings.
     *
     * @param string $exceptionmessage An empty string if no exception is expected.
     * @param array $fields An array of [key => value] properties for adaptivequiz instance.
     */
    #[DataProvider('item_administration_params_data')]
    public function test_item_administration_params_update_cannot_affect_other_adaptivequiz_properties(
        string $exceptionmessage,
        array $fields
    ): void {
        global $DB;

        $this->resetAfterTest();

        $coregenerator = $this->getDataGenerator();
        /** @var \mod_adaptivequiz_generator $adaptivequizgenerator */
        $adaptivequizgenerator = $coregenerator->get_plugin_generator('mod_adaptivequiz');

        $course = $coregenerator->create_course();
        $adaptivequiz = $adaptivequizgenerator->create_instance(['name' => 'Adaptive Quiz', 'course' => $course->id]);

        $updatedata = (object) array_merge(['id' => $adaptivequiz->id], $fields);

        if ($exceptionmessage) {
            $this->expectExceptionMessage($exceptionmessage);
        }

        adaptivequiz_update_item_administration_params($updatedata);

        $adaptivequizupdated = $DB->get_record('adaptivequiz', ['id' => $adaptivequiz->id], '*', MUST_EXIST);
        self::assertEquals($adaptivequiz->name, $adaptivequizupdated->name);
    }

    /**
     * Data provider for test_item_administration_params_update_cannot_affect_other_adaptivequiz_properties().
     */
    public static function item_administration_params_data(): array {
        return [
            'empty_fieldset_left' => [
                'exceptionmessage' => 'moodle_database::update_record_raw() no fields found.',
                'fields' => [
                    'name' => 'Adaptive Quiz edited name',
                ],
            ],
            'with_some_unexpected_fields' => [
                'exceptionmessage' => '',
                'fields' => [
                    'highestlevel' => 120,
                    'name' => 'Adaptive Quiz edited name',
                ],
            ],
        ];
    }
}
