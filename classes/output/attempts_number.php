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

/**
 * Output object to display the number of attempts in teh given adaptive quiz activity.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempts_number implements renderable {

    /**
     * @var moodle_url $reporturl
     */
    public $reporturl;

    /**
     * @var int $number
     */
    public $number;

    /**
     * The constructor.
     *
     * @param moodle_url $reporturl
     * @param int $number
     */
    public function __construct(moodle_url $reporturl, int $number) {
        $this->reporturl = $reporturl;
        $this->number = $number;
    }
}
