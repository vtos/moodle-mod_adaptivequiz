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

/**
 * A value object containing info about the next item (question) to be administered during a CAT session.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class next_item {

    /**
     * @var int $slot Slot of the next question (that is the item), a quba's thing.
     */
    private $slot;

    /**
     * The constructor.
     *
     * @param int $slot
     */
    public function __construct(int $slot) {
        $this->slot = $slot;
    }

    /**
     * Queries for the slot property.
     */
    public function slot(): int {
        return $this->slot;
    }
}
