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
 * Instantiates proper item administration implementation specific to the CAT model being used.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_adaptivequiz\local\itemadministration;

use mod_adaptivequiz\local\attempt\attempt;
use question_usage_by_activity;
use stdClass;

interface item_administration_factory {

    /**
     * Instantiates an object of a class implementing the item administration interface.
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
    ): item_administration;
}
