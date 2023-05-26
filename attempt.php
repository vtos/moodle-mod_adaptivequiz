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
use mod_adaptivequiz\local\attempt\cat_model_params;
use mod_adaptivequiz\local\catalgorithm\catalgo;
use mod_adaptivequiz\local\catalgorithm\difficulty_logit;
use mod_adaptivequiz\local\fetchquestion;
use mod_adaptivequiz\local\itemadministration\item_administration;
use mod_adaptivequiz\local\question\question_answer_evaluation;
use mod_adaptivequiz\local\question\questions_answered_summary_provider;
use mod_adaptivequiz\local\report\questions_difficulty_range;

$id = required_param('cmid', PARAM_INT); // Course module id.
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
    cat_model_params::create_new_for_attempt($adaptiveattempt->read_attempt_data()->id);
}

$standarderror = 0.0;

if (!empty($attempteddifficultylevel) && confirm_sesskey()) {
    // Process student's responses.
    $time = time();
    $quba = question_engine::load_questions_usage_by_activity($adaptiveattempt->read_attempt_data()->uniqueid);
    $quba->process_all_actions($time);
    $quba->finish_all_questions($time);
    question_engine::save_questions_usage_by_activity($quba);

    $catmodelparams = cat_model_params::for_attempt($adaptiveattempt->read_attempt_data()->id);

    $questionsdifficultyrange = questions_difficulty_range::from_activity_instance($adaptivequiz);

    $answersummary = (new questions_answered_summary_provider($quba))->collect_summary();

    // Map the linear scale to a logarithmic logit scale.
    $logit = catalgo::convert_linear_to_logit($attempteddifficultylevel, $questionsdifficultyrange);

    $questionsattempted = $adaptiveattempt->read_attempt_data()->questionsattempted + 1;
    $standarderror = catalgo::estimate_standard_error($questionsattempted, $answersummary->number_of_correct_answers(),
        $answersummary->number_of_wrong_answers());

    $measure = catalgo::estimate_measure(
        difficulty_logit::from_float($catmodelparams->get('difficultysum'))
            ->summed_with_another_logit(difficulty_logit::from_float($logit))->as_float(),
        $questionsattempted,
        $answersummary->number_of_correct_answers(), $answersummary->number_of_wrong_answers());

    try {
        $adaptiveattempt->update_after_question_answered(time());
        $catmodelparams->update_with_calculation_steps_result(
            cat_calculation_steps_result::from_floats($logit, $standarderror, $measure)
        );
    } catch (Exception $exception) {
        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id, 0, false, MUST_EXIST);

        throw new moodle_exception('unableupdatediffsum', 'adaptivequiz',
            new moodle_url('/mod/adaptivequiz/attempt.php', ['cmid' => $cm->id]));
    }

    // An answer was submitted, decrement the sum of questions for the attempted difficulty level.
    fetchquestion::decrement_question_sum_for_difficulty_level($attempteddifficultylevel);

    redirect(new moodle_url('/mod/adaptivequiz/attempt.php', ['cmid' => $cm->id]));
}

// Initialize quba.
$qubaid = $adaptiveattempt->read_attempt_data()->uniqueid;
$quba = ($qubaid == 0)
    ? question_engine::make_questions_usage_by_activity('mod_adaptivequiz', $context)
    : question_engine::load_questions_usage_by_activity($qubaid);
if ($qubaid == 0) {
    $quba->set_preferred_behaviour(attempt::ATTEMPTBEHAVIOUR);
}

$questionanswerevaluation = new question_answer_evaluation($quba);
$questionanswerevaluationresult = $questionanswerevaluation->perform();

$adaptivequiz->context = $context;
$adaptivequiz->cm = $cm;

$minattemptreached = adaptivequiz_min_number_of_questions_reached($adaptiveattempt->read_attempt_data()->id, $cm->instance,
    $USER->id);

$algorithm = new catalgo($minattemptreached);
$fetchquestion = new fetchquestion($adaptivequiz, 1, $adaptivequiz->lowestlevel, $adaptivequiz->highestlevel);

$itemadministration = new item_administration($quba, $algorithm, $fetchquestion);
$itemadministrationevaluation = $itemadministration->evaluate_ability_to_administer_next_item($adaptiveattempt, $adaptivequiz,
    $questionanswerevaluationresult);

// Check item administration evaluation.
if ($itemadministrationevaluation->item_administration_is_to_stop()) {
    // Set the attempt to complete, update the standard error and attempt message, then redirect the user to the attempt-finished
    // page.
    $adaptiveattempt->complete($context, $itemadministrationevaluation->stoppage_reason(), time());
    cat_model_params::for_attempt($adaptiveattempt->read_attempt_data()->id)
        ->update_when_attempt_completed($itemadministration->standard_error_from_algorithm());

    redirect(new moodle_url('/mod/adaptivequiz/attemptfinished.php',
        ['attempt' => $adaptiveattempt->read_attempt_data()->id, 'instance' => $adaptivequiz->id]));
}

// Retrieve the question slot id.
$slot = $itemadministrationevaluation->next_item()->slot();

$level = $itemadministrationevaluation->next_item()->difficulty_level();

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
