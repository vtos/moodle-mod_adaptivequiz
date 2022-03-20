<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Adaptive testing main view page script
 *
 * @copyright  2013 Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once __DIR__ . '/../../config.php';
require_once $CFG->libdir . '/tablelib.php';
require_once $CFG->dirroot.'/mod/adaptivequiz/locallib.php';

use core\activity_dates;
use core_completion\cm_completion_details;
use mod_adaptivequiz\local\user_attempts_table;
use mod_adaptivequiz\output\user_attempt_summary;

$id = optional_param('id', 0, PARAM_INT);
$n  = optional_param('n', 0, PARAM_INT);

if ($id) {
    $cm         = get_coursemodule_from_id('adaptivequiz', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $adaptivequiz  = $DB->get_record('adaptivequiz', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($n) {
    $adaptivequiz  = $DB->get_record('adaptivequiz', ['id' => $n], '*', MUST_EXIST);
    $course     = $DB->get_record('course', ['id' => $adaptivequiz->course], '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id, $course->id, false, MUST_EXIST);
} else {
    print_error('invalidarguments');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$event = \mod_adaptivequiz\event\course_module_viewed::create(
    [
        'objectid' => $PAGE->cm->instance,
        'context' => $PAGE->context,
    ]
);

$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $adaptivequiz);
$event->trigger();

$PAGE->set_url('/mod/adaptivequiz/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($adaptivequiz->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

/** @var mod_adaptivequiz_renderer $renderer */
$renderer = $PAGE->get_renderer('mod_adaptivequiz');

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($adaptivequiz->name));

$cminfo = cm_info::create($cm);
$completiondetails = cm_completion_details::get_instance($cminfo, $USER->id);
$activitydates = activity_dates::get_dates_for_module($cminfo, $USER->id);
echo $OUTPUT->activity_information($cminfo, $completiondetails, $activitydates);

if ($adaptivequiz->intro) { // Conditions to show the intro can change to look for own settings or whatever.
    echo $OUTPUT->box(format_module_intro('adaptivequiz', $adaptivequiz, $cm->id), 'generalbox mod_introbox', 'newmoduleintro');
}

if (has_capability('mod/adaptivequiz:attempt', $context)) {

    $allattemptscount = $DB->count_records('adaptivequiz_attempt',
        ['instance' => $adaptivequiz->id, 'userid' => $USER->id]);
    if ($allattemptscount && $adaptivequiz->attempts == 1) {
        $sql = 'SELECT id, attemptstate, measure, timemodified
            FROM {adaptivequiz_attempt}
            WHERE instance = ? AND userid = ?
            ORDER BY timemodified DESC';
        if ($userattempts = $DB->get_records_sql($sql, [$adaptivequiz->id, $USER->id], 0,1)) {
            $userattempt = $userattempts[array_key_first($userattempts)];

            echo $renderer->heading(get_string('attempt_summary', 'adaptivequiz'), 3);
            echo $renderer->render(user_attempt_summary::from_db_records($userattempt, $adaptivequiz));
        }
    }
    if ($allattemptscount && $adaptivequiz->attempts != 1) {
        echo $renderer->heading(get_string('attemptsuserprevious', 'adaptivequiz'), 3);

        $attemptstable = new user_attempts_table($renderer);
        $attemptstable->init($PAGE->url, $adaptivequiz, $USER->id);
        $attemptstable->out(5, true);
    }
    if (!$allattemptscount) {
        echo html_writer::div(get_string('attemptsusernoprevious', 'adaptivequiz'), 'alert alert-info text-center');
    }

    $completedattemptscount = adaptivequiz_count_user_previous_attempts($adaptivequiz->id, $USER->id);
    if (adaptivequiz_allowed_attempt($adaptivequiz->attempts, $completedattemptscount)) {
        if (empty($adaptivequiz->browsersecurity)) {
            echo $renderer->display_start_attempt_form($cm->id);
        } else {
            echo $renderer->display_start_attempt_form_scured($cm->id);
        }
    } else {
        echo html_writer::div(get_string('noattemptsallowed', 'adaptivequiz'), 'alert alert-info text-center');
    }
}

if (has_capability('mod/adaptivequiz:viewreport', $context)) {
    echo $renderer->display_view_report_form($cm->id);
    echo $renderer->display_question_analysis_form($cm->id);
}

echo $OUTPUT->footer();
