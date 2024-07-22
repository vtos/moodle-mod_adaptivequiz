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

namespace mod_adaptivequiz;

use context_module;
use core_tag_tag;
use mod_adaptivequiz\local\attempt;
use mod_adaptivequiz\local\catalgo;
use mod_adaptivequiz\local\fetchquestion;
use moodle_exception;
use question_engine;
use stdClass;

/**
 * High level API class to manage the process of CAT.
 *
 * @package    mod_adaptivequiz
 * @copyright  2024 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cat_session {

    /**
     * Chooses the next question and adds it to the quba instance to be presented to the user.
     *
     * @param int $uniqueid
     * @param stdClass $adaptivequiz
     * @param context_module $context
     * @param attempt $adaptiveattempt
     */
    public static function run_item_administration(
        int $uniqueid,
        stdClass $adaptivequiz,
        context_module $context,
        attempt $adaptiveattempt
    ): void {
        global $USER;

        $message = $adaptiveattempt->get_status();
        if (!empty($message)) {
            // The attempt has been flagged as having a stoppage reason, complete it and exit here.
            adaptivequiz_complete_attempt($uniqueid, $adaptivequiz, $context, $USER->id, $message);

            return;
        }

        $nextdiff = $adaptiveattempt->get_level();

        $setlevel = ($nextdiff === 0) ? (int) $adaptivequiz->startinglevel : $nextdiff;
        $adaptiveattempt->set_level($setlevel);

        $attemptstatus = $adaptiveattempt->start_attempt($context);

        // Check if attempt status is set to ready.
        if (empty($attemptstatus)) {
            // Retrieve the most recent status message for the attempt.
            $message = $adaptiveattempt->get_status();

            $noquestionsfetchedforattempt = $uniqueid == 0;
            if ($noquestionsfetchedforattempt) {
                // The script will try to complete an 'empty' attempt as it couldn't fetch the first question for some reason.
                // This is an invalid behaviour, which could be caused by a misconfigured questions pool. Stop it here.
                throw new moodle_exception('attemptnofirstquestion', 'adaptivequiz');
            }

            adaptivequiz_complete_attempt($uniqueid, $adaptivequiz, $context, $USER->id, $message);
        }
    }

    /**
     * Processes the submitted question answer and operates on the given attempt instance changing its state.
     *
     * @param int $uniqueid
     * @param stdClass $adaptivequiz
     * @param attempt $adaptiveattempt
     * @param callable $qubahelper Used to change behavior when processing a submitted question.
     */
    public static function process_administered_item_result(
        int $uniqueid,
        stdClass $adaptivequiz,
        attempt $adaptiveattempt,
        callable $qubahelper
    ): void {
        global $USER;

        $attemptrec = $adaptiveattempt->get_attempt();

        if (!adaptivequiz_uniqueid_part_of_attempt($uniqueid, $adaptivequiz->id, $USER->id)) {
            throw new moodle_exception('uniquenotpartofattempt', 'adaptivequiz');
        }

        $quba = question_engine::load_questions_usage_by_activity($uniqueid);
        $qubahelper($quba);
        question_engine::save_questions_usage_by_activity($quba);

        $slots = $quba->get_slots();
        $slot = $slots[count($slots) - 1];

        $question = $quba->get_question($slot);

        $questiontags = core_tag_tag::get_item_tags('core_question', 'question', $question->id);
        $questiontags = array_filter($questiontags, function (core_tag_tag $tag): bool {
            return substr($tag->name, 0, strlen(ADAPTIVEQUIZ_QUESTION_TAG)) === ADAPTIVEQUIZ_QUESTION_TAG;
        });
        $questiontag = array_shift($questiontags);

        $difflevel = substr($questiontag->name, strlen(ADAPTIVEQUIZ_QUESTION_TAG));

        $minattemptreached = adaptivequiz_min_attempts_reached($uniqueid, $adaptivequiz->id, $USER->id);

        $algo = new catalgo($quba, (int) $attemptrec->id, $minattemptreached, (int) $difflevel);
        $nextdiff = $algo->perform_calculation_steps();

        $adaptiveattempt->set_level($nextdiff);

        $difflogit = $algo->get_levellogit();
        $standarderror = $algo->get_standarderror();
        $measure = $algo->get_measure();
        $everythingokay = adaptivequiz_update_attempt_data($uniqueid, $adaptivequiz->id, $USER->id, $difflogit, $standarderror,
            $measure);

        // Something went wrong with updating the attempt.
        if (!$everythingokay) {
            throw new moodle_exception('unableupdatediffsum', 'adaptivequiz');
        }

        // Check whether the status property is empty.
        $message = $algo->get_status();
        if (!empty($message)) {
            $adaptiveattempt->set_status($message);

            return;
        }

        // Lastly decrement the sum of questions for the attempted difficulty level.
        $fetchquestion = new fetchquestion($adaptivequiz, (int) $difflevel, $adaptivequiz->lowestlevel,
            $adaptivequiz->highestlevel);
        $tagquestcount = $fetchquestion->get_tagquestsum();
        $tagquestcount = $fetchquestion->decrement_question_sum_from_difficulty($tagquestcount, $difflevel);
        $fetchquestion->set_tagquestsum($tagquestcount);

        $fetchquestion->store_tagquestsum_in_session();
    }
}
