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
 * Provides methods for storing and fetching of the report user preferences from the storage.
 * Operates on user session as well to avoid unnecessary database queries.
 *
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_adaptivequiz\local\report\users_attempts\user_preferences;

defined('MOODLE_INTERNAL') || die();

final class repository {

    public static function save(string $uniqueid, preferences $prefs): void {
        global $SESSION;

        $prefsarr = $prefs->as_array();

        $SESSION->flextableextra[$uniqueid] = $prefsarr;
        set_user_preference('flextable_extra_' . $uniqueid, json_encode($prefsarr));
    }

    public static function get(string $uniqueid): preferences {
        global $SESSION;

        $storedprefsarr = empty($SESSION->flextableextra[$uniqueid])
            ? json_decode(get_user_preferences('flextable_extra_' . $uniqueid), true)
            : $SESSION->flextableextra[$uniqueid]
        ;

        return empty($storedprefsarr) ? preferences::defaults() : preferences::from_array($storedprefsarr);
    }
}
