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

namespace mod_adaptivequiz\local\itemadministration;

use Exception;
use mod_adaptivequiz\local\attempt\attempt;
use mod_adaptivequiz\local\attempt\cat_calculation_steps_result;
use mod_adaptivequiz\local\attempt\cat_model_params;
use mod_adaptivequiz\local\catalgorithm\catalgo;
use mod_adaptivequiz\local\catalgorithm\difficulty_logit;
use mod_adaptivequiz\local\fetchquestion;
use mod_adaptivequiz\local\question\question_answer_evaluation_result;
use mod_adaptivequiz\local\question\questions_answered_summary_provider;
use mod_adaptivequiz\local\report\questions_difficulty_range;
use moodle_exception;
use moodle_url;
use question_bank;
use question_engine;
use question_state_gaveup;
use question_state_gradedpartial;
use question_state_gradedright;
use question_state_gradedwrong;
use question_state_todo;
use question_usage_by_activity;
use stdClass;

/**
 * The class is responsible for administering an item (a question) during a CAT session.
 *
 * At first step this is mainly extraction of some code from the irrelevant class.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_administration {

    /**
     * @var question_usage_by_activity $quba
     */
    private $quba;

    /**
     * @var catalgo $algorithm
     */
    private $algorithm;

    /**
     * @var fetchquestion $fetchquestion
     */
    private $fetchquestion;

    /**
     * The constructor.
     *
     * @param question_usage_by_activity $quba
     * @param catalgo $algorithm
     * @param fetchquestion $fetchquestion
     */
    public function __construct(question_usage_by_activity $quba, catalgo $algorithm, fetchquestion $fetchquestion) {
        $this->quba = $quba;
        $this->algorithm = $algorithm;
        $this->fetchquestion = $fetchquestion;
    }

    /**
     * Assesses the ability to administer next question during the quiz.
     *
     * @param attempt $attempt
     * @param stdClass $adaptivequiz
     * @param int $lastdifficultylevel The last difficulty level used in the attempt.
     * @param question_answer_evaluation_result|null $questionanswerevaluationresult
     * @return item_administration_evaluation
     * @throws moodle_exception
     */
    public function evaluate_ability_to_administer_next_item(
        attempt $attempt,
        stdClass $adaptivequiz,
        int $lastdifficultylevel,
        ?question_answer_evaluation_result $questionanswerevaluationresult
    ): item_administration_evaluation {
        $questionsattempted = $attempt->read_attempt_data()->questionsattempted;

        $determinenextdifficultyresult = null;
        if (!is_null($questionanswerevaluationresult)) {
            $catmodelparams = cat_model_params::for_attempt($attempt->read_attempt_data()->id);

            $questionsdifficultyrange = questions_difficulty_range::from_activity_instance($adaptivequiz);

            $answersummary = (new questions_answered_summary_provider($this->quba))->collect_summary();

            // Map the linear scale to a logarithmic logit scale.
            $logit = catalgo::convert_linear_to_logit($lastdifficultylevel, $questionsdifficultyrange);

            $standarderror = catalgo::estimate_standard_error($questionsattempted + 1, $answersummary->number_of_correct_answers(),
                $answersummary->number_of_wrong_answers());

            $measure = catalgo::estimate_measure(
                difficulty_logit::from_float($catmodelparams->get('difficultysum'))
                    ->summed_with_another_logit(difficulty_logit::from_float($logit))->as_float(),
                $questionsattempted + 1,
                $answersummary->number_of_correct_answers(), $answersummary->number_of_wrong_answers());

            try {
                $attempt->update_after_question_answered(time());
                $catmodelparams->update_with_calculation_steps_result(
                    cat_calculation_steps_result::from_floats($logit, $standarderror, $measure)
                );
            } catch (Exception $exception) {
                $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id, 0, false, MUST_EXIST);

                throw new moodle_exception('unableupdatediffsum', 'adaptivequiz',
                    new moodle_url('/mod/adaptivequiz/attempt.php', ['cmid' => $cm->id]));
            }

            // Determine the next difficulty level or whether there is an error.
            $determinenextdifficultyresult = $this->algorithm->determine_next_difficulty_level(
                $questionsattempted + 1,
                $questionsdifficultyrange,
                $adaptivequiz->standarderror,
                $questionanswerevaluationresult,
                $answersummary,
                $logit,
                $standarderror
            );

            $questionsattempted++;
        }

        if (!is_null($determinenextdifficultyresult)) {
            if ($determinenextdifficultyresult->is_with_error()) {
                return item_administration_evaluation::with_stoppage_reason($determinenextdifficultyresult->error_message());
            }

            // An answer was submitted, decrement the sum of questions for the attempted difficulty level.
            fetchquestion::decrement_question_sum_for_difficulty_level($lastdifficultylevel);
        }

        $nextdifficultylevel = is_null($determinenextdifficultyresult)
            ? $this->get_next_difficulty_level_from_quba(
                $adaptivequiz->startinglevel,
                questions_difficulty_range::from_activity_instance($adaptivequiz)
            )
            : $determinenextdifficultyresult->next_difficulty_level();

        // Check if the level requested is out of the minimum/maximum boundaries for the attempt.
        // Note: this seems to be unreachable, as the algorithm seems to never produce difficulty which exceeds the maximum.
        if (!$this->level_in_bounds($nextdifficultylevel, $adaptivequiz)) {

            return item_administration_evaluation::with_stoppage_reason(
                get_string('leveloutofbounds', 'adaptivequiz', $nextdifficultylevel)
            );
        }

        // Check if the attempt has reached the maximum number of questions attempted.
        if ($questionsattempted >= $adaptivequiz->maximumquestions) {

            return item_administration_evaluation::with_stoppage_reason(get_string('maxquestattempted', 'adaptivequiz'));
        }

        // Find the last question viewed/answered by the user.
        // The last slot in the array should be the last question that was attempted (meaning it was either shown to the user
        // or the user submitted an answer to it).
        $questionslots = $this->quba->get_slots();
        $slot = !empty($questionslots) ? end($questionslots) : 0;

        // Check if this is the beginning of an attempt (and pass the starting level) or the continuation of an attempt.
        if (empty($slot) && 0 == $questionsattempted) {
            // Set the starting difficulty level.
            $this->fetchquestion->set_level((int) $adaptivequiz->startinglevel);
            // Sets the level class property.
            $nextdifficultylevel = $adaptivequiz->startinglevel;
            // Set the rebuild flag for fetchquestion class.
            $this->fetchquestion->rebuild = true;

        } else if (!empty($slot) && $this->was_answer_submitted_to_question($slot)) {
            // If the attempt already has a question attached to it, check if an answer was submitted to the question.
            // If so fetch a new question.

            // Provide the question-fetching process with limits based on our last question.
            // If the last question was correct...
            if ($this->quba->get_question_mark($slot) > 0) {
                // Only ask questions harder than the last question unless we are already at the top of the ability scale.
                if ($lastdifficultylevel < $adaptivequiz->highestlevel) {
                    $this->fetchquestion->set_minimum_level($lastdifficultylevel + 1);
                    // Do not ask a question of the same level unless we are already at the max.
                    if ($lastdifficultylevel == $nextdifficultylevel) {
                        $nextdifficultylevel++;
                    }
                }
            } else {
                // If the last question was wrong...
                // Only ask questions easier than the last question unless we are already at the bottom of the ability scale.
                if ($lastdifficultylevel > $adaptivequiz->lowestlevel) {
                    $this->fetchquestion->set_maximum_level($lastdifficultylevel - 1);
                    // Do not ask a question of the same level unless we are already at the min.
                    if ($lastdifficultylevel == $nextdifficultylevel) {
                        $nextdifficultylevel--;
                    }
                }
            }

            // Reset the slot number back to zero, since we are going to fetch a new question.
            $slot = 0;
            // Set the level of difficulty to fetch.
            $this->fetchquestion->set_level($nextdifficultylevel);

        } else if (empty($slot) && 0 < $questionsattempted) {

            // If this condition is met, then something went wrong because the slot id is empty BUT the questions attempted is
            // greater than zero. Stop the attempt.
            // Note: this is only possible when the database is severely out of sync. For example, an attempt has been updated
            // after answering a question, but quba data was not saved. Possibly, a coding exception should be thrown instead.
            return item_administration_evaluation::with_stoppage_reason(get_string('errorattemptstate', 'adaptivequiz'));
        }

        // If the slot property is set, then we have a question that is ready to be attempted.  No more process is required.
        if (!empty($slot)) {

            return item_administration_evaluation::with_next_item(new next_item($nextdifficultylevel, $slot));
        }

        // If we are here, then the slot property was unset and a new question needs to prepared for display.
        $status = $this->get_question_ready($attempt, $nextdifficultylevel);

        if (empty($status)) {

            return item_administration_evaluation::with_stoppage_reason(
                get_string('errorfetchingquest', 'adaptivequiz', $nextdifficultylevel)
            );
        }

        return $status;
    }

    /**
     * Gets standard error parameter from the algorithm.
     *
     * This method should not be there normally, it's used to support refactoring. Must be removed as soon as possible.
     *
     * @return float
     */
    public function standard_error_from_algorithm(): float {
        return $this->algorithm->get_standarderror();
    }

    /**
     * This function checks to see if the difficulty level is out of the boundaries set for the attempt.
     *
     * @param int $level The difficulty level requested.
     * @param stdClass $adaptivequiz An {adaptivequiz} record.
     * @return bool
     */
    private function level_in_bounds(int $level, stdClass $adaptivequiz): bool {
        if ($adaptivequiz->lowestlevel <= $level && $adaptivequiz->highestlevel >= $level) {
            return true;
        }

        return false;
    }

    /**
     * Determines if the user submitted an answer to the question.
     *
     * @param int $slot The question's slot.
     */
    private function was_answer_submitted_to_question(int $slot): bool {
        $state = $this->quba->get_question_state($slot);

        // Check if the state of the question attempted was graded right, partially right, wrong or gave up, count the question has
        // having an answer submitted.
        $marked = $state instanceof question_state_gradedright || $state instanceof question_state_gradedpartial
            || $state instanceof question_state_gradedwrong || $state instanceof question_state_gaveup;

        if ($marked) {
            return true;
        }

        return false;
    }

    /**
     * This function gets the question ready for display to the user.
     *
     * @param attempt $attempt
     * @param int $nextdifficultylevel
     * @return item_administration_evaluation
     */
    private function get_question_ready(attempt $attempt, int $nextdifficultylevel): item_administration_evaluation {
        global $DB;

        // Fetch questions already attempted.
        $exclude = $DB->get_records_menu('question_attempts', ['questionusageid' => $attempt->read_attempt_data()->uniqueid],
            'id ASC', 'id,questionid');
        // Fetch questions for display.
        $questionids = $this->fetchquestion->fetch_questions($exclude);

        if (empty($questionids)) {

            return item_administration_evaluation::with_stoppage_reason(
                get_string('errorfetchingquest', 'adaptivequiz', $nextdifficultylevel)
            );
        }

        // Select one random question.
        $questiontodisplay = array_rand($questionids);

        // Load basic question data.
        $questionobj = question_preload_questions(array($questiontodisplay));
        get_question_options($questionobj);

        // Make a copy of the array and pop off the first (and only) element (current() didn't work for some reason).
        $quest = $questionobj;
        $quest = array_pop($quest);

        // Create the question_definition object.
        $question = question_bank::load_question($quest->id);
        // Add the question to the usage question_usable_by_activity object.
        $slot = $this->quba->add_question($question);
        // Start the question attempt.
        $this->quba->start_question($slot);
        // Save the question usage and question attempt state to the DB.
        question_engine::save_questions_usage_by_activity($this->quba);
        // Update the attempt unique id.
        $attempt->set_quba_id($this->quba->get_id());

        // Set class level property to the difficulty level of the question returned from fetchquestion class.
        $nextdifficultylevel = $this->fetchquestion->get_level();

        return item_administration_evaluation::with_next_item(new next_item($nextdifficultylevel, $slot));
    }

    /**
     * Gets the next difficulty level based on previously answered questions.
     *
     * @param int $startinglevel
     * @param questions_difficulty_range $questionsdifficultyrange
     * @return int
     */
    private function get_next_difficulty_level_from_quba(
        int $startinglevel,
        questions_difficulty_range $questionsdifficultyrange
    ): int {
        $questattempted = 0;
        $currdiff = $startinglevel;

        // Get question slots for the attempt.
        $slots = $this->quba->get_slots();

        // Should not normally happen.
        if (empty($slots)) {
            return $startinglevel;
        }

        // Get the last question's state.
        $state = $this->quba->get_question_state(end($slots));
        // If the state of the last question in the attempt is 'todo' remove it from the array, as the user never submitted their
        // answer.
        if ($state instanceof question_state_todo) {
            array_pop($slots);
        }

        // Reset the array pointer back to the beginning.
        reset($slots);

        $algo = new catalgo(false);

        // Iterate over slots and count correct answers.
        foreach ($slots as $slot) {
            $mark = $this->quba->get_question_mark($slot);

            if (is_null($mark) || 0.0 >= $mark) {
                $correct = false;
            } else {
                $correct = true;
            }

            $questattempted++;

            $logit = catalgo::convert_linear_to_logit($currdiff, $questionsdifficultyrange);
            $currdiff = $algo->compute_next_difficulty($questattempted, $correct, $questionsdifficultyrange, $logit);
        }

        return $currdiff;
    }
}
