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

use mod_adaptivequiz\local\attempt\attempt;
use mod_adaptivequiz\local\itemadministration\item_administration;
use mod_adaptivequiz\local\itemadministration\item_administration_factory;
use mod_adaptivequiz\local\question\question_answer_evaluation;
use question_usage_by_activity;
use stdClass;

/**
 * Contains implementations of the item administration factory.
 *
 * @package    adaptivequizcatmodel_helloworld
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class helloworld_item_administration_factory implements item_administration_factory {

    /**
     * Implements the interface.
     *
     * @param question_usage_by_activity $quba
     * @param attempt $attempt
     * @param stdClass $adaptivequiz
     * @return item_administration
     */
    public function item_administration_implementation(
        question_usage_by_activity $quba,
        attempt $attempt,
        stdClass $adaptivequiz
    ): item_administration {
        return new helloworld_item_administration(new question_answer_evaluation($quba), $adaptivequiz);
    }
}
