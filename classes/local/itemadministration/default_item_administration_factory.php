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

require_once($CFG->dirroot .'/mod/adaptivequiz/locallib.php');

use mod_adaptivequiz\local\attempt;
use question_usage_by_activity;
use stdClass;

/**
 * Default implementation of the item administration factory.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class default_item_administration_factory implements item_administration_factory {

    /**
     * Implementation of the interface.
     *
     * @param question_usage_by_activity $quba
     * @param attempt $attempt
     * @param stdClass $adaptivequiz Apart from db fields, extra 'cm' and 'context' properties are present.
     * @return item_administration
     */
    public function item_administration_implementation(
        question_usage_by_activity $quba,
        attempt $attempt,
        stdClass $adaptivequiz
    ): item_administration {
        return new default_item_administration($quba, $attempt, $adaptivequiz);
    }
}
