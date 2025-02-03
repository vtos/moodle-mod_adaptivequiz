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

namespace mod_adaptivequiz\external;

use basic_testcase;

/**
 * Testing exporting of the ability measure data.
 *
 * @package    mod_adaptivequiz
 * @copyright  2025 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers     \mod_adaptivequiz\external\ability_measure_exporter
 */
final class ability_measure_exporter_test extends basic_testcase {

    public function test_it_exports_the_required_data(): void {
        global $PAGE;

        $attempt = (object) [
            'measure' => 3.42429,
        ];

        $adaptivequiz = (object) [
            'minimumquestions' => 25,
            'maximumquestions' => 10,
            'highestlevel' => 15,
            'lowestlevel' => 1,
        ];

        $exporter = new ability_measure_exporter([
            'highestlevel' => $adaptivequiz->highestlevel,
            'lowestlevel' => $adaptivequiz->lowestlevel,
        ], [
            'attempt' => $attempt,
        ]);

        $exported = $exporter->export($PAGE->get_renderer('core'));
        self::assertEquals((object) [
            'abilitymeasurevalue' => 14.56,
            'highestlevel' => 15,
            'lowestlevel' => 1,
        ], $exported);
    }
}
