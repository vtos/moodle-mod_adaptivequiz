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
 * Adaptive quiz view attempt report script.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');

use mod_adaptivequiz\local\report\individual_user_attempts\filter as user_attempts_table;
use mod_adaptivequiz\local\report\individual_user_attempts\table as individual_user_attempts_table;
use mod_adaptivequiz\local\report\questions_difficulty_range;
use mod_adaptivequiz\local\report\users_attempts\filter\filter;
use mod_adaptivequiz\local\report\users_attempts\filter\filter_form;
use mod_adaptivequiz\local\report\users_attempts\filter\filter_options;
use mod_adaptivequiz\local\report\users_attempts\user_preferences\filter_user_preferences;
use mod_adaptivequiz\local\report\users_attempts\user_preferences\user_preferences;
use mod_adaptivequiz\local\report\users_attempts\user_preferences\user_preferences_form;
use mod_adaptivequiz\local\report\users_attempts\user_preferences\user_preferences_repository;
use mod_adaptivequiz\local\report\users_attempts\users_attempts_table;

$id = required_param('id', PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$downloadusersattempts = optional_param('download', '', PARAM_ALPHA);
$resetfilter = optional_param('resetfilter', 0, PARAM_INT);

$cm = get_coursemodule_from_id('adaptivequiz', $id, 0, false, MUST_EXIST);
$adaptivequiz = $DB->get_record('adaptivequiz', ['id' => $cm->instance], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

require_capability('mod/adaptivequiz:viewreport', $context);

$PAGE->set_context($context);
$PAGE->set_url('/mod/adaptivequiz/viewattemptreport.php', ['id' => $cm->id, 'userid' => $userid]);

/** @var mod_adaptivequiz_renderer $renderer */
$renderer = $PAGE->get_renderer('mod_adaptivequiz');

if (!$userid) {
    $reportuserprefs = user_preferences_repository::get();

    $reportuserprefsform = new user_preferences_form($PAGE->url->out());
    if ($prefsformdata = $reportuserprefsform->get_data()) {
        $reportuserprefs = user_preferences::from_plain_object($prefsformdata);

        if (!$reportuserprefs->persistent_filter() && $reportuserprefs->has_filter_preference()) {
            $reportuserprefs = $reportuserprefs->without_filter_preference();
        }

        user_preferences_repository::save($reportuserprefs);
    }
    $reportuserprefsform->set_data($reportuserprefs->as_array());

    $filter = filter::from_vars($adaptivequiz->id, groups_get_activity_group($cm, true));
    if ($resetfilter) {
        $filter->fill_from_array(['users' => filter_options::users_option_default(),
            'includeinactiveenrolments' => filter_options::INCLUDE_INACTIVE_ENROLMENTS_DEFAULT]);
    }

    $reportfilterform = new filter_form($PAGE->url->out(), ['actionurl' => $PAGE->url]);
    if ($reportuserprefs->persistent_filter() && $reportuserprefs->has_filter_preference()) {
        $filter->fill_from_preference($reportuserprefs->filter());
        $reportfilterform->set_data($reportuserprefs->filter()->as_array());
    }
    if ($resetfilter) {
        $filterdefaultsarray = ['users' => filter_options::users_option_default(),
            'includeinactiveenrolments' => filter_options::INCLUDE_INACTIVE_ENROLMENTS_DEFAULT];

        $filter->fill_from_array($filterdefaultsarray);

        if ($reportuserprefs->persistent_filter()) {
            user_preferences_repository::save(
                $reportuserprefs->with_filter_preference(filter_user_preferences::from_array($filterdefaultsarray))
            );
        }

        $reportfilterform->set_data($filterdefaultsarray);
    }
    if ($filterformdata = $reportfilterform->get_data()) {
        $filter->fill_from_array((array) $filterformdata);

        if ($reportuserprefs->persistent_filter()) {
            user_preferences_repository::save(
                $reportuserprefs->with_filter_preference(filter_user_preferences::from_array((array) $filterformdata))
            );
        }
    }

    $attemptsreporttable = new users_attempts_table($renderer, $cm->id,
        questions_difficulty_range::from_activity_instance($adaptivequiz), $PAGE->url, $context, $filter);
    $attemptsreporttable->is_downloading($downloadusersattempts,
        get_string('reportattemptsdownloadfilename', 'adaptivequiz', format_string($adaptivequiz->name)));
    if ($attemptsreporttable->is_downloading()) {
        $attemptsreporttable->out(1, false);
        exit;
    }
}

$title = get_string('activityreports', 'adaptivequiz');

if ($userid) {
    $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
    $title = get_string('reportindividualuserattemptpageheading', 'adaptivequiz', fullname($user));
}

$PAGE->set_title($title);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->activityheader->disable();

echo $renderer->header();
echo $renderer->heading($title);

if ($userid) {
    $attemptstable = new individual_user_attempts_table(
        $renderer,
        user_attempts_table::from_vars($cm->instance, $user->id),
        $PAGE->url,
        questions_difficulty_range::from_activity_instance($adaptivequiz)
    );
    $attemptstable->out(20, false);

    echo $renderer->footer();
    exit;
}

groups_print_activity_menu($cm, new moodle_url('/mod/adaptivequiz/view.php', ['id' => $cm->id]));

echo $renderer->container_start('usersattemptstable-wrapper');
$attemptsreporttable->out($reportuserprefs->rows_per_page(), $reportuserprefs->show_initials_bar());
echo $renderer->container_end();

$reportuserprefsform->display();

$reportfilterform->display();

$resetfilterurl = $PAGE->url;
$resetfilterurl->param('resetfilter', 1);
echo $renderer->reset_users_attempts_filter_action($resetfilterurl);

echo $renderer->footer();
