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
 * The class is responsible for administering an item (a question) during a CAT session.
 *
 * In the process of CAT 'item administration' means the process of assessing answer given to the previous question and
 * performing some calculations to decide what the next item (a question) is to be administered (presented to the quiz-taker).
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_adaptivequiz\local\itemadministration;

interface item_administration {

    /**
     * Decides whether the next item should be administered of the test must stop.
     *
     * Takes slot number of the previous question that has been administered. Using the slot number the required info about
     * the question state can be obtained, like whether the answer is correct, mark value, etc.
     *
     * The decision may be stopping the attempt if some condition is reached, or administer next item. Thus, must return
     * a specific object with such information.
     *
     * @param int|null $previousquestionslot Slot number of the previous question, which has just been answered. When null value
     * is passed this means this is either a fresh attempt that has just started, or continuation of the previously started
     * attempt.
     * @return item_administration_evaluation
     */
    public function evaluate_ability_to_administer_next_item(?int $previousquestionslot): item_administration_evaluation;
}
