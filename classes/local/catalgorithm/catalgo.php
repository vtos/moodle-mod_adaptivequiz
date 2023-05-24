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

namespace mod_adaptivequiz\local\catalgorithm;

use coding_exception;
use mod_adaptivequiz\local\question\question_answer_evaluation_result;
use mod_adaptivequiz\local\question\questions_answered_summary;
use mod_adaptivequiz\local\report\questions_difficulty_range;
use stdClass;

/**
 * This class performs the simple algorithm to determine the next level of difficulty a student should attempt.
 *
 * It also recommends whether the calculation has reached an acceptable level of error.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class catalgo {

    /** @var bool $readytostop flag to denote whether to assume the student has met the minimum requirements */
    protected $readytostop = true;

    /** @var int $nextdifficulty the next dificulty level to administer */
    protected $nextdifficulty = 0;

    /** @var float $measure the ability measure */
    protected $measure = 0.0;

    /** @var float $standarderror the standard error of the measure */
    protected $standarderror = 0.0;

    /**
     * The constructor.
     *
     * @param bool $readytostop True of the algo should assume the user has answered the minimum number of question and should
     *                          compare the results against the standard error.
     */
    public function __construct(bool $readytostop = true) {
        $this->readytostop = $readytostop;
    }

    /**
     * This function returns the $standarderror property.
     *
     * @return float retuns the $standarderror property
     */
    public function get_standarderror() {
        return $this->standarderror;
    }

    /**
     * This function returns the $measure property.
     *
     * @return float retuns the $measure property
     */
    public function get_measure() {
        return $this->measure;
    }

    /**
     * This function performs the different steps in the CAT simple algorithm.
     *
     * @param float $attemptdifficultysum
     * @param int $questionsattemptednum
     * @param questions_difficulty_range $questionsdifficultyrange
     * @param float $standarderrortostop
     * @param question_answer_evaluation_result $questionanswerevaluationresult
     * @param questions_answered_summary $answersummary
     * @param int $lastdifficultylevel
     * @return determine_next_difficulty_result
     * @throws coding_exception
     */
    public function determine_next_difficulty_level(
        float $attemptdifficultysum,
        int $questionsattemptednum,
        questions_difficulty_range $questionsdifficultyrange,
        float $standarderrortostop,
        question_answer_evaluation_result $questionanswerevaluationresult,
        questions_answered_summary $answersummary,
        int $lastdifficultylevel
    ): determine_next_difficulty_result {
        if (0 >= $lastdifficultylevel) {
            throw new coding_exception('last difficulty level must have a positive value');
        }

        if (!$questionanswerevaluationresult->answer_was_given()) {
            return determine_next_difficulty_result::with_error(get_string('errorlastattpquest', 'adaptivequiz'));
        }

        $questionsattemptednum++;

        $correct = $questionanswerevaluationresult->answer_is_correct();

        // Map the linear scale to a logarithmic logit scale.
        $logit = self::convert_linear_to_logit($lastdifficultylevel, $questionsdifficultyrange);

        $this->nextdifficulty = $this->compute_next_difficulty(
            $questionsattemptednum,
            $correct,
            $questionsdifficultyrange,
            $logit
        );

        // If he user hasn't met the minimum requirements to end the attempt, then return with the next difficulty level.
        if (empty($this->readytostop)) {
            return determine_next_difficulty_result::with_next_difficulty_level_determined($this->nextdifficulty);
        }

        // Test that the sum of incorrect and correct answers equal to the sum of question attempted.
        if ($answersummary->sum_of_answers() != $questionsattemptednum) {
            return determine_next_difficulty_result::with_error(get_string('errorsumrightwrong', 'adaptivequiz'));
        }

        $difficultysum = $attemptdifficultysum + $logit;

        $this->measure = self::estimate_measure($difficultysum, $questionsattemptednum,
            $answersummary->number_of_correct_answers(), $answersummary->number_of_wrong_answers());

        $this->standarderror = self::estimate_standard_error($questionsattemptednum, $answersummary->number_of_correct_answers(),
            $answersummary->number_of_wrong_answers());

        // Convert the standard error (as a percent) set for the activity into a decimal percent, then
        // convert it to a logit.
        $quizdefinederror = $standarderrortostop / 100;
        $quizdefinederror = self::convert_percent_to_logit($quizdefinederror);

        // If the calculated standard error is within the parameters of the attempt then populate the status message.
        if ($this->standard_error_within_parameters($this->standarderror, $quizdefinederror)) {
            // Convert logits to percent for display.
            $val = new stdClass();
            $val->calerror = self::convert_logit_to_percent($this->standarderror);
            $val->calerror = 100 * round($val->calerror, 2);
            $val->definederror = self::convert_logit_to_percent($quizdefinederror);
            $val->definederror = 100 * round($val->definederror, 2);

            return determine_next_difficulty_result::with_error(get_string('calcerrorwithinlimits', 'adaptivequiz', $val));
        }

        return determine_next_difficulty_result::with_next_difficulty_level_determined($this->nextdifficulty);
    }

    /**
     * This function takes a percent as a float between 0 and less than 0.5 and converts it into a logit value.
     *
     * @throws coding_exception if percent is out of bounds
     * @param float $percent percent represented as a decimal 15% = 0.15
     * @return float logit value of percent
     */
    public static function convert_percent_to_logit($percent) {
        if ($percent < 0 || $percent >= 0.5) {
            throw new coding_exception('convert_percent_to_logit: percent is out of bounds', 'Percent must be 0 >= and < 0.5');
        }
        return log( (0.5 + $percent) / (0.5 - $percent) );
    }

    /**
     * This function takes a logit as a float greater than or equal to 0 and converts it into a percent.
     *
     * @throws coding_exception if logit is out of bounds
     * @param float $logit logit value
     * @return float logit value of percent
     */
    public static function convert_logit_to_percent($logit) {
        if ($logit < 0) {
            throw new coding_exception('convert_logit_to_percent: logit is out of bounds',
                'logit must be greater than or equal to 0');
        }
        return ( 1 / ( 1 + exp(0 - $logit) ) ) - 0.5;
    }

    /**
     * Convert a logit value to a fraction between 0 and 1.
     *
     * @param float $logit logit value
     * @return float the logit value mapped as a fraction
     */
    public static function convert_logit_to_fraction($logit) {
        return exp($logit) / ( 1 + exp($logit) );
    }

    /**
     * This function takes the inverse of a logit value, then maps the value onto the scale defined for the attempt.
     *
     * @param float $logit logit value
     * @param int $max the maximum value of the scale
     * @param int $min the minimum value of the scale
     * @return float the logit value mapped onto the scale
     */
    public static function map_logit_to_scale($logit, $max, $min) {
        $fraction = self::convert_logit_to_fraction($logit);
        $scaledvalue = ( ( $max - $min ) * $fraction ) + $min;
        return $scaledvalue;
    }

    /**
     * This function compares the calculated standard error with the activity defined standard error allowed for the attempt.
     *
     * @param float $calculatederror the error calculated from the parameters of the user's current attempt
     * @param float $definederror the allowed error set for the activity instance
     * @return bool true if the calulated error is less than or equal to the defined error, otherwise false
     */
    public function standard_error_within_parameters($calculatederror, $definederror) {
        if ($calculatederror <= $definederror) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * This function estimates the standard error in the measurement.
     *
     * @param int $questattempt the number of question attempted
     * @param int $sumcorrect the sum of correct answers
     * @param int $sumincorrect the sum of incorrect answers
     * @return float a decimal rounded to 5 places is returned
     */
    public static function estimate_standard_error($questattempt, $sumcorrect, $sumincorrect) {
        if ($sumincorrect == 0) {
            $standarderror = sqrt($questattempt / ( ($sumcorrect - 0.5) * ($sumincorrect + 0.5) ) );
        } else if ($sumcorrect == 0) {
            $standarderror = sqrt($questattempt / ( ($sumcorrect + 0.5) * ($sumincorrect - 0.5) ) );
        } else {
            $standarderror = sqrt($questattempt / ( $sumcorrect * $sumincorrect ) );
        }

        return round($standarderror, 5);
    }

    /**
     * This function estimates the measure of ability.
     *
     * @param float $diffsum the sum of difficulty levels expressed as logits
     * @param int $questattempt the number of question attempted
     * @param int $sumcorrect the sum of correct answers
     * @param int $sumincorrect the sum of incorrect answers
     * @return float an estimate of the measure of ability
     */
    public static function estimate_measure($diffsum, $questattempt, $sumcorrect, $sumincorrect) {
        if ($sumincorrect == 0) {
            $measure = ($diffsum / $questattempt) + log( ($sumcorrect - 0.5) / ($sumincorrect + 0.5) );
        } else if ($sumcorrect == 0) {
            $measure = ($diffsum / $questattempt) + log( ($sumcorrect + 0.5) / ($sumincorrect - 0.5) );
        } else {
            $measure = ($diffsum / $questattempt) + log( $sumcorrect / $sumincorrect );
        }
        return round($measure, 5, PHP_ROUND_HALF_UP);
    }

    /**
     * This function does the work to determine the next difficulty level.
     *
     * @param int $questattempted The sum of questions attempted.
     * @param bool $correct True if the user got the previous question correct, otherwise false.
     * @param questions_difficulty_range $questionsdifficultyrange
     * @param float $logit The result of calling {@see self::convert_linear_to_logit()}.
     * @return int The next difficulty level.
     */
    public function compute_next_difficulty(
        int $questattempted,
        bool $correct,
        questions_difficulty_range $questionsdifficultyrange,
        float $logit
    ): int {
        // Check if the last question was marked correctly.
        if ($correct) {
            $nextdifficulty = $logit + 2 / $questattempted;
        } else {
            $nextdifficulty = $logit - 2 / $questattempted;
        }

        // Calculate the inverse to translate the value into a difficulty level.
        $invps = 1 / ( 1 + exp( (-1 * $nextdifficulty) ) );
        $invps = round($invps, 2);
        $difflevel = $questionsdifficultyrange->lowest_level() +
            ( $invps * ($questionsdifficultyrange->highest_level() - $questionsdifficultyrange->lowest_level()) );
        $difflevel = round($difflevel);

        return (int) $difflevel;
    }

    /**
     * Map an linear-scale difficulty/ability level to a logit scale.
     *
     * @param int $level An integer level
     * @param questions_difficulty_range $questionsdifficultyrange
     * @return float
     */
    public static function convert_linear_to_logit($level, questions_difficulty_range $questionsdifficultyrange): float {
        $min = $questionsdifficultyrange->lowest_level();
        $max = $questionsdifficultyrange->highest_level();

        // Map the level on a linear percentage scale.
        $percent = ($level - $min) / ($max - $min);

        // We will use a limit that is 1/2th the granularity of the question levels as our base.
        // For example, for levels 1-100, we will use a base of 0.5% (5.3 logits),
        // for levels 1-1000 we will use a base of 0.05% (7.6 logits).
        //
        // Note that the choice of 1/2 the granularity is somewhat arbitrary.
        // The floor value for the ends of the scale is being chosen so that answers
        // at the end of the scale do not excessively weight the ability measure
        // in ways that are not recoverable by subsequent answers.
        //
        // For example, lets say that on a scale of 1-10, a user of level 5 makes
        // a dumb mistake and answers two level 1 questions wrong, but then continues
        // the test and answers 20 more questions with every question up to level 5
        // right and those above wrong. The test should likely score the user somewhere
        // a bit below 5 with 5 being included in the Standard Error.
        //
        // Several test runs with different floors showed that 1/1000 gave far too
        // much weight to answers at the edge of the scale. 1/10 did ok, but
        // 1/2 seemed to allow recovery from spurrious answers at the edges while
        // still allowing consistent answers at the edges to trend the ability measure to
        // the top/bottom level.
        $granularity = 1 / ($max - $min);
        $percentfloor = $granularity / 2;

        // Avoid a division by zero error.
        if ($percent == 1) {
            $percent = 1 - $percentfloor;
        }

        // Map the percentage scale to a logrithmic logit scale.
        $logit = log( $percent / (1 - $percent) );

        // Check if result is inifinite.
        if (is_infinite($logit)) {
            $logitfloor = log( $percentfloor / (1 - $percentfloor) );
            if ($logit > 0) {
                return -1 * $logitfloor;
            } else {
                return $logitfloor;
            }
        }
        return $logit;
    }
}
