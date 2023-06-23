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

use context_module;
use mod_adaptivequiz\local\attempt\attempt;
use moodle_url;
use renderable;
use stdClass;

/**
 * Output object to display the number of attempts for the given adaptive quiz activity.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempts_number implements renderable {

    /**
     * @var int $number The number of attempts to include to the link's text.
     */
    public $number;

    /**
     * @var bool $show Whether the attempt number should be shown (may depend on capabilities, for example).
     */
    public $show;

    /**
     * @var moodle_url|null $reporturl
     */
    public $reporturl;

    /**
     * The constructor, closed.
     *
     * The factory method must be used instead.
     *
     * @param int $number
     * @param bool $show
     * @param moodle_url|null $reporturl
     */
    private function __construct(int $number, bool $show, ?moodle_url $reporturl) {
        $this->number = $number;
        $this->show = $show;
        $this->reporturl = $reporturl;
    }

    /**
     * A factory method to instantiate an object.
     *
     * This is the only proper method of instantiating an object of the class, as it checks whether a CAT model sub-plugin
     * is being used and tries to pick up the report link from it.
     *
     * @param stdClass $adaptivequiz
     * @param stdClass $cm
     * @return self
     */
    public static function create(stdClass $adaptivequiz, stdClass $cm): self {
        $attemptsnumber = attempt::total_number($adaptivequiz->id);
        $showattemptsnumber = has_capability('mod/adaptivequiz:viewreport', context_module::instance($cm->id));

        if (!$adaptivequiz->catmodel) {
            $reportlink = new moodle_url('/mod/adaptivequiz/viewattemptreport.php', ['id' => $cm->id]);

            return new self($attemptsnumber, $showattemptsnumber, $reportlink);
        }

        $pluginswithfunction = get_plugin_list_with_function('adaptivequizcatmodel', 'attempts_report_url');
        $catmodelcomponentname = 'adaptivequizcatmodel_' . $adaptivequiz->catmodel;
        if (!array_key_exists($catmodelcomponentname, $pluginswithfunction)) {
            return new self($attemptsnumber, $showattemptsnumber, null);
        }

        $functionname = $pluginswithfunction[$catmodelcomponentname];
        $reporturl = $functionname($adaptivequiz, $cm);
        if (!($reporturl instanceof moodle_url)) {
            return new self($attemptsnumber, $showattemptsnumber, null);
        }

        return new self($attemptsnumber, $showattemptsnumber, $reporturl);
    }
}
