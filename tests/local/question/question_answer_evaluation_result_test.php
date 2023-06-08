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

namespace mod_adaptivequiz\local\question;

use base_testcase;

/**
 * Test class.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_adaptivequiz\local\question\question_answer_evaluation_result
 */
class question_answer_evaluation_result_test extends base_testcase {

    public function test_it_instantiates_and_can_be_queried_for_the_data(): void {
        $result = question_answer_evaluation_result::when_answer_is_correct();
        self::assertTrue($result->answer_is_correct());

        $result = question_answer_evaluation_result::when_answer_is_incorrect();
        self::assertFalse($result->answer_is_correct());
    }
}
