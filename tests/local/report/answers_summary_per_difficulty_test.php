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

namespace mod_adaptivequiz\local\report;

use basic_testcase;
use coding_exception;
use stdClass;

/**
 * Unit test class.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_adaptivequiz\local\report\answers_summary_per_difficulty
 */
class answers_summary_per_difficulty_test extends basic_testcase {

    public function test_it_can_collect_answers_summary_for_difficulty_levels_and_can_be_queried_for_them(): void {
        $adaptivequiz = new stdClass();
        $adaptivequiz->lowestlevel = 1;
        $adaptivequiz->highestlevel = 50;

        $summary = answers_summary_per_difficulty::from_difficulty_range(
            questions_difficulty_range::from_activity_instance($adaptivequiz)
        );

        $summary->increment_number_of_correct_answers_for_difficulty(5);
        $summary->increment_number_of_correct_answers_for_difficulty(5);
        $summary->increment_number_of_correct_answers_for_difficulty(20);
        $summary->increment_number_of_incorrect_answers_for_difficulty(20);
        $summary->increment_number_of_incorrect_answers_for_difficulty(20);
        // Go beyond the initialized range.
        $summary->increment_number_of_correct_answers_for_difficulty(55);
        $summary->increment_number_of_incorrect_answers_for_difficulty(55);

        self::assertEquals(
            new answers_summary(2, 0),
            $summary->answers_summary_for_difficulty(5)
        );
        self::assertEquals(
            new answers_summary(1, 2),
            $summary->answers_summary_for_difficulty(20)
        );
        self::assertEquals(
            new answers_summary(1, 1),
            $summary->answers_summary_for_difficulty(55)
        );
    }

    public function test_it_cannot_be_queried_for_answers_summary_for_unknown_difficulty_level(): void {
        $adaptivequiz = new stdClass();
        $adaptivequiz->lowestlevel = 1;
        $adaptivequiz->highestlevel = 50;

        self::expectException(coding_exception::class);
        answers_summary_per_difficulty::from_difficulty_range(
            questions_difficulty_range::from_activity_instance($adaptivequiz)
        )
            ->answers_summary_for_difficulty(51);
    }
}
