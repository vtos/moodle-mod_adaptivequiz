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

namespace adaptivequizcatmodel_helloworld\local\report;

/**
 * Attempts filter.
 *
 * @package    adaptivequizcatmodel_helloworld
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class attempts_filter {

    /**
     * @var int $adaptivequizid
     */
    public $adaptivequizid;

    /**
     * The constructor, may be used for quicker instantiation.
     */
    public function __construct(int $adaptivequizid) {
        $this->adaptivequizid = $adaptivequizid;
    }
}
