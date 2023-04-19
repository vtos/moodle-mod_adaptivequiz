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
 * Adaptive quiz attempt script.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/adaptivequiz/locallib.php');
require_once($CFG->dirroot . '/tag/lib.php');

use mod_adaptivequiz\local\adaptive_quiz_requires;
use mod_adaptivequiz\local\attempt\attempt;
use mod_adaptivequiz\local\attempt\cat_calculation_steps_result;
use mod_adaptivequiz\local\catalgorithm\catalgo;
use mod_adaptivequiz\local\fetchquestion;
use mod_adaptivequiz\local\itemadministration\item_administration;
use mod_adaptivequiz\local\question\question_answer_evaluation;
use mod_adaptivequiz\local\question\questions_answered_summary_provider;
use mod_adaptivequiz\local\report\questions_difficulty_range;

$id = required_param('cmid', PARAM_INT); // Course module id.
$uniqueid  = optional_param('uniqueid', 0, PARAM_INT);  // Unique id of the attempt.
$attempteddifficultylevel  = optional_param('dl', 0, PARAM_INT);

if (!$cm = get_coursemodule_from_id('adaptivequiz', $id)) {
    throw new moodle_exception('invalidcoursemodule');
}
if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    throw new moodle_exception('coursemisconf');
}

global $USER, $DB, $SESSION;

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
$passwordattempt = false;

try {
    $adaptivequiz  = $DB->get_record('adaptivequiz', array('id' => $cm->instance), '*', MUST_EXIST);
} catch (dml_exception $e) {
    $url = new moodle_url('/mod/adaptivequiz/attempt.php', array('cmid' => $id));
    $debuginfo = '';

    if (!empty($e->debuginfo)) {
        $debuginfo = $e->debuginfo;
    }

    throw new moodle_exception('invalidmodule', 'error', $url, $e->getMessage(), $debuginfo);
}

// Setup page global for standard viewing.
$viewurl = new moodle_url('/mod/adaptivequiz/view.php', array('id' => $cm->id));
$PAGE->set_url('/mod/adaptivequiz/view.php', array('cmid' => $cm->id));
$PAGE->set_title(format_string($adaptivequiz->name));
$PAGE->set_context($context);
$PAGE->activityheader->disable();
$PAGE->add_body_class('limitedwidth');

// Check if the user has the attempt capability.
require_capability('mod/adaptivequiz:attempt', $context);

try {
    (new adaptive_quiz_requires())
        ->deferred_feedback_question_behaviour_is_enabled();
} catch (moodle_exception $activityavailabilityexception) {
    throw new moodle_exception(
        'activityavailabilitystudentnotification',
        'adaptivequiz',
        new moodle_url('/mod/adaptivequiz/view.php', ['id' => $cm->id])
    );
}

// Check if the user has any previous attempts at this activity.
$count = adaptivequiz_count_user_previous_attempts($adaptivequiz->id, $USER->id);

if (!adaptivequiz_allowed_attempt($adaptivequiz->attempts, $count)) {
    throw new moodle_exception('noattemptsallowed', 'adaptivequiz');
}

// Create an instance of the module renderer class.
$output = $PAGE->get_renderer('mod_adaptivequiz');
// Setup password required form.
$mform = $output->display_password_form($cm->id);
// Check if a password is required.
if (!empty($adaptivequiz->password)) {
    // Check if the user has alredy entered in their password.
    $condition = adaptivequiz_user_entered_password($adaptivequiz->id);

    if (empty($condition) && $mform->is_cancelled()) {
        // Return user to landing page.
        redirect($viewurl);
    } else if (empty($condition) && $data = $mform->get_data()) {
        $SESSION->passwordcheckedadpq = array();

        if (0 == strcmp($data->quizpassword, $adaptivequiz->password)) {
            $SESSION->passwordcheckedadpq[$adaptivequiz->id] = true;
        } else {
            $SESSION->passwordcheckedadpq[$adaptivequiz->id] = false;
            $passwordattempt = true;
        }
    }
}

$adaptiveattempt = attempt::find_in_progress_for_user($adaptivequiz, $USER->id);
if ($adaptiveattempt === null) {
    $adaptiveattempt = attempt::create($adaptivequiz, $USER->id);
}

$algo = new stdClass();
$nextdifficultylevel = null;
$standarderror = 0.0;

// If uniqueid is not empty the process respones.
if (!empty($uniqueid) && confirm_sesskey()) {
    // Check if the uniqueid belongs to the same attempt record the user is currently using.
    if (!adaptivequiz_uniqueid_part_of_attempt($uniqueid, $cm->instance, $USER->id)) {
        throw new moodle_exception('uniquenotpartofattempt', 'adaptivequiz');
    }

    // Process student's responses.
    try {
        // Set a time stamp for the actions below.
        $time = time();
        // Load the user's current usage from the DB.
        $quba = question_engine::load_questions_usage_by_activity((int) $uniqueid);
        // Update the actions done to the question.
        $quba->process_all_actions($time);
        // Finish the grade attempt at the question.
        $quba->finish_all_questions($time);
        // Save the data about the usage to the DB.
        question_engine::save_questions_usage_by_activity($quba);

        if (!empty($attempteddifficultylevel)) {
            // Check if the minimum number of attempts have been reached.
            $minattemptreached = adaptivequiz_min_attempts_reached($uniqueid, $cm->instance, $USER->id);

            // Create an instance of the CAT algo class.
            $algo = new catalgo($minattemptreached, (int) $attempteddifficultylevel);

            $questionanswerevaluation = new question_answer_evaluation($quba);
            $questionanswerevaluationresult = $questionanswerevaluation->perform();

            // Determine the next difficulty level or whether there is an error.
            $determinenextdifficultylevelresult = $algo->determine_next_difficulty_level(
                (float) $adaptiveattempt->read_attempt_data()->difficultysum,
                (int) $adaptiveattempt->read_attempt_data()->questionsattempted,
                questions_difficulty_range::from_activity_instance($adaptivequiz),
                (float) $adaptivequiz->standarderror,
                $questionanswerevaluationresult,
                (new questions_answered_summary_provider($quba))->collect_summary()
            );
            $nextdifficultylevel = $determinenextdifficultylevelresult->next_difficulty_level();

            // Increment difficulty level for attempt.
            $difflogit = $algo->get_levellogit();
            if (is_infinite($difflogit)) {
                throw new moodle_exception('unableupdatediffsum', 'adaptivequiz',
                    new moodle_url('/mod/adaptivequiz/attempt.php', ['cmid' => $id]));
            }

            $standarderror = $algo->get_standarderror();

            try {
                $catcalculationresult = cat_calculation_steps_result::from_floats($difflogit, $standarderror, $algo->get_measure());
                $adaptiveattempt->update_after_question_answered($catcalculationresult, time());
            } catch (Exception $exception) {
                throw new moodle_exception('unableupdatediffsum', 'adaptivequiz',
                    new moodle_url('/mod/adaptivequiz/attempt.php', ['cmid' => $id]));
            }

            if ($determinenextdifficultylevelresult->is_with_error()) {
                $adaptiveattempt->complete($context, $standarderror, $determinenextdifficultylevelresult->error_message(), time());

                redirect(new moodle_url('/mod/adaptivequiz/attemptfinished.php',
                    ['cmid' => $cm->id, 'id' => $cm->instance, 'uattid' => $uniqueid]));
            }

            // Lastly decrement the sum of questions for the attempted difficulty level.
            (new fetchquestion($adaptivequiz, $attempteddifficultylevel, $adaptivequiz->lowestlevel, $adaptivequiz->highestlevel))
                ->decrement_question_sum_for_difficulty_level($attempteddifficultylevel);
        }
    } catch (question_out_of_sequence_exception $e) {
        $url = new moodle_url('/mod/adaptivequiz/attempt.php', array('cmid' => $id));
        throw new moodle_exception('submissionoutofsequencefriendlymessage', 'question', $url);

    } catch (Exception $e) {
        $url = new moodle_url('/mod/adaptivequiz/attempt.php', array('cmid' => $id));
        $debuginfo = '';

        if (!empty($e->debuginfo)) {
            $debuginfo = $e->debuginfo;
        }

        throw new moodle_exception('errorprocessingresponses', 'question', $url, $e->getMessage(), $debuginfo);
    }
}

$adaptivequiz->context = $context;
$adaptivequiz->cm = $cm;

// If value is null then set the difficulty level to the starting level for the attempt.
if (is_null($nextdifficultylevel)) {
    $nextdifficultylevel = $adaptivequiz->startinglevel;
}

// Initialize quba.
$qubaid = $adaptiveattempt->read_attempt_data()->uniqueid;
$quba = ($qubaid == 0)
    ? question_engine::make_questions_usage_by_activity('mod_adaptivequiz', $context)
    : question_engine::load_questions_usage_by_activity($qubaid);
if ($qubaid == 0) {
    $quba->set_preferred_behaviour(attempt::ATTEMPTBEHAVIOUR);
}

$fetchquestion = new fetchquestion($adaptivequiz, 1, $adaptivequiz->lowestlevel, $adaptivequiz->highestlevel);

$itemadministration = new item_administration($quba, $fetchquestion);
$itemadministrationevaluation = $itemadministration->evaluate_ability_to_administer_next_item($adaptiveattempt, $adaptivequiz,
    $adaptiveattempt->read_attempt_data()->questionsattempted, $attempteddifficultylevel, $nextdifficultylevel);

// Check item administration evaluation.
if ($itemadministrationevaluation->item_administration_is_to_stop()) {
    // Set the attempt to complete, update the standard error and attempt message, then redirect the user to the attempt-finished
    // page.
    if ($algo instanceof catalgo) {
        $standarderror = $algo->get_standarderror();
    }

    $noquestionsfetchedforattempt = $uniqueid == 0;
    if ($noquestionsfetchedforattempt) {
        // The script will try to complete an 'empty' attempt as it couldn't fetch the first question for some reason.
        // This is an invalid behaviour, which could be caused by a misconfigured questions pool. Stop it here.
        throw new moodle_exception('attemptnofirstquestion', 'adaptivequiz',
            (new moodle_url('/mod/adaptivequiz/view.php', ['id' => $cm->id]))->out());
    }

    $adaptiveattempt->complete($context, $standarderror, $itemadministrationevaluation->stoppage_reason(), time());

    redirect(new moodle_url('/mod/adaptivequiz/attemptfinished.php',
        ['cmid' => $cm->id, 'id' => $cm->instance, 'uattid' => $uniqueid]));
}

// Retrieve the question slot id.
$slot = $itemadministrationevaluation->next_item()->slot();

// If $nextdifficultylevel is null then this is either a new attempt or a continuation of a previous attempt.
// Calculate the current difficulty level the attempt should be at.
if (is_null($nextdifficultylevel)) {
    // Calculate the current difficulty level.
    // Create an instance of the catalgo class, however constructor arguments are not important.
    $algo = new catalgo($quba, false, 1);
    $level = $algo->get_current_diff_level(
        $quba,
        (int) $adaptivequiz->startinglevel,
        questions_difficulty_range::from_activity_instance($adaptivequiz)
    );
} else {
    // Retrieve the currently set difficulty level.
    $level = $itemadministrationevaluation->next_item()->difficulty_level();
}

$headtags = $output->init_metadata($quba, $slot);
$PAGE->requires->js_init_call('M.mod_adaptivequiz.init_attempt_form', array($viewurl->out(), $adaptivequiz->browsersecurity),
    false, $output->adaptivequiz_get_js_module());

// Init secure window if enabled.
if (!empty($adaptivequiz->browsersecurity)) {
    $PAGE->blocks->show_only_fake_blocks();
    $output->init_browser_security();
} else {
    $PAGE->set_heading(format_string($course->fullname));
}

echo $output->header();

// Check if the user entered a password.
$condition = adaptivequiz_user_entered_password($adaptivequiz->id);

if (!empty($adaptivequiz->password) && empty($condition)) {
    if ($passwordattempt) {
        $mform->set_data(array('message' => get_string('wrongpassword', 'adaptivequiz')));
    }

    $mform->display();
} else {
    $attemptdata = $adaptiveattempt->read_attempt_data();

    if ($adaptivequiz->showattemptprogress) {
        echo $output->container_start('attempt-progress-container');
        echo $output->attempt_progress($attemptdata->questionsattempted, $adaptivequiz->maximumquestions);
        echo $output->container_end();
    }

    echo $output->question_submit_form($id, $quba, $slot, $level, $attemptdata->questionsattempted + 1);
}

echo $output->print_footer();
