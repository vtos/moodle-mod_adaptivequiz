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
use coding_exception;
use context_module;
use mod_adaptivequiz\local\attempt\attempt_state;
use mod_adaptivequiz_generator;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;

/**
 * A test class.
 *
 * @package    mod_adaptivequiz
 * @copyright  2025 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\mod_adaptivequiz\attempt::class)]
final class attempt_test extends advanced_testcase {

    public function test_it_does_not_allow_to_create_attempts_in_database(): void {
        $attempt = new attempt(0, (object) [
            'instance' => 1,
            'userid' => 1,
            'uniqueid' => 0,
            'attemptstate' => attempt_state::IN_PROGRESS,
            'questionsattempted' => 0,
            'difficultysum' => 0,
            'standarderror' => 999,
            'measure' => 0,
        ]);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('The attempt persistent class must not be used to store the attempt\'s state.');

        $attempt->save();
    }

    public function test_id_does_not_allow_to_change_attempts_and_store_the_changes_in_database(): void {
        $this->resetAfterTest();

        /** @var mod_adaptivequiz_generator $modgenerator */
        $modgenerator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');

        $adaptivequiz = $this->create_adaptive_quiz();
        $user = $this->getDataGenerator()->create_user();
        $context = context_module::instance($adaptivequiz->cmid);

        $attempt = $modgenerator->create_in_progress_attempt($user->id, $adaptivequiz->id, $context);

        $attempt->set_many([
            'attemptstopcriteria' => 'some_text',
            'standarderror' => 0.4,
            'questionsattempted' => 12,
        ]);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('The attempt persistent class must not be used to store the attempt\'s state.');

        $attempt->save();
    }

    public function test_it_does_not_allow_to_delete_attempts_in_database(): void {
        $this->resetAfterTest();

        /** @var mod_adaptivequiz_generator $modgenerator */
        $modgenerator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');

        $adaptivequiz = $this->create_adaptive_quiz();
        $user = $this->getDataGenerator()->create_user();
        $context = context_module::instance($adaptivequiz->cmid);

        $attempt = $modgenerator->create_in_progress_attempt($user->id, $adaptivequiz->id, $context);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('The attempt persistent class must not be used to delete attempts.');

        $attempt->delete();
    }

    public function test_it_gets_an_attempt_by_criteria_for_user(): void {
        $this->resetAfterTest();

        /** @var mod_adaptivequiz_generator $modgenerator */
        $modgenerator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');

        $adaptivequiz = $this->create_adaptive_quiz();

        $user = $this->getDataGenerator()->create_user();
        $randomuser = $this->getDataGenerator()->create_user();

        $context = context_module::instance($adaptivequiz->cmid);

        $randomattempt1 = $modgenerator->create_completed_attempt($randomuser->id, $adaptivequiz->id, $context, $attemptdata = [],
            $stoppagereason = 'some_text');
        $randomattempt2 = $modgenerator->create_completed_attempt($randomuser->id, $adaptivequiz->id, $context, $attemptdata = [],
            $stoppagereason = 'some_text');

        $attempt1 = $modgenerator->create_completed_attempt($user->id, $adaptivequiz->id, $context, $attemptdata = [
            'measure' => -3.34409,
        ], $stoppagereason = 'some_text');

        $this->waitForSecond();

        $attempt2 = $modgenerator->create_completed_attempt($user->id, $adaptivequiz->id, $context, $attemptdata = [
            'measure' => -0.75816,
        ], $stoppagereason = 'some_text');

        $this->waitForSecond();

        $attempt3 = $modgenerator->create_completed_attempt($user->id, $adaptivequiz->id, $context, $attemptdata = [
            'measure' => -3.66337,
        ], $stoppagereason = 'some_text');

        $result = attempt::get_with_highest_score_for_user($user->id, $adaptivequiz->id);
        self::assertEquals($attempt2->get('id'), $result->get('id'));

        $result = attempt::get_first_for_user($user->id, $adaptivequiz->id);
        self::assertEquals($attempt1->get('id'), $result->get('id'));

        $result = attempt::get_last_for_user($user->id, $adaptivequiz->id);
        self::assertEquals($attempt3->get('id'), $result->get('id'));
    }

    /**
     * Wraps creation of an adaptive quiz instance with the item bank configured.
     *
     * Note, this is intended for a single call within a test - it'll create a new course, qbank mod, etc. with each
     * call.
     *
     * @return stdClass What mod generator's create_instance() returns.
     */
    private function create_adaptive_quiz(): stdClass {
        $coregenerator = $this->getDataGenerator();
        /** @var \mod_adaptivequiz_generator $adaptivequizgenerator */
        $modgenerator = $coregenerator->get_plugin_generator('mod_adaptivequiz');
        /** @var  \core_question_generator $questionsgenerator */
        $questiongenerator = $coregenerator->get_plugin_generator('core_question');
        /** @var \mod_qbank_generator $qbankgenerator */
        $qbankgenerator = $coregenerator->get_plugin_generator('mod_qbank');

        $course = $coregenerator->create_course();

        $qbank = $qbankgenerator->create_instance(['course' => $course->id]);
        $qbankcm = get_coursemodule_from_instance('qbank', $qbank->id, 0, false, MUST_EXIST);
        $qbankcontext = context_module::instance($qbankcm->id);
        $qcat = question_get_default_category($qbankcontext->id);

        $question = $questiongenerator->create_question('truefalse', null, ['category' => $qcat->id]);

        $startinglevel = 1;

        $questiongenerator->create_question_tag(['questionid' => $question->id, 'tag' => "adpq_$startinglevel"]);

        $adaptivequiz = $modgenerator->create_instance([
            'course' => $course->id,
            'startinglevel' => $startinglevel,
            'lowestlevel' => 1,
            'highestlevel' => 10,
            'minimumquestions' => 1,
            'maximumquestions' => 2,
        ]);

        $modgenerator->create_link_with_question_bank([
            'adaptivequizid' => $adaptivequiz->id,
            'qbankid' => $qbank->id,
        ]);

        return $adaptivequiz;
    }
}