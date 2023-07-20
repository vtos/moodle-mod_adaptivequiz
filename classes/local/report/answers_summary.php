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

namespace mod_adaptivequiz\local\report;

/**
 * Encapsulates correct and incorrect answers data to pass around.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class answers_summary {

    /**
     * @var int $numberofcorrectanswers
     */
    private $numberofcorrectanswers;

    /**
     * @var int $numberofincorrectanswers
     */
    private $numberofincorrectanswers;

    /**
     * The constructor.
     */
    public function __construct(int $numberofcorrect, int $numberofincorrect) {
        $this->numberofcorrectanswers = $numberofcorrect;
        $this->numberofincorrectanswers = $numberofincorrect;
    }

    /**
     * Property getter.
     *
     * @return int
     */
    public function number_of_correct_answers(): int {
        return $this->numberofcorrectanswers;
    }

    /**
     * Property getter.
     *
     * @return int
     */
    public function number_of_incorrect_answers(): int {
        return $this->numberofincorrectanswers;
    }
}
