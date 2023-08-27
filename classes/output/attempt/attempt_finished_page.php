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

namespace mod_adaptivequiz\output\attempt;

use cm_info;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Output object to render the page, which is displayed when an attempt is finished.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_finished_page implements renderable, templatable {

    /**
     * @var string $feedback
     */
    private $feedback;

    /**
     * @var moodle_url $continueurl
     */
    private $continueurl;

    /**
     * @var bool $browsersecurityenabled
     */
    private $browsersecurityenabled;

    /**
     * Empty and closed, the factory method must be used instead.
     */
    private function __construct() {
    }

    /**
     * A factory method to properly instantiate an object of the class.
     *
     * When a CAT model sub-plugin is used, the method tries to wire up its implementation of the attempt feedback callback
     * to obtain the feedback content.
     *
     * @param stdClass $attemptrecord A record from the {adaptivequiz_attempt} table.
     * @param stdClass $adaptivequiz A record from the {adaptivequiz} table.
     * @param cm_info $cm
     * @return self
     */
    public static function for_attempt_on_adaptive_quiz(stdClass $attemptrecord, stdClass $adaptivequiz, cm_info $cm): self {
        global $PAGE;

        $page = new self();
        $page->continueurl = new moodle_url('/mod/adaptivequiz/view.php', ['id' => $cm->id]);
        $page->browsersecurityenabled = $adaptivequiz->browsersecurity;

        if ($adaptivequiz->catmodel) {
            // Try wire up the custom feedback from the sub-plugin being used. If it's implemented in a sub-plugin, it always has
            // a precedence over the default feedback provided by the activity.
            $pluginswithfunction = get_plugin_list_with_function('adaptivequizcatmodel', 'attempt_finished_feedback');
            $catmodelcomponentname = 'adaptivequizcatmodel_' . $adaptivequiz->catmodel;
            if (array_key_exists($catmodelcomponentname, $pluginswithfunction)) {
                $functionname = $pluginswithfunction[$catmodelcomponentname];
                $page->feedback = $functionname($adaptivequiz, $cm, $attemptrecord);

                return $page;
            }
        }

        $page->feedback = $PAGE->get_renderer('mod_adaptivequiz')
            ->render(attempt_finished_feedback::for_attempt_on_adaptive_quiz($attemptrecord, $adaptivequiz));

        return $page;
    }

    /**
     * Implements the interface.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        return [
            'feedback' => $this->feedback,
            'continuebutton' => $output->continue_button($this->continueurl),
            'browsersecurityenabled' => $this->browsersecurityenabled,
        ];
    }
}
