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

use base_testcase;
use coding_exception;

/**
 * Test class.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_adaptivequiz\local\itemadministration\next_item
 */
class next_item_test extends base_testcase {

    public function test_it_fails_to_instantiate_with_a_negative_question_id(): void {
        self::expectException(coding_exception::class);
        next_item::from_question_id(-5);
    }

    public function test_it_fails_to_instantiate_with_a_zero_question_id(): void {
        self::expectException(coding_exception::class);
        next_item::from_question_id(0);
    }

    public function test_it_fails_to_instantiate_with_a_negative_slot_number(): void {
        self::expectException(coding_exception::class);
        next_item::from_quba_slot(-9);
    }

    public function test_it_fails_to_instantiate_with_a_zero_slot_number(): void {
        self::expectException(coding_exception::class);
        next_item::from_quba_slot(0);
    }

    public function test_it_instantiates_with_valid_arguments_and_can_be_queried_for_the_data(): void {
        $nextitemwithquestionid = next_item::from_question_id(100458);
        self::assertEquals(100458, $nextitemwithquestionid->question_id());

        $nextitemwithslot = next_item::from_quba_slot(18);
        self::assertEquals(18, $nextitemwithslot->quba_slot());
    }
}
