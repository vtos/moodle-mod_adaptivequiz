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
 * Script which is run when the user was redirected from the attempt.php script to finish the attempt.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/adaptivequiz/locallib.php');

use mod_adaptivequiz\local\attempt\attempt;
use mod_adaptivequiz\local\attempt\cat_model_params;
use mod_adaptivequiz\output\ability_measure;

$attemptid = required_param('attempt', PARAM_INT);
$instanceid = required_param('instance', PARAM_INT);

$adaptivequiz  = $DB->get_record('adaptivequiz', array('id' => $instanceid), '*', MUST_EXIST);
$attempt = attempt::get_by_id($attemptid, $adaptivequiz);

if (!$cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id)) {
    throw new moodle_exception('invalidcoursemodule');
}
if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    throw new moodle_exception('coursemisconf');
}

$abilitymeasurerenderable = null;
if ($adaptivequiz->showabilitymeasure) {
    $abilitymeasurevalue = cat_model_params::for_attempt($attempt->read_attempt_data()->id)->get('measure');
    $abilitymeasurerenderable = ability_measure::of_attempt_on_adaptive_quiz($adaptivequiz, $abilitymeasurevalue);
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

require_capability('mod/adaptivequiz:attempt', $context);

$PAGE->set_url('/mod/adaptivequiz/attemptfinished.php', ['cmid' => $cm->id, 'id' => $cm->instance]);
$PAGE->set_title(format_string($adaptivequiz->name));
$PAGE->set_context($context);
$PAGE->activityheader->disable();
$PAGE->add_body_class('limitedwidth');

$output = $PAGE->get_renderer('mod_adaptivequiz');

// Init secure window if enabled.
$popup = false;
if (!empty($adaptivequiz->browsersecurity)) {
    $PAGE->blocks->show_only_fake_blocks();
    $output->init_browser_security(false);
    $popup = true;
} else {
    $PAGE->set_heading(format_string($course->fullname));
}

echo $output->header();
echo $output->attempt_feedback($adaptivequiz->attemptfeedback, $cm->id, $abilitymeasurerenderable, $popup);
echo $output->footer();
