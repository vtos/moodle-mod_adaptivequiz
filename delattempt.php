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

require_once(__DIR__ . '/../../config.php');

$attemptid = required_param('attempt', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);
$returnurl = optional_param('return', '', PARAM_URL);

$attempt = $DB->get_record('adaptivequiz_attempt', ['id' => $attemptid], '*', MUST_EXIST);
$adaptivequiz = $DB->get_record('adaptivequiz', ['id' => $attempt->instance], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id, $adaptivequiz->course, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $adaptivequiz->course], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

require_capability('mod/adaptivequiz:viewreport', $context);

$user = $DB->get_record('user', ['id' => $attempt->userid], '*', MUST_EXIST);

$PAGE->set_url('/mod/adaptivequiz/delattempt.php', ['attempt' => $attempt->id]);
$PAGE->set_title(format_string($adaptivequiz->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$a = new stdClass();
$a->name = fullname($user);
$a->timecompleted = userdate($attempt->timemodified);

$customcatmodelinuse = !empty($adaptivequiz->catmodel);

// Handle the case when a custom CAT model is in use, but return URL wasn't provided. Redirect to the plugin's view page then.
if ($customcatmodelinuse && !$returnurl) {
    $returnurl = new moodle_url('/mod/adaptivequiz/view.php', ['id' => $cm->id]);
}

if (!$customcatmodelinuse) {
    $returnurl = new moodle_url('/mod/adaptivequiz/viewattemptreport.php', ['cmid' => $cm->id, 'userid' => $user->id]);
}

if ($confirm) {
    question_engine::delete_questions_usage_by_activity($attempt->uniqueid);
    $DB->delete_records('adaptivequiz_attempt', ['id' => $attempt->id]);

    // Allow sub-plugins to run their code when relevant.
    if ($customcatmodelinuse) {
        $catmodelcomponentname = 'adaptivequizcatmodel_' . $adaptivequiz->catmodel;
        $pluginswithfunction = get_plugin_list_with_function('adaptivequizcatmodel', 'post_delete_attempt_callback');
        if (!array_key_exists($catmodelcomponentname, $pluginswithfunction)) {
            return;
        }

        $functionname = $pluginswithfunction[$catmodelcomponentname];
        $functionname($adaptivequiz, $attempt);
    }

    adaptivequiz_update_grades($adaptivequiz, $user->id);

    $message = get_string('attemptdeleted', 'adaptivequiz', $a);
    redirect($returnurl, $message, 4);
}

$message = get_string('confirmdeleteattempt', 'adaptivequiz', $a);

$confirm = new moodle_url('/mod/adaptivequiz/delattempt.php',
    ['attempt' => $attempt->id, 'confirm' => 1, 'return' => $returnurl]);

echo $OUTPUT->header();
echo $OUTPUT->confirm($message, $confirm, $returnurl);
echo $OUTPUT->footer();
