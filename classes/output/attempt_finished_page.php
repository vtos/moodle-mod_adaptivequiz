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
use renderer_base;
use stdClass;
use templatable;

/**
 * Output object to render the page which is displayed when an attempt is finished.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_finished_page implements renderable, templatable {

    /**
     * @var bool $browsersecurityenabled
     */
    private $browsersecurityenabled;

    /**
     * @var attempt_feedback $attemptfeedback
     */
    private $attemptfeedback;

    /**
     * @var moodle_url $continueurl
     */
    private $continueurl;

    /**
     * Empty and closed, the factory method must be used instead.
     */
    private function __construct() {
    }

    /**
     * A factory method to wrap proper instantiation of the renderable.
     *
     * @param stdClass $adaptivequiz
     * @param stdClass $cm
     * @param stdClass $attempt
     */
    public static function create(stdClass $adaptivequiz, stdClass $cm, stdClass $attempt): self {
        $page = new self();
        $page->browsersecurityenabled = $adaptivequiz->browsersecurity;
        $page->attemptfeedback = attempt_feedback::create($adaptivequiz, $cm, $attempt);
        $page->continueurl = new moodle_url('/mod/adaptivequiz/view.php', ['id' => $cm->id]);

        return $page;
    }

    /**
     * Implements the interface.
     *
     * @param renderer_base $output
     * @return stdClass|array
     */
    public function export_for_template(renderer_base $output) {
        return array_merge([
            'browsersecurityenabled' => $this->browsersecurityenabled,
            'continuebutton' => $output->continue_button($this->continueurl),
        ], $this->attemptfeedback->export_for_template($output));
    }
}
