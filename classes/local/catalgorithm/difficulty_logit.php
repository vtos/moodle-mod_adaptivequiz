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

/**
 * The class represents the sum of difficulty levels of the questions attempted measured in logits.
 *
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_adaptivequiz\local\catalgorithm;

use InvalidArgumentException;

final class difficulty_logit {

    /**
     * @var float $value
     */
    private $value;

    private function __construct(float $value) {
        if ($value === INF) {
            throw new InvalidArgumentException('unexpected infinite value for the logit');
        }

        $this->value = $value;
    }

    public function as_float(): float {
        return $this->value;
    }

    public static function from_float(float $value): self {
        return new self($value);
    }
}
