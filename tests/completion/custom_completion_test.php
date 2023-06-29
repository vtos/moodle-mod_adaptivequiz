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

namespace mod_adaptivequiz\completion;

use advanced_testcase;
use cm_info;
use context_module;
use mod_adaptivequiz\local\attempt\attempt;

/**
 * Completion rules tests.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_adaptivequiz\completion\custom_completion
 */
class custom_completion_test extends advanced_testcase {

    public function test_it_defines_completion_state_based_on_attempt_completion(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $adaptivequizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');
        $adaptivequiz = $adaptivequizgenerator->create_instance(['course' => $course->id, 'completionattemptcompleted' => 1]);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id, $course->id);

        $attempt = attempt::create($adaptivequiz->id, $user->id);

        $cminfo = cm_info::create($cm);
        $cminfo->override_customdata('customcompletionrules',
            ['completionattemptcompleted' => $adaptivequiz->completionattemptcompleted]);

        $completion = new custom_completion($cminfo, $user->id);

        $this->assertEquals(COMPLETION_INCOMPLETE, $completion->get_state('completionattemptcompleted'));

        $attempt->complete($adaptivequiz, context_module::instance($cm->id), 'php unit test', time());

        $this->assertEquals(COMPLETION_COMPLETE, $completion->get_state('completionattemptcompleted'));
    }
}
