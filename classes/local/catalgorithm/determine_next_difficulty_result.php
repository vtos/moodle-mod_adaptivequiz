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

declare(strict_types=1);

namespace mod_adaptivequiz\local\catalgorithm;

use coding_exception;

/**
 * A value object to shape the result of determining the next difficulty level.
 *
 * May contain an error message when applicable, or the next difficulty level when no error.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class determine_next_difficulty_result {

    /**
     * @var string|null $errormessage
     */
    private $errormessage;

    /**
     * @var int|null $nextdifficultylevel
     */
    private $nextdifficultylevel;

    /**
     * The constructor.
     *
     * @param string|null $errormessage
     * @param int|null $nextdifficultylevel
     * @throws coding_exception
     */
    public function __construct(?string $errormessage, ?int $nextdifficultylevel) {
        if (is_null($errormessage) && is_null($nextdifficultylevel)) {
            throw new coding_exception('error message and next difficulty level cannot be both null');
        }

        $this->errormessage = $errormessage;
        $this->nextdifficultylevel = $nextdifficultylevel;
    }

    /**
     * A shortcut method to check whether the result contains an error.
     *
     * @return bool
     */
    public function is_with_error(): bool {
        return !is_null($this->errormessage);
    }

    /**
     * Query the object for the value of error message.
     *
     * @return string|null
     */
    public function error_message(): ?string {
        return $this->errormessage;
    }

    /**
     * Query the object for the value of next difficulty level.
     *
     * @return int|null
     */
    public function next_difficulty_level(): ?int {
        return $this->nextdifficultylevel;
    }

    /**
     * A named constructor to instantiate an object with the error message.
     *
     * @param string $errormessage
     * @return self
     */
    public static function with_error(string $errormessage): self {
        return new self($errormessage, null);
    }

    /**
     * A named constructor to instantiate an object when no error and the next difficulty level is determined.
     *
     * @param int $nextdifficultylevel
     * @return self
     */
    public static function with_next_difficulty_level_determined(int $nextdifficultylevel): self {
        return new self(null, $nextdifficultylevel);
    }
}
