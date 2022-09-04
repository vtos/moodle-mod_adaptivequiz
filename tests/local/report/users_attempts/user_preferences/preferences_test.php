<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local\report\users_attempts\user_preferences;

use advanced_testcase;
use mod_adaptivequiz\local\report\users_attempts\user_preferences\preferences;
use stdClass;

class preferences_test extends advanced_testcase {

    /**
     * @test
     */
    public function it_acquires_correct_default_values_when_provided_values_are_not_in_valid_range(): void {
        $preferences = preferences::from_array(['perpage' => 100, 'showinitialsbar' => 22]);

        $this->assertEquals(15, $preferences->rows_per_page());
        $this->assertTrue($preferences->show_initials_bar());

        $preferencesAsObject = new stdClass();
        $preferencesAsObject->perpage = -25;
        $preferencesAsObject->showinitialsbar = 100;
        $preferences = preferences::from_plain_object($preferencesAsObject);

        $this->assertEquals(15, $preferences->rows_per_page());
        $this->assertTrue($preferences->show_initials_bar());
    }

    /**
     * @test
     */
    public function it_can_be_converted_to_array(): void {
        $preferences = preferences::defaults();
        $this->assertEquals(['perpage' => 15, 'showinitialsbar' => 1], $preferences->as_array());
    }
}
