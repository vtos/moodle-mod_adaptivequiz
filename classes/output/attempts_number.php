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

namespace mod_adaptivequiz\output;

use moodle_url;
use renderable;
use stdClass;

/**
 * Output object to display the number of attempts for the given adaptive quiz activity.
 *
 * Intended to be used for the adaptive quizzes utilizing a custom CAT model where the default attempts report cannot be displayed
 * and a link to an alternative report is displayed instead.
 *
 * @package    mod_adaptivequiz
 * @copyright  2024 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempts_number implements renderable {

    /**
     * @var int $number
     */
    public $number;

    /**
     * @var moodle_url|null $reporturl
     */
    public $reporturl;

    /**
     * Instantiates a proper object for the case when a custom CAT model is in use.
     *
     * @param stdClass $adaptivequiz
     * @param stdClass $cm
     */
    public static function when_custom_catmodel_in_use(stdClass $adaptivequiz, stdClass $cm): self {
        global $DB;

        $attemptsnumber = new self;
        $attemptsnumber->number = $DB->count_records('adaptivequiz_attempt', ['instance' => $adaptivequiz->id]);
        $attemptsnumber->reporturl = null;

        if (empty($adaptivequiz->catmodel)) {
            return $attemptsnumber;
        }

        $pluginswithfunction = get_plugin_list_with_function('adaptivequizcatmodel', 'attempts_report_url');
        $catmodelcomponentname = 'adaptivequizcatmodel_' . $adaptivequiz->catmodel;
        if (!array_key_exists($catmodelcomponentname, $pluginswithfunction)) {
            return $attemptsnumber;
        }

        $functionname = $pluginswithfunction[$catmodelcomponentname];
        $attemptsnumber->reporturl = $functionname($adaptivequiz, $cm);

        return $attemptsnumber;
    }
}
