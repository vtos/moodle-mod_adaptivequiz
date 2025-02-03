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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/adaptivequiz/locallib.php');

use advanced_testcase;
use cm_info;
use context_module;
use mod_adaptivequiz\local\attempt;

/**
 * A test class.
 *
 * @package    mod_adaptivequiz
 * @copyright  2022 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_adaptivequiz\completion\custom_completion
 */
final class custom_completion_test extends advanced_testcase {

    public function test_it_defines_completion_state_based_on_attempt_completion():void {
        global $DB;

        $this->resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $modgenerator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');

        $course = $this->getDataGenerator()->create_course([
            'enablecompletion' => 1,
        ]);

        $questioncategory = $questiongenerator->create_question_category([
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

        $adaptivequiz = $modgenerator->create_instance([
            'course' => $course->id,
            'startinglevel' => $startinglevel,
            'lowestlevel' => 1,
            'highestlevel' => 10,
            'questionpool' => [
                $questioncategory->id,
            ],
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionattemptcompleted' => 1,
        ]);

        $user = $this->getDataGenerator()->create_user();

        // End of setup.

        $context = context_module::instance($adaptivequiz->cmid);

        $adaptivequizforattempt = clone($adaptivequiz);
        $adaptivequizforattempt->context = $context;

        $attempt = new attempt($adaptivequizforattempt, $user->id);

        $cm = get_coursemodule_from_id(modulename: 'adaptivequiz', cmid: $adaptivequiz->cmid, courseid: 0, sectionnum: false,
            strictness: MUST_EXIST);
        $cminfo = cm_info::create($cm);

        $completion = new custom_completion($cminfo, $user->id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $completion->get_state('completionattemptcompleted'));

        $attempt->set_level($adaptivequiz->startinglevel);
        $attempt->start_attempt();

        $attemptrecord = $attempt->get_attempt();
        $attemptuniqueid = $DB->get_field('adaptivequiz_attempt', 'uniqueid', ['id' => $attemptrecord->id], MUST_EXIST);

        adaptivequiz_complete_attempt($attemptuniqueid, $adaptivequiz, $context, $user->id, '1', 'php unit test');

        $this->assertEquals(COMPLETION_COMPLETE, $completion->get_state('completionattemptcompleted'));
    }
}
