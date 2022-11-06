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

/**
 * This interface defines the methods required for pluggable statistic-results that may be added to the question analysis.
 *
 * @copyright  2013 Middlebury College {@link http://www.middlebury.edu/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_adaptivequiz\local\questionanalysis\statistics;

class percent_correct_statistic_result implements question_statistic_result {
    /** @var float $fraction  */
    protected $fraction = null;

    /**
     * Constructor
     *
     * @param float $fraction
     * @return void
     */
    public function __construct ($fraction) {
        $this->fraction = $fraction;
    }

    /**
     * A sortable version of the result.
     *
     * @return mixed string or numeric
     */
    public function sortable () {
        return $this->fraction;
    }

    /**
     * A printable version of the result.
     *
     * @param numeric $result
     * @return mixed string or numeric
     */
    public function printable () {
        return round($this->fraction * 100).'%';
    }
}
