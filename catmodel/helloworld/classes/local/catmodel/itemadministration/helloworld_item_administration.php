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

namespace adaptivequizcatmodel_helloworld\local\catmodel\itemadministration;

use mod_adaptivequiz\local\itemadministration\item_administration;
use mod_adaptivequiz\local\itemadministration\item_administration_evaluation;
use mod_adaptivequiz\local\itemadministration\next_item;
use mod_adaptivequiz\local\question\question_answer_evaluation;
use question_bank;
use stdClass;

/**
 * Contains implementations of the item administration interface.
 *
 * @package    adaptivequizcatmodel_helloworld
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class helloworld_item_administration implements item_administration {

    /**
     * @var question_answer_evaluation $questionanswerevaluation
     */
    private $questionanswerevaluation;

    /**
     * @var stdClass $adaptivequiz
     */
    private $adaptivequiz;

    /**
     * The constructor.
     *
     * @param question_answer_evaluation $questionanswerevaluation
     * @param stdClass $adaptivequiz
     */
    public function __construct(question_answer_evaluation $questionanswerevaluation, stdClass $adaptivequiz) {
        $this->questionanswerevaluation = $questionanswerevaluation;
        $this->adaptivequiz = $adaptivequiz;
    }

    /**
     * Implements the interface.
     *
     * The example logic is to stop the attempt if the question is answered incorrectly, in case of a correct answer just fetch
     * any random question from the configured pool.
     *
     * @param int|null $previousquestionslot
     * @return item_administration_evaluation
     */
    public function evaluate_ability_to_administer_next_item(?int $previousquestionslot): item_administration_evaluation {
        if (is_null($previousquestionslot)) {
            // This means no answer has been given yet, it's a fresh attempt.
            if (!$questionidarr = $this->fetch_question_id()) {
                return item_administration_evaluation::with_stoppage_reason(
                    get_string('itemadministration:stopbecausenomorequestions', 'adaptivequizcatmodel_helloworld')
                );
            }

            return item_administration_evaluation::with_next_item(next_item::from_question_id(array_rand($questionidarr)));
        }

        $questionanswerevaluationresult = $this->questionanswerevaluation->perform($previousquestionslot);

        if (!$questionanswerevaluationresult->answer_is_correct()) {
            return item_administration_evaluation::with_stoppage_reason(
                get_string('itemadministration:stopbecauseincorrectanswer', 'adaptivequizcatmodel_helloworld')
            );
        }

        if (!$questionidarr = $this->fetch_question_id()) {
            return item_administration_evaluation::with_stoppage_reason(
                get_string('itemadministration:stopbecausenomorequestions', 'adaptivequizcatmodel_helloworld')
            );
        }

        return item_administration_evaluation::with_next_item(next_item::from_question_id(array_rand($questionidarr)));
    }

    /**
     * Fetches array of question id for the configured pool.
     *
     * @return int[] An array of question id.
     */
    private function fetch_question_id(): array {
        global $DB;

        $questioncategoryid = $DB->get_field('adaptivequiz_question', 'questioncategory', ['instance' => $this->adaptivequiz->id],
            MUST_EXIST);

        return question_bank::get_finder()->get_questions_from_categories($questioncategoryid, '');
    }
}
