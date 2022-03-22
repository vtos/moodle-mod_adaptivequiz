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
 * Adaptive quiz view attempted questions
 *
 * @copyright  2013 Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once __DIR__ . '/../../config.php';
require_once $CFG->dirroot . '/tag/lib.php';
require_once $CFG->dirroot . '/mod/adaptivequiz/locallib.php';

$id = required_param('cmid', PARAM_INT);
$uniqueid = required_param('uniqueid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$tab = optional_param('tab', 'attemptsummary', PARAM_ALPHA);

if (!$cm = get_coursemodule_from_id('adaptivequiz', $id)) {
    print_error('invalidcoursemodule');
}
if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error("coursemisconf");
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

require_capability('mod/adaptivequiz:viewreport', $context);

$param = array('uniqueid' => $uniqueid, 'userid' => $userid, 'activityid' => $cm->instance);
$sql = 'SELECT a.name, a.highestlevel, a.lowestlevel, aa.timecreated, aa.timemodified, aa.attemptstate, aa.attemptstopcriteria,
               aa.questionsattempted, aa.difficultysum, aa.standarderror, aa.measure, aa.uniqueid
          FROM {adaptivequiz} a
          JOIN {adaptivequiz_attempt} aa ON a.id = aa.instance
         WHERE aa.uniqueid = :uniqueid
               AND aa.userid = :userid
               AND a.id = :activityid
      ORDER BY a.name ASC';
$adaptivequiz  = $DB->get_record_sql($sql, $param);

$quba = question_engine::load_questions_usage_by_activity($uniqueid);

$user = $DB->get_record('user', ['id' => $userid]);
if (!$user) {
    $user = new stdClass();
    $user->firstname = get_string('unknownuser', 'adaptivequiz');
    $user->lastname = '#'.$userid;
}

$a = new stdClass();
$a->quizname = format_string($adaptivequiz->name);
$a->fullname = fullname($user);
$a->finished = userdate($adaptivequiz->timemodified);
$title = get_string('reviewattemptreport', 'adaptivequiz', $a);

$PAGE->set_url('/mod/adaptivequiz/reviewattempt.php',
    ['cmid' => $cm->id, 'uniqueid' => $uniqueid, 'userid' => $userid]);
$PAGE->set_title($title);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->navbar->add(get_string('reports'));
$PAGE->navbar->add(get_string('reportuserattemptstitleshort', 'adaptivequiz', fullname($user)),
    new moodle_url('/mod/adaptivequiz/viewattemptreport.php', ['userid' => $user->id, 'cmid' => $cm->id]));
$PAGE->navbar->add(get_string('reviewattempt', 'adaptivequiz'));

$output = $PAGE->get_renderer('mod_adaptivequiz');

$PAGE->requires->js_init_call('M.mod_adaptivequiz.init_reviewattempt', null, false,
    $output->adaptivequiz_get_js_module());

echo $output->print_header();
echo $output->heading($title);

echo $output->attempt_review_tabs($PAGE->url, $tab);
echo $output->attempt_report_page_by_tab($tab, $adaptivequiz, $user, $quba, $cm->id, $page);

echo $output->print_footer();
