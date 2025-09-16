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
 * Redirect users who clicked on a link in the gradebook.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');

use mod_adaptivequiz\attempt as read_attempt;
use mod_adaptivequiz\local\attempt as attempt_entity;

$id = required_param('id', PARAM_INT);          // Course module ID.
$itemnumber = optional_param('itemnumber', 0, PARAM_INT); // Item number, may be != 0 for activities that allow more than one
                                                          // grade per user.
$userid = optional_param('userid', 0, PARAM_INT); // Graded user ID (optional).

if (!$cm = get_coursemodule_from_id('adaptivequiz', $id)) {
    throw new moodle_exception('invalidcoursemodule');
}
if (!$course = $DB->get_record('course', ['id' => $cm->course])) {
    throw new moodle_exception("coursemisconf");
}

$adaptivequiz  = $DB->get_record('adaptivequiz', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

if (!has_capability('mod/adaptivequiz:viewreport', $context) || $userid == $USER->id) {
    redirect(new moodle_url('/mod/adaptivequiz/view.php', ['id' => $id]));
}

if ($userid) {
    if (!attempt_entity::user_has_completed_on_quiz($adaptivequiz->id, $userid)) {
        redirect(new moodle_url('/mod/adaptivequiz/view.php', ['id' => $id]));
    }

    /** @var read_attempt $attempt */
    $attempt = call_user_func(function (stdClass $adaptivequiz, int $userid): read_attempt {
        if ($adaptivequiz->grademethod == ADAPTIVEQUIZ_GRADEHIGHEST) {
            return read_attempt::get_with_highest_score_for_user($userid, $adaptivequiz->id);
        }
        if ($adaptivequiz->grademethod == ADAPTIVEQUIZ_ATTEMPTFIRST) {
            return read_attempt::get_first_for_user($userid, $adaptivequiz->id);
        }

        // The grading method is ADAPTIVEQUIZ_ATTEMPTLAST.
        return read_attempt::get_last_for_user($userid, $adaptivequiz->id);
    }, $adaptivequiz, $userid);

    redirect(new moodle_url('/mod/adaptivequiz/reviewattempt.php', ['attempt' => $attempt->get('id')]));
}

redirect(new moodle_url('/mod/adaptivequiz/view.php', ['id' => $id]));
