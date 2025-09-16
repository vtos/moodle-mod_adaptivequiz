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
use mod_adaptivequiz\local\attempt\attempt_state;
use mod_adaptivequiz_generator;

/**
 * Adaptive PHPUnit data generator testcase.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers     \mod_adaptivequiz_generator
 */
final class generator_test extends advanced_testcase {

    public function test_generator() {
        global $DB, $SITE;

        $this->resetAfterTest();

        $this->assertEquals(0, $DB->count_records('adaptivequiz'));

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');
        $this->assertInstanceOf('mod_adaptivequiz_generator', $generator);
        $this->assertEquals('adaptivequiz', $generator->get_modulename());

        $questioncategory = $this->getDataGenerator()
            ->get_plugin_generator('core_question')
            ->create_question_category(['name' => 'My category']);

        $generator->create_instance([
            'course' => $SITE->id,
            'questionpool' => [$questioncategory->id],
        ]);

        $generator->create_instance([
            'course' => $SITE->id,
            'questionpool' => [$questioncategory->id],
        ]);

        $adaptivequiz = $generator->create_instance([
            'course' => $SITE->id,
            'questionpool' => [$questioncategory->id],
        ]);

        $this->assertEquals(3, $DB->count_records('adaptivequiz'));

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id);
        $this->assertEquals($adaptivequiz->id, $cm->instance);
        $this->assertEquals('adaptivequiz', $cm->modname);
        $this->assertEquals($SITE->id, $cm->course);

        $context = context_module::instance($cm->id);
        $this->assertEquals($adaptivequiz->cmid, $context->instanceid);
    }

    public function test_it_handles_question_category_names_when_creating_an_instance(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $coursecontext = context_course::instance($course->id);

        $questioncategoryname1 = 'My category 1';
        $questioncategoryname2 = 'My category 2';

        $questioncategory1 = $this->getDataGenerator()
            ->get_plugin_generator('core_question')
            ->create_question_category([
                'contextid' => $coursecontext->id,
                'name' => $questioncategoryname1,
            ]);

        $questioncategory2 = $this->getDataGenerator()
            ->get_plugin_generator('core_question')
            ->create_question_category([
                'contextid' => $coursecontext->id,
                'name' => $questioncategoryname2,
            ]);

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');

        // Pool as a single string.
        $adaptivequiz = $generator->create_instance([
            'course' => $course->id,
            'questionpoolnamed' => $questioncategoryname1,
        ]);
        self::assertEquals(1, $DB->count_records('adaptivequiz_question', [
            'instance' => $adaptivequiz->id,
            'questioncategory' => $questioncategory1->id,
        ]));

        // Pool as an array of strings.
        $adaptivequiz = $generator->create_instance([
            'course' => $course->id,
            'questionpoolnamed' => [$questioncategoryname1, $questioncategoryname2],
        ]);
        self::assertEquals(1, $DB->count_records('adaptivequiz_question', [
            'instance' => $adaptivequiz->id,
            'questioncategory' => $questioncategory1->id,
        ]));
        self::assertEquals(1, $DB->count_records('adaptivequiz_question', [
            'instance' => $adaptivequiz->id,
            'questioncategory' => $questioncategory2->id,
        ]));
    }

    public function test_it_creates_an_in_progress_attempt(): void {
        $this->resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        /** @var mod_adaptivequiz_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');

        $course = $this->getDataGenerator()->create_course();

        $coursecontext = context_course::instance($course->id);

        $questioncategory = $questiongenerator->create_question_category([
            'contextid' => $coursecontext->id,
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

        $adaptivequiz = $generator->create_instance([
            'course' => $course->id,
            'startinglevel' => $startinglevel,
            'lowestlevel' => 1,
            'highestlevel' => 10,
            'questionpool' => [
                $questioncategory->id,
            ],
        ]);

        $user = $this->getDataGenerator()->create_user();
        $context = context_module::instance($adaptivequiz->cmid);

        // End of setup.

        $attempt = $generator->create_in_progress_attempt($user->id, $adaptivequiz->id, $context);
        $this->assertEquals(attempt_state::IN_PROGRESS, $attempt->get('attemptstate'));
        $this->assertGreaterThan(0, $attempt->get('uniqueid'));
        $this->assertEquals($user->id, $attempt->get('userid'));
        $this->assertEquals($adaptivequiz->id, $attempt->get('instance'));
        $this->assertEmpty($attempt->get('attemptstopcriteria'));
    }

    public function test_it_creates_a_completed_attempt(): void {
        $this->resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        /** @var mod_adaptivequiz_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');

        $course = $this->getDataGenerator()->create_course();

        $coursecontext = context_course::instance($course->id);

        $questioncategory = $questiongenerator->create_question_category([
            'contextid' => $coursecontext->id,
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

        $adaptivequiz = $generator->create_instance([
            'course' => $course->id,
            'startinglevel' => $startinglevel,
            'lowestlevel' => 1,
            'highestlevel' => 10,
            'questionpool' => [
                $questioncategory->id,
            ],
        ]);

        $user = $this->getDataGenerator()->create_user();
        $context = context_module::instance($adaptivequiz->cmid);

        // End of setup.

        $attempt = $generator->create_completed_attempt($user->id, $adaptivequiz->id, $context, $attemptdata = [
            'standarderror' => 0.83666,
            'measure' => -3.34409,
        ], $stoppagereason = 'Maximum number of questions attempted');

        $this->assertEquals(attempt_state::COMPLETED, $attempt->get('attemptstate'));
        $this->assertGreaterThan(0, $attempt->get('uniqueid'));
        $this->assertEquals($user->id, $attempt->get('userid'));
        $this->assertEquals($adaptivequiz->id, $attempt->get('instance'));
        $this->assertEquals('Maximum number of questions attempted', $attempt->get('attemptstopcriteria'));
        $this->assertEquals(0.83666, $attempt->get('standarderror'));
        $this->assertEquals(-3.34409, $attempt->get('measure'));
    }
}
