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
 * Adaptive quiz attempt script
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/adaptivequiz/locallib.php');

$cmid = required_param('cmid', PARAM_INT); // Course module id.
$instance = required_param('id', PARAM_INT); // Activity instance id.
$uniqueid = required_param('uattid', PARAM_INT); // Attempt unique id.

if (!$cm = get_coursemodule_from_id('adaptivequiz', $cmid)) {
    throw new moodle_exception('invalidcoursemodule');
}
if (!$course = $DB->get_record('course', ['id' => $cm->course])) {
    throw new moodle_exception('coursemisconf');
}

$adaptivequiz = $DB->get_record('adaptivequiz', ['id' => $cm->instance], '*', MUST_EXIST);
$attempt = $DB->get_record('adaptivequiz_attempt', ['uniqueid' => $uniqueid], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// TODO - check if user has capability to attempt.

// Check if this is the owner of the attempt.
$validattempt = adaptivequiz_uniqueid_part_of_attempt($uniqueid, $instance, $USER->id);

// Display an error message if this is not the owner of the attempt.
if (!$validattempt) {
    $url = new moodle_url('/mod/adaptivequiz/attempt.php', ['cmid' => $cm->id]);
    throw new moodle_exception('notyourattempt', 'adaptivequiz', $url);
}

$PAGE->set_url('/mod/adaptivequiz/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($adaptivequiz->name));
$PAGE->set_context($context);
$PAGE->activityheader->disable();
$PAGE->add_body_class('limitedwidth');

$output = $PAGE->get_renderer('mod_adaptivequiz');

// Display page as a 'secure' window if enabled.
if ($adaptivequiz->browsersecurity) {
    $PAGE->blocks->show_only_fake_blocks();
    $output->init_browser_security(false);

    echo $output->header();
    echo $output->attempt_finished_page($adaptivequiz, $cm, $attempt);

    $PAGE->requires->js_init_call('M.mod_adaptivequiz.secure_window.init_close_button',
        [new moodle_url('/mod/adaptivequiz/view.php', ['id' => $cm->id])], $output->adaptivequiz_get_js_module());

    echo $output->footer();
    exit;
}

$PAGE->set_heading(format_string($course->fullname));

echo $output->header();
echo $output->attempt_finished_page($adaptivequiz, $cm, $attempt);
echo $output->footer();
