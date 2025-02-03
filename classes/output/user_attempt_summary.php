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

use mod_adaptivequiz\local\catalgo;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Renders overview of a user's own single attempt on the view page.
 *
 * @package    mod_adaptivequiz
 * @copyright  2022 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_attempt_summary implements renderable, templatable {

    /**
     * @var stdClass $adaptivequiz
     */
    private $adaptivequiz;

    /**
     * @var stdClass $attempt
     */
    private $attempt;

    /**
     * The constructor.
     *
     * @param stdClass $adaptivequiz
     * @param stdClass $attempt
     */
    public function __construct(stdClass $adaptivequiz, stdClass $attempt) {
        $this->adaptivequiz = $adaptivequiz;
        $this->attempt = $attempt;
    }

    /**
     * Implements the interface.
     *
     * @param renderer_base $output
     * @return stdClass|array
     */
    public function export_for_template(renderer_base $output) {
        $return = [
            'attemptstate' => get_string('recent' . $this->attempt->attemptstate, 'adaptivequiz'),
            'attemptstateraw' => $this->attempt->attemptstate,
            'attempttimefinished' => $this->attempt->timemodified,
            'abilitymeasure' => null,
            'adaptivequizhighestlevel' => null,
            'adaptivequizlowestlevel' => null,
        ];

        if ($this->adaptivequiz->showabilitymeasuresummary) {
            $return['abilitymeasure'] = !is_null($this->attempt->measure)
                ? round(catalgo::map_logit_to_scale($this->attempt->measure, $this->adaptivequiz->highestlevel,
                    $this->adaptivequiz->lowestlevel), 2)
                : get_string('na', 'adaptivequiz');

            $return['adaptivequizhighestlevel'] = $this->adaptivequiz->highestlevel;
            $return['adaptivequizlowestlevel'] = $this->adaptivequiz->lowestlevel;
        }

        return (object) $return;
    }
}
