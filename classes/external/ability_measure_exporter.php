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

namespace mod_adaptivequiz\external;

use core\external\exporter;
use mod_adaptivequiz\local\catalgo;
use renderer_base;

/**
 * Exporter class to format the ability measure value for output.
 *
 * Note, the output from this exporter is not intended for using in any further calculations. It's purely for output purposes:
 * output templates, external functions, etc.
 *
 * @package    mod_adaptivequiz
 * @copyright  2025 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ability_measure_exporter extends exporter {

    /**
     * Defines the list of properties to export.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'highestlevel' => [
                'type' => PARAM_INT,
            ],
            'lowestlevel' => [
                'type' => PARAM_INT,
            ],
        ];
    }

    /**
     * Defines the list of extra properties to export.
     *
     * Implements the parent's abstract method.
     *
     * @return array
     */
    protected static function define_other_properties() {
        return [
            'abilitymeasurevalue' => [
                'type' => PARAM_FLOAT,
            ],
        ];
    }

    /**
     * Returns a list of objects that are required to do the exporting.
     *
     * Overrides the parent's method.
     *
     * @return array
     */
    protected static function define_related() {
        return [
            'attempt' => 'stdClass',
        ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * Overrides the parent's method.
     *
     * @param renderer_base $output
     * @return array
     */
    protected function get_other_values(renderer_base $output) {
        $attempt = $this->related['attempt'];

        $measure = round(catalgo::map_logit_to_scale($attempt->measure, $this->data['highestlevel'],
            $this->data['lowestlevel']), 2);

        return [
            'abilitymeasurevalue' => $measure,
        ];
    }
}
