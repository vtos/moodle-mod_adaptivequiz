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

declare(strict_types=1);

namespace mod_adaptivequiz\local\question;

use question_usage_by_activity;

/**
 * Serves to extract the logic of assessing whether a question was answered correctly and whether it was answered at all.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class question_answer_evaluation {

    /**
     * @var question_usage_by_activity $quba
     */
    private $quba;

    /**
     * The constructor.
     */
    public function __construct(question_usage_by_activity $quba) {
        $this->quba = $quba;
    }

    /**
     * Runs the evaluation and produces a result.
     *
     * @return question_answer_evaluation_result
     */
    public function perform(): question_answer_evaluation_result {
        $slot = $this->find_last_question_slot();
        if ($slot === null) {
            return question_answer_evaluation_result::when_answer_was_not_given();
        }

        if (!$this->answer_was_given($slot)) {
            return question_answer_evaluation_result::when_answer_was_not_given();
        }

        $mark = $this->quba->get_question_mark($slot);
        if ($mark === null) {
            return question_answer_evaluation_result::when_answer_was_not_given();
        }

        if ($mark > 0.0) {
            return question_answer_evaluation_result::when_answer_is_correct();
        }

        return question_answer_evaluation_result::when_answer_is_incorrect();
    }

    /**
     * Searches for slot of the last question used by the adaptive quiz activity.
     *
     * @return int|null
     */
    private function find_last_question_slot(): ?int {
        // The last slot in the array should be the last question that was attempted (meaning it was either shown to the user or the
        // user submitted an answer to it).
        $slots = $this->quba->get_slots();
        if (empty($slots)) {
            return null;
        }

        return array_pop($slots);
    }

    /**
     * Checks whether the question was given an answer.
     *
     * @param int $slot
     * @return bool
     */
    private function answer_was_given(int $slot): bool {
        $state = $this->quba->get_question_state($slot);

        return $state->is_graded();
    }
}
