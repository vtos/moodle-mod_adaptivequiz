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
use context_module;
use mod_adaptivequiz\local\attempt\attempt_state;
use mod_adaptivequiz_generator;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Adaptive PHPUnit data generator testcase.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\mod_adaptivequiz_generator::class)]
final class generator_test extends advanced_testcase {

    public function test_generator() {
        global $DB, $SITE;

        $this->resetAfterTest();

        $this->assertEquals(0, $DB->count_records('adaptivequiz'));

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');
        $this->assertInstanceOf('mod_adaptivequiz_generator', $generator);
        $this->assertEquals('adaptivequiz', $generator->get_modulename());

        $generator->create_instance(['course' => $SITE->id]);
        $generator->create_instance(['course' => $SITE->id]);

        $adaptivequiz = $generator->create_instance(['course' => $SITE->id]);

        $this->assertEquals(3, $DB->count_records('adaptivequiz'));

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id);
        $this->assertEquals($adaptivequiz->id, $cm->instance);
        $this->assertEquals('adaptivequiz', $cm->modname);
        $this->assertEquals($SITE->id, $cm->course);

        $context = context_module::instance($cm->id);
        $this->assertEquals($adaptivequiz->cmid, $context->instanceid);
    }

    public function test_it_creates_links_with_question_banks(): void {
        global $DB;

        $this->resetAfterTest();

        /** @var mod_adaptivequiz_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');

        $course = $this->getDataGenerator()->create_course();
        $qbankgenerator = $this->getDataGenerator()->get_plugin_generator('mod_qbank');

        $adaptivequiz = $generator->create_instance(['course' => $course->id]);

        $qbank1 = $qbankgenerator->create_instance(['course' => $course->id]);
        $qbank2 = $qbankgenerator->create_instance(['course' => $course->id]);

        foreach ([$qbank1->id, $qbank2->id] as $qbankid) {
            $generator->create_link_with_question_bank([
                'adaptivequizid' => $adaptivequiz->id,
                'qbankid' => $qbankid,
            ]);
        }

        $result = $DB->get_fieldset('adaptivequiz_qbank', 'qbankid', ['adaptivequizid' => $adaptivequiz->id]);
        self::assertCount(2, $result);
        self::assertEqualsCanonicalizing([$qbank1->id, $qbank2->id], $result);
    }

    public function test_it_creates_links_with_single_question_categories(): void {
        global $DB;

        $this->resetAfterTest();

        $coregenerator = $this->getDataGenerator();
        /** @var \mod_qbank_generator $qbankgenerator */
        $qbankgenerator = $coregenerator->get_plugin_generator('mod_qbank');
        /** @var  \core_question_generator $questiongenerator */
        $questiongenerator = $coregenerator->get_plugin_generator('core_question');
        /** @var mod_adaptivequiz_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');

        $course = $this->getDataGenerator()->create_course();

        $qbank = $qbankgenerator->create_instance(['course' => $course->id]);
        $qbankcm = get_coursemodule_from_instance('qbank', $qbank->id, $course->id);
        $qbankcontext = context_module::instance($qbankcm->id);

        $qcat1 = $questiongenerator->create_question_category([
            'contextid' => $qbankcontext->id,
            'name' => 'My category 1',
        ]);

        $qcat2 = $questiongenerator->create_question_category([
            'contextid' => $qbankcontext->id,
            'name' => 'My category 2',
        ]);

        $adaptivequiz1 = $generator->create_instance(['course' => $course->id]);
        $adaptivequiz2 = $generator->create_instance(['course' => $course->id]);

        $generator->create_link_with_question_category([
            'adaptivequizid' => $adaptivequiz1->id,
            'qcategoryid' => $qcat1->id,
        ]);

        $generator->create_link_with_question_category([
            'adaptivequizid' => $adaptivequiz1->id,
            'qcategoryid' => $qcat2->id,
        ]);

        // Assertions.

        $result = $DB->get_fieldset('adaptivequiz_question', 'questioncategory', ['instance' => $adaptivequiz1->id]);
        self::assertCount(2, $result);
        self::assertEqualsCanonicalizing([$qcat1->id, $qcat2->id], $result);

        self::assertEquals(0, $DB->count_records('adaptivequiz_question', ['instance' => $adaptivequiz2->id]));
    }

    public function test_it_creates_an_in_progress_attempt(): void {
        $this->resetAfterTest();

        $coregenerator = $this->getDataGenerator();
        /** @var mod_adaptivequiz_generator $generator */
        $generator = $coregenerator->get_plugin_generator('mod_adaptivequiz');
        /** @var \mod_qbank_generator $qbankgenerator */
        $qbankgenerator = $coregenerator->get_plugin_generator('mod_qbank');
        /** @var  \core_question_generator $questiongenerator */
        $questiongenerator = $coregenerator->get_plugin_generator('core_question');

        $course = $coregenerator->create_course();

        $qbank = $qbankgenerator->create_instance(['course' => $course->id]);
        $qbankcm = get_coursemodule_from_instance('qbank', $qbank->id, 0, false, MUST_EXIST);
        $qbankcontext = context_module::instance($qbankcm->id);
        $qcat = question_get_default_category($qbankcontext->id);

        $question = $questiongenerator->create_question('truefalse', null, ['category' => $qcat->id]);

        $startinglevel = 1;

        $questiongenerator->create_question_tag(['questionid' => $question->id, 'tag' => "adpq_$startinglevel"]);

        $adaptivequiz = $generator->create_instance([
            'course' => $course->id,
            'startinglevel' => $startinglevel,
            'lowestlevel' => 1,
            'highestlevel' => 10,
            'minimumquestions' => 1,
            'maximumquestions' => 2,
        ]);

        $generator->create_link_with_question_bank([
            'adaptivequizid' => $adaptivequiz->id,
            'qbankid' => $qbank->id,
        ]);

        $user = $coregenerator->create_user();
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

        $coregenerator = $this->getDataGenerator();
        /** @var mod_adaptivequiz_generator $generator */
        $generator = $coregenerator->get_plugin_generator('mod_adaptivequiz');
        /** @var \mod_qbank_generator $qbankgenerator */
        $qbankgenerator = $coregenerator->get_plugin_generator('mod_qbank');
        /** @var  \core_question_generator $questiongenerator */
        $questiongenerator = $coregenerator->get_plugin_generator('core_question');

        $course = $coregenerator->create_course();

        $qbank = $qbankgenerator->create_instance(['course' => $course->id]);
        $qbankcm = get_coursemodule_from_instance('qbank', $qbank->id, 0, false, MUST_EXIST);
        $qbankcontext = context_module::instance($qbankcm->id);
        $qcat = question_get_default_category($qbankcontext->id);

        $question = $questiongenerator->create_question('truefalse', null, ['category' => $qcat->id]);

        $startinglevel = 1;

        $questiongenerator->create_question_tag(['questionid' => $question->id, 'tag' => "adpq_$startinglevel"]);

        $adaptivequiz = $generator->create_instance([
            'course' => $course->id,
            'startinglevel' => $startinglevel,
            'lowestlevel' => 1,
            'highestlevel' => 10,
            'minimumquestions' => 1,
            'maximumquestions' => 2,
        ]);

        $generator->create_link_with_question_bank([
            'adaptivequizid' => $adaptivequiz->id,
            'qbankid' => $qbank->id,
        ]);

        $user = $coregenerator->create_user();
        $context = context_module::instance($adaptivequiz->cmid);

        // End of setup.

        $attemptdata = [
            'standarderror' => 0.83666,
            'measure' => -3.34409,
        ];

        $stoppagereason = 'Maximum number of questions attempted';

        $attempt = $generator->create_completed_attempt(
            $user->id,
            $adaptivequiz->id,
            $context,
            $attemptdata,
            $stoppagereason
        );

        $this->assertEquals(attempt_state::COMPLETED, $attempt->get('attemptstate'));
        $this->assertGreaterThan(0, $attempt->get('uniqueid'));
        $this->assertEquals($user->id, $attempt->get('userid'));
        $this->assertEquals($adaptivequiz->id, $attempt->get('instance'));
        $this->assertEquals('Maximum number of questions attempted', $attempt->get('attemptstopcriteria'));
        $this->assertEquals(0.83666, $attempt->get('standarderror'));
        $this->assertEquals(-3.34409, $attempt->get('measure'));
    }
}
