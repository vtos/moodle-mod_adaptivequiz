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

namespace mod_adaptivequiz\local\itemadministration;

use coding_exception;

/**
 * A value object containing info about the next item (question) to be administered during a CAT session.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class next_item {

    /**
     * @var int $questionid
     */
    private $questionid;

    /**
     * @var int $qubaslot
     */
    private $qubaslot;

    /**
     * The constructor, closed, names constructors must be used instead.
     *
     * @param int|null $questionid
     * @param int|null $qubaslot
     * @throws coding_exception
     */
    private function __construct(?int $questionid, ?int $qubaslot) {
        if (!is_null($questionid) && $questionid <= 0) {
            throw new coding_exception('a positive integer is expected for the question id');
        }

        if (!is_null($qubaslot) && $qubaslot <= 0) {
            throw new coding_exception('a positive integer is expected for the slot number');
        }

        $this->questionid = $questionid;
        $this->qubaslot = $qubaslot;
    }

    /**
     * Property getter.
     */
    public function question_id(): ?int {
        return $this->questionid;
    }

    /**
     * Property getter.
     */
    public function quba_slot(): ?int {
        return $this->qubaslot;
    }

    /**
     * A named constructor.
     *
     * @param int $questionid
     * @return self
     */
    public static function from_question_id(int $questionid): self {
        return new self($questionid, null);
    }

    /**
     * A named constructor.
     *
     * @param int $qubaslot
     * @return self
     */
    public static function from_quba_slot(int $qubaslot): self {
        return new self(null, $qubaslot);
    }
}
