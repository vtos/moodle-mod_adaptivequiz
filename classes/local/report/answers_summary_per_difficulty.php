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

use coding_exception;

/**
 * Encapsulates info about the number of correct and incorrect answers for a difficulty level.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class answers_summary_per_difficulty {

    /**
     * @var answers_summary[] $summaryperdifficulty The key is difficulty level.
     */
    private $summaryperdifficulty = [];

    /**
     * The constructor, closed and empty, the factory method should be used instead.
     */
    private function __construct() {
    }

    public static function from_difficulty_range(questions_difficulty_range $difficultyrange): self {
        $summaryperdifficulty = new self();

        for ($i = $difficultyrange->lowest_level(); $i <= $difficultyrange->highest_level(); $i++) {
            $summaryperdifficulty->summaryperdifficulty[$i] = new answers_summary(0, 0);
        }

        return $summaryperdifficulty;
    }

    /**
     * Returns an object with incremented number of correct answers.
     *
     * @param int $difficulty
     * @return self
     */
    public function increment_number_of_correct_answers_for_difficulty(int $difficulty): self {
        $this->ensure_difficulty_is_initialized($difficulty);

        $currentanswersummary = $this->summaryperdifficulty[$difficulty];

        $this->summaryperdifficulty[$difficulty] = new answers_summary(
            $currentanswersummary->number_of_correct_answers() + 1,
            $currentanswersummary->number_of_incorrect_answers()
        );

        return $this;
    }

    /**
     * Returns an object with decremented number of correct answers.
     *
     * @param int $difficulty
     * @return self
     */
    public function increment_number_of_incorrect_answers_for_difficulty(int $difficulty): self {
        $this->ensure_difficulty_is_initialized($difficulty);

        $currentanswersummary = $this->summaryperdifficulty[$difficulty];

        $this->summaryperdifficulty[$difficulty] = new answers_summary(
            $currentanswersummary->number_of_correct_answers(),
            $currentanswersummary->number_of_incorrect_answers() + 1
        );

        return $this;
    }

    /**
     * Returns an answers summary object for the requested difficulty.
     *
     * @param int $difficulty
     * @return answers_summary
     * @throws coding_exception
     */
    public function answers_summary_for_difficulty(int $difficulty): answers_summary {
        if (!array_key_exists($difficulty, $this->summaryperdifficulty)) {
            throw new coding_exception('unknown question difficulty requested: '. $difficulty);
        }

        return $this->summaryperdifficulty[$difficulty];
    }

    /**
     * Checks whether the difficulty has been initialized with answers summary.
     *
     * @param int $difficulty
     * @return void
     */
    private function ensure_difficulty_is_initialized(int $difficulty): void {
        if (array_key_exists($difficulty, $this->summaryperdifficulty)) {
            return;
        }

        $this->summaryperdifficulty[$difficulty] = new answers_summary(0, 0);
    }
}
