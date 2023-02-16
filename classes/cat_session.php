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

use coding_exception;
use context_module;
use core_component;
use core_tag_tag;
use mod_adaptivequiz\local\attempt;
use mod_adaptivequiz\local\catalgo;
use mod_adaptivequiz\local\fetchquestion;
use mod_adaptivequiz\local\itemadministration\default_item_administration_factory;
use mod_adaptivequiz\local\itemadministration\item_administration_factory;
use moodle_exception;
use question_bank;
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

        $adaptiveattempt->get_attempt();
        $quba = $adaptiveattempt->initialize_quba($context);

        $message = $adaptiveattempt->get_status();
        if (!empty($message)) {
            // The attempt has been flagged as having a stoppage reason, complete it and exit here.
            adaptivequiz_complete_attempt($uniqueid, $adaptivequiz, $context, $USER->id, $message);

            return;
        }

        $itemadministrationfactory = $adaptivequiz->catmodel
            ? self::catmodel_item_administration_factory($adaptivequiz->catmodel)
            : new default_item_administration_factory();

        $slots = $quba->get_slots();
        $previousslot = !empty($slots) ? end($slots) : null;

        $itemadministration = $itemadministrationfactory->item_administration_implementation($quba, $adaptiveattempt,
            $adaptivequiz);
        $itemadministrationevaluation = $itemadministration->evaluate_ability_to_administer_next_item($previousslot);

        if ($itemadministrationevaluation->item_administration_is_to_stop()) {
            $noquestionsfetchedforattempt = $uniqueid == 0;
            if ($noquestionsfetchedforattempt) {
                // The script will try to complete an 'empty' attempt as it couldn't fetch the first question for some reason.
                // This is an invalid behaviour, which could be caused by a misconfigured questions pool. Stop it here.
                throw new moodle_exception('attemptnofirstquestion', 'adaptivequiz');
            }

            adaptivequiz_complete_attempt($uniqueid, $adaptivequiz, $context, $USER->id,
                $itemadministrationevaluation->stoppage_reason());

            // In case it was an alternative CAT implementation set the attempt status.
            if (empty($adaptiveattempt->get_status())) {
                $adaptiveattempt->set_status($itemadministrationevaluation->stoppage_reason());
            }

            return;
        }

        $slot = $itemadministrationevaluation->next_item()->quba_slot();
        if (is_null($slot)) {
            $question = question_bank::load_question($itemadministrationevaluation->next_item()->question_id());
            $slot = $quba->add_question($question);

            if (!$quba->get_question_state($slot)->is_active()) {
                $quba->start_question($slot);
                question_engine::save_questions_usage_by_activity($quba);

                // If this is the first question, set quba id for the attempt.
                if (count($quba->get_slots()) == 1) {
                    $adaptiveattempt->set_quba_id($quba->get_id());
                }
            }

            $adaptiveattempt->set_question_slot_number($slot);
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

        // If this is a custom CAT model, update the attempt and exit.
        if ($adaptivequiz->catmodel) {
            adaptivequiz_update_attempt_data($uniqueid, $adaptivequiz->id, $USER->id, 0, 0, 0);

            return;
        }

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

    /**
     * Tries to instantiate implementation of the factory for the given CAT model.
     *
     * @param string $catmodel
     * @return item_administration_factory
     * @throws coding_exception
     */
    private static function catmodel_item_administration_factory(string $catmodel): item_administration_factory {
        $implementations = core_component::get_component_classes_in_namespace(
            "adaptivequizcatmodel_$catmodel",
            'local\catmodel\itemadministration'
        );
        if (empty($implementations)) {
            throw new coding_exception(
                'implementations of the item_administration_factory interface could not be found for the selected CAT model'
            );
        }

        $classnames = array_filter(array_keys($implementations), function (string $classname): bool {
            return is_subclass_of($classname, '\mod_adaptivequiz\local\itemadministration\item_administration_factory');
        });

        if (empty($classnames)) {
            throw new coding_exception(
                'implementations of the item_administration_factory interface could not be found for the selected CAT model'
            );
        }

        if (count($classnames) > 1) {
            throw new coding_exception('only one implementation of the item_administration_factory interface is expected');
        }

        $classname = array_shift($classnames);

        return new $classname();
    }
}
