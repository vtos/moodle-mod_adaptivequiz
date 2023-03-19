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

namespace mod_adaptivequiz\local\itemadministration;

use coding_exception;

/**
 * A value object representing result of assessing whether the next item (question) can be administered during a CAT session.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_administration_evaluation {

    /**
     * @var next_item|null $nextitem
     */
    private $nextitem;

    /**
     * @var string|null $stoppagereason
     */
    private $stoppagereason;

    /**
     * The constructor.
     *
     * @param next_item|null $nextitem
     * @param string|null $stoppagereason
     * @throws coding_exception
     */
    public function __construct(?next_item $nextitem, ?string $stoppagereason) {
        if ($nextitem === null && $stoppagereason === null) {
            throw new coding_exception('next item and stoppage reason cannot be both null');
        }

        $this->nextitem = $nextitem;
        $this->stoppagereason = $stoppagereason;
    }

    /**
     * Returns next item object.
     */
    public function next_item(): ?next_item {
        return $this->nextitem;
    }

    /**
     * A shorthand method to know whether item administration should be stopped.
     */
    public function item_administration_is_to_stop(): bool {
        return $this->stoppagereason !== null;
    }

    /**
     * Return the stoppage reason.
     */
    public function stoppage_reason(): ?string {
        return $this->stoppagereason;
    }

    /**
     * A named constructor to quickly instantiate an evaluation object for the 'stoppage' result of evaluation.
     *
     * @param string $reason
     * @return self
     */
    public static function with_stoppage_reason(string $reason): self {
        return new self(null, $reason);
    }

    /**
     * A named constructor to quickly instantiate an evaluation object with the next item (question) data.
     *
     * @param next_item $item
     * @return self
     */
    public static function with_next_item(next_item $item): self {
        return new self($item, null);
    }
}
