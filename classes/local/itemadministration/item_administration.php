<?php

namespace mod_adaptivequiz\local\itemadministration;

use mod_adaptivequiz\local\question\question_answer_evaluation_result;

/**
 * The class is responsible for administering an item (a question) during a CAT session.
 *
 * In the process of CAT 'item administration' means the process of assessing amswer given to the previous question and
 * performing some calculations to decide what the next item (a question) is to be administered (presented to the quiz-taker).
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface item_administration {

    /**
     * Takes the result of evaluation of the answer given to the previous question and makes a decision based on that.
     *
     * The decision may be stopping the attempt if some condition is reached, or administer next item. Thus, must return
     * a specific object with such information.
     *
     * When null value is passed as the result of evaluating answer given to the previous question, this means this is either
     * a fresh attempt that has just started, or continuation of the previously started attempt.
     *
     * @param question_answer_evaluation_result|null $questionanswerevaluationresult
     * @return item_administration_evaluation
     */
    public function evaluate_ability_to_administer_next_item(
        ?question_answer_evaluation_result $questionanswerevaluationresult
    ): item_administration_evaluation;
}
