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

use context_module;
use mod_adaptivequiz\local\attempt;
use question_usage_by_activity;
use stdClass;

/**
 * The class is responsible for administering an item (a question) during a CAT session.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class default_item_administration implements item_administration {

    /**
     * @var question_usage_by_activity $quba
     */
    private $quba;

    /**
     * @var attempt $attempt
     */
    private $attempt;

    /**
     * @var stdClass $adaptivequiz A record from {adaptivequiz}.
     */
    private $adaptivequiz;

    /**
     * The constructor.
     *
     * @param question_usage_by_activity $quba
     * @param attempt $attempt
     * @param stdClass $adaptivequiz
     */
    public function __construct(question_usage_by_activity $quba, attempt $attempt, stdClass $adaptivequiz) {
        $this->quba = $quba;
        $this->attempt = $attempt;
        $this->adaptivequiz = $adaptivequiz;
    }

    /**
     * Assesses the ability to administer next question during the quiz.
     *
     * @param int|null $previousquestionslot See the interface.
     * @return item_administration_evaluation
     */
    public function evaluate_ability_to_administer_next_item(
        ?int $previousquestionslot
    ): item_administration_evaluation {
        $nextdiff = $this->attempt->get_level();

        $setlevel = ($nextdiff === 0) ? (int) $this->adaptivequiz->startinglevel : $nextdiff;
        $this->attempt->set_level($setlevel);

        $cm = get_coursemodule_from_instance('adaptivequiz', $this->adaptivequiz->id, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        $this->attempt->set_question_slot_number(($previousquestionslot !== null) ? $previousquestionslot : 0);

        $attemptstatus = $this->attempt->start_attempt($context);

        // Check if attempt status is set to ready.
        if (empty($attemptstatus)) {
            $message = $this->attempt->get_status();

            return item_administration_evaluation::with_stoppage_reason($message);
        }

        return item_administration_evaluation::with_next_item(
            next_item::from_quba_slot($this->attempt->get_question_slot_number())
        );
    }
}
