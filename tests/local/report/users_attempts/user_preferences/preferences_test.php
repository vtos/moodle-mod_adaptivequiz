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

use base_testcase;
use mod_adaptivequiz\local\report\users_attempts\filter\filter_options;
use mod_adaptivequiz\local\report\users_attempts\user_preferences\filter_user_preferences;
use mod_adaptivequiz\local\report\users_attempts\user_preferences\preferences;
use stdClass;

class preferences_test extends base_testcase {

    /**
     * @test
     */
    public function it_acquires_correct_default_values_when_provided_values_are_not_in_valid_range(): void {
        $preferences = preferences::from_array(['perpage' => 100, 'showinitialsbar' => 22, 'persistentfilter' => -1]);

        $this->assertEquals(15, $preferences->rows_per_page());
        $this->assertTrue($preferences->show_initials_bar());
        $this->assertFalse($preferences->persistent_filter());
        $this->assertNull($preferences->filter());

        $preferences = preferences::from_array([]);

        $this->assertEquals(15, $preferences->rows_per_page());
        $this->assertTrue($preferences->show_initials_bar());
        $this->assertFalse($preferences->persistent_filter());
        $this->assertNull($preferences->filter());

        $preferencesAsObject = new stdClass();
        $preferencesAsObject->perpage = -25;
        $preferencesAsObject->showinitialsbar = 100;
        $preferencesAsObject->persistentfilter = "12";
        $preferences = preferences::from_plain_object($preferencesAsObject);

        $this->assertEquals(15, $preferences->rows_per_page());
        $this->assertTrue($preferences->show_initials_bar());
        $this->assertFalse($preferences->persistent_filter());
        $this->assertNull($preferences->filter());
    }

    /**
     * @test
     */
    public function it_can_acquire_and_loose_filter_preferences_while_preserving_previously_set_preferences(): void {
        $preferences = preferences::from_array(['perpage' => 10, 'showinitialsbar' => 0, 'persistentfilter' => 1]);

        $pfilterpreferencesarray = ['users' => filter_options::users_option_default(),
            'includeinactiveenrolments' => filter_options::INCLUDE_INACTIVE_ENROLMENTS_DEFAULT];
        $preferences = $preferences->with_filter_preference(filter_user_preferences::from_array($pfilterpreferencesarray));

        $this->assertEquals(10, $preferences->rows_per_page());
        $this->assertEquals(0, $preferences->show_initials_bar());
        $this->assertEquals(1, $preferences->persistent_filter());
        $this->assertEquals(filter_user_preferences::from_array($pfilterpreferencesarray), $preferences->filter());

        $preferences = $preferences->without_filter_preference();
        $this->assertEquals(10, $preferences->rows_per_page());
        $this->assertEquals(0, $preferences->show_initials_bar());
        $this->assertEquals(1, $preferences->persistent_filter());
        $this->assertEquals(null, $preferences->filter());
    }

    /**
     * @test
     */
    public function it_can_be_converted_to_array(): void {
        $preferences = preferences::defaults();
        $this->assertEquals(['perpage' => 15, 'showinitialsbar' => 1, 'persistentfilter' => 0, 'filter' => null],
            $preferences->as_array());
    }
}
