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

namespace adaptivequizcatmodel_catquiz\local\catmodel\itemadministration;

use local_catquiz\catquiz_handler;
use mod_adaptivequiz\local\itemadministration\item_administration;
use mod_adaptivequiz\local\itemadministration\item_administration_evaluation;
use mod_adaptivequiz\local\itemadministration\next_item;
use mod_adaptivequiz\local\question\question_answer_evaluation_result;
use question_bank;
use question_engine;
use question_usage_by_activity;
use stdClass;

/**
 * Contains implementations of the item administration interface.
 *
 * @package    adaptivequizcatmodel_catquiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class catquiz_item_administration implements item_administration {

    /**
     * @var question_usage_by_activity $quba
     */
    private $quba;

    /**
     * @var stdClass $adaptivequiz
     */
    private $adaptivequiz;

    /**
     * The constructor.
     *
     * @param question_usage_by_activity $quba
     * @param stdClass $adaptivequiz
     */
    public function __construct(question_usage_by_activity $quba, stdClass $adaptivequiz) {
        $this->quba = $quba;
        $this->adaptivequiz = $adaptivequiz;
    }

    /**
     * Implements the interface.
     *
     * The example logic is to stop the attempt if the question is answered incorrectly, in case of a correct answer just fetch
     * any random question from the configured pool.
     *
     * @param question_answer_evaluation_result|null $questionanswerevaluationresult
     * @return item_administration_evaluation
     */
    public function evaluate_ability_to_administer_next_item(
        ?question_answer_evaluation_result $questionanswerevaluationresult
    ): item_administration_evaluation {
        if (is_null($questionanswerevaluationresult)) {
            // This means no answer has been given yet, it's a fresh attempt.
            if (!$questionid = $this->fetch_question_id()) {
                return item_administration_evaluation::with_stoppage_reason(
                    get_string('itemadministration:stopbecausenomorequestions', 'adaptivequizcatmodel_catquiz')
                );
            }

            $slot = $this->get_slot_for_question($questionid);

            return item_administration_evaluation::with_next_item(new next_item($slot));
        }

        if (!$questionanswerevaluationresult->answer_is_correct()) {
            return item_administration_evaluation::with_stoppage_reason(
                get_string('itemadministration:stopbecauseincorrectanswer', 'adaptivequizcatmodel_catquiz')
            );
        }

        //if (!$questionid = $this->fetch_question_id()) {
            return item_administration_evaluation::with_stoppage_reason(
                get_string('itemadministration:stopbecausenomorequestions', 'adaptivequizcatmodel_catquiz')
            );
        //}

        //$slot = $this->get_slot_for_question($questionid);

        //return item_administration_evaluation::with_next_item(new next_item($slot));
    }

    /**
     * Fetches a question id for the configured pool.
     *
     * @return int Question id
     */
    private function fetch_question_id(): int {
        // Dummy value - will be replaced by custom logic from catquiz handler
        return catquiz_handler::fetch_question_id();
    }

    /**
     * Starts a random question from the configured pool and returns its slot number.
     *
     * @param int $questionid
     * @return int The slot number.
     */
    private function get_slot_for_question(int $questionid): int {
        $question = question_bank::load_question($questionid);
        $slot = $this->quba->add_question($question);
        $this->quba->start_question($slot);
        question_engine::save_questions_usage_by_activity($this->quba);

        return $slot;
    }
}
