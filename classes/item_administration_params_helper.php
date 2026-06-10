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

namespace mod_adaptivequiz;

use stdClass;

/**
 * Provides methods to read information about item administration parameters.
 *
 * @package    mod_adaptivequiz
 * @copyright  2026 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_administration_params_helper {

    /**
     * @var string[] Proeprties of the 'adaptivequiz' module related to item administration.
     */
    private const PARAMS = ['highestlevel', 'lowestlevel', 'startinglevel', 'minimumquestions', 'maximumquestions',
        'standarderror'];

    /**
     * Returns names of the fields related to item administration settings.
     *
     * @return string[] An array of 'adaptivequiz' module's field names.
     */
    public static function fields(): array {
        return self::PARAMS;
    }

    /**
     * Reports whether all required parameters for item administration are set.
     *
     * @param stdClass $adaptivequiz An 'adaptivequiz' instance.
     */
    public static function is_all_set_for_adaptivequiz(stdClass $adaptivequiz): bool {
        // If at least one fails the whole thing does as well.
        foreach (self::PARAMS as $field) {
            if (!$adaptivequiz->{$field}) {
                return false;
            }
        }

        return true;
    }

    /**
     * Provides detailed information on the validity of item administration parameters.
     *
     * @param stdClass $adaptivequiz An 'adaptivequiz' instance.
     * @return array The key is a param shortname, the value is an error message (if the param is invalid).
     */
    public static function get_validation_results_for_adaptivequiz(stdClass $adaptivequiz): array {
        $return = [];

        foreach (self::PARAMS as $param) {
            $return[$param] = '';
        }

        $lowestlevelnum = item_bank_helper::count_adaptivequiz_questions_with_difficulty_level(
            $adaptivequiz->id,
            $adaptivequiz->lowestlevel
        );

        if (!$lowestlevelnum) {
            $return['lowestlevel'] = get_string('itembankinvalidlevel', 'adaptivequiz');
        }

        $highestlevelnum = item_bank_helper::count_adaptivequiz_questions_with_difficulty_level(
            $adaptivequiz->id,
            $adaptivequiz->highestlevel
        );

        if (!$highestlevelnum) {
            $return['highestlevel'] = get_string('itembankinvalidlevel', 'adaptivequiz');
        }

        return $return;
    }

    /**
     * A shortcut method to wrap all checks for item administration parameters.
     *
     * @param stdClass $adaptivequiz An 'adaptivequiz' instance.
     */
    public static function is_all_valid_for_adaptivequiz(stdClass $adaptivequiz): bool {
        if (!self::is_all_set_for_adaptivequiz($adaptivequiz)) {
            return false;
        }

        $paramsvalidation = self::get_validation_results_for_adaptivequiz($adaptivequiz);
        foreach ($paramsvalidation as $validationresult) {
            $validationfailed = $validationresult != '';
            if ($validationfailed) {
                return false;
            }
        }

        return true;
    }
}
