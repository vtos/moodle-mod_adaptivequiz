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

use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Output object to render debugging info for an attempt.
 *
 * @package    mod_adaptivequiz
 * @copyright  2026 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_debug_info implements renderable, templatable {

    /**
     * @var stdClass $attempt A record from {adaptivequiz_attempt}.
     */
    private $attempt;

    /**
     * The constructor.
     *
     * @param stdClass $attempt
     */
    public function __construct(stdClass $attempt) {
        $this->attempt = $attempt;
    }

    /**
     * Implements the interface.
     *
     * @param renderer_base $output
     * @return stdClass|array
     */
    public function export_for_template(renderer_base $output) {
        return [
            'params' => [
                [
                    'name' => get_string('attemptquestion_diffsum', 'adaptivequiz'),
                    'value' => $this->attempt->difficultysum,
                ],
                [
                    'name' => get_string('standarderrorhdr', 'adaptivequiz'),
                    'value' => $this->attempt->standarderror,
                ],
                [
                    'name' => get_string('attemptquestion_abilitylogits', 'adaptivequiz'),
                    'value' => $this->attempt->measure,
                ],
                [
                    'name' => get_string('attemptstopcriteria', 'adaptivequiz'),
                    'value' => $this->attempt->attemptstopcriteria,
                ],
            ],
        ];
    }
}
