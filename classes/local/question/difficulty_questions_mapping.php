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

use coding_exception;
use mod_adaptivequiz\local\repository\questions_number_per_difficulty;

/**
 * Defines mapping of difficulty levels and the number of questions associated with each level.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class difficulty_questions_mapping {

    /**
     * @var array<int, int> $mapping The key is difficulty level, the value is number of questions.
     */
    private $mapping = [];

    /**
     * The constructor, empty.
     */
    public function __construct() {
    }

    /**
     * Increases sum of questions for the given difficulty by the given number and returns a new object.
     *
     * @param int $difficulty
     * @param int $add
     * @return void
     */
    public function add_to_questions_number_for_difficulty(int $difficulty, int $add): self {
        $mapping = $this->mapping;

        if (!array_key_exists($difficulty, $mapping)) {
            $mapping[$difficulty] = 0;
        }

        $mapping[$difficulty] += $add;

        $newobject = new self();
        $newobject->mapping = $mapping;

        return $newobject;
    }

    /**
     * Decrements the number of questions for the given difficulty and return a new object.
     *
     * @param int $difficulty
     * @return self
     * @throws coding_exception
     */
    public function decrement_questions_number_for_difficulty(int $difficulty): self {
        if (!array_key_exists($difficulty, $this->mapping)) {
            throw new coding_exception('unknown difficulty level to decrement the number of questions for: '. $difficulty);
        }

        $mapping = $this->mapping;
        $mapping[$difficulty]--;

        $newobject = new self();
        $newobject->mapping = $mapping;

        return $newobject;
    }

    /**
     * Reports whether the given difficulty contains any questions associated.
     *
     * @param int $difficulty
     * @return bool
     */
    public function questions_exist_for_difficulty(int $difficulty): bool {
        if (!array_key_exists($difficulty, $this->mapping)) {
            return false;
        }

        return $this->mapping[$difficulty] > 0;
    }

    /**
     * Reports whether the mapping contains no meaningful data.
     *
     * @return bool
     */
    public function is_empty(): bool {
        return empty($this->mapping);
    }

    /**
     * Serializes the object.
     *
     * @return int[]
     */
    public function as_array(): array {
        return $this->mapping;
    }

    /**
     * Instantiates an object with empty mapping.
     *
     * @return self
     */
    public static function create_empty(): self {
        return new self();
    }

    /**
     * Instantiates an object from array of single typed mappings.
     *
     * @param questions_number_per_difficulty[] $mappings
     * @return self
     */
    public static function from_array_of_single_mappings(array $mappings): self {
        $object = new self();

        foreach ($mappings as $difficultyquestionsmapping) {
            $object->mapping[$difficultyquestionsmapping->difficulty()] = $difficultyquestionsmapping->questions_number();
        }

        return $object;
    }
}
