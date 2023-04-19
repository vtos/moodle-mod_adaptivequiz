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

namespace mod_adaptivequiz\local\question;

/**
 * A data-transfer object to convey information on how many questions were answered correctly and incorrectly.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class questions_answered_summary {

    /**
     * @var int $numberofcorrectanswers
     */
    public $numberofcorrectanswers;

    /**
     * @var int $numberofwronganswers
     */
    public $numberofwronganswers;

    /**
     * Property getter.
     */
    public function number_of_wrong_answers(): int {
        return $this->numberofwronganswers;
    }

    /**
     * Property getter.
     */
    public function number_of_correct_answers(): int {
        return $this->numberofcorrectanswers;
    }

    /**
     * Returns sum of all answers given, both wrong and correct.
     */
    public function sum_of_answers(): int {
        return $this->numberofwronganswers + $this->numberofcorrectanswers;
    }

    /**
     * A named constructor for quick instantiation.
     *
     * @param int $numberofwronganswers
     * @param int $numberofcorrectanswers
     * @return questions_answered_summary
     */
    public static function from_integers(int $numberofwronganswers, int $numberofcorrectanswers): self {
        $summary = new self();
        $summary->numberofwronganswers = $numberofwronganswers;
        $summary->numberofcorrectanswers = $numberofcorrectanswers;

        return $summary;
    }
}
