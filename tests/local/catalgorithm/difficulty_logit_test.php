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

namespace mod_adaptivequiz\local\catalgorithm;

use base_testcase;
use InvalidArgumentException;

/**
 * Test class.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_adaptivequiz\local\catalgorithm\difficulty_logit
 */
class difficulty_logit_test extends base_testcase {

    public function test_it_cannot_be_instantiated_with_infinite_value(): void {
        $this->expectException(InvalidArgumentException::class);
        difficulty_logit::from_float(INF);
    }

    public function test_it_can_be_summed_with_another_logit(): void {
        $logit = difficulty_logit::from_float(3.7072286);
        $summedlogit = $logit->summed_with_another_logit(difficulty_logit::from_float(6.4324805));

        self::assertEquals(difficulty_logit::from_float(10.1397091), $summedlogit);
    }
}
