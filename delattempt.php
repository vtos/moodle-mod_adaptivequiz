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
 * Confirmation page to remove student attempts.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot .'/mod/adaptivequiz/locallib.php');

use mod_adaptivequiz\local\attempt\attempt;
use mod_adaptivequiz\local\delete_attempt;

// TODO: explain why this is needed as a param.
$cmid = required_param('id', PARAM_INT);
$attemptid = required_param('attempt', PARAM_INT);
// TODO: explain why this is needed as a param.
$userid = required_param('user', PARAM_INT);
$returnurl = required_param('return', PARAM_URL);
$confirm = optional_param('confirm', 0, PARAM_INT);

/** @var cm_info $cm */
list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'adaptivequiz');

require_login($course, true, $cm);

if ($confirm) {
    delete_attempt::with_id($attemptid);

    redirect($returnurl, get_string('attemptdeleted', 'adaptivequiz'));
}

$context = context_module::instance($cm->id);

require_capability('mod/adaptivequiz:viewreport', $context);

$attempt = attempt::get_by_id($attemptid);

$adaptivequiz = $DB->get_record('adaptivequiz', ['id' => $attempt->read_attempt_data()->instance], '*', MUST_EXIST);
$user = $DB->get_record('user', ['id' => $attempt->read_attempt_data()->userid], '*', MUST_EXIST);

$PAGE->set_url('/mod/adaptivequiz/delattempt.php', ['attempt' => $attemptid]);
$PAGE->set_title(format_string($adaptivequiz->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();

$a = new stdClass();
$a->name = fullname($user);
$a->timecompleted = userdate($attempt->read_attempt_data()->timemodified);
$message = get_string('confirmdeleteattempt', 'adaptivequiz', $a);
$confirm = new moodle_url('/mod/adaptivequiz/delattempt.php',
    ['id' => $cmid, 'attempt' => $attemptid, 'user' => $userid, 'return' => $returnurl, 'confirm' => 1]);
echo $OUTPUT->confirm($message, $confirm, $returnurl);

echo $OUTPUT->footer();
