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
 * The object of this class contains the data required to update attempt's state after a question had been answered. The data
 * normally comes from the CAT algorithm, thus, this is a simple data-transfer object with public properties.
 *
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_adaptivequiz\local\attempt;

use mod_adaptivequiz\local\catalgorithm\difficulty_logit;

final class cat_calculation_steps_result {

    /**
     * @var difficulty_logit $logit
     */
    private $logit;

    /**
     * @var float $standarderror
     */
    private $standarderror;

    /**
     * @var float $measure
     */
    private $measure;

    public function __construct(difficulty_logit $logit, float $standarderror, float $measure) {
        $this->logit = $logit;
        $this->standarderror = $standarderror;
        $this->measure = $measure;
    }

    public function logit(): difficulty_logit {
        return $this->logit;
    }

    public function standard_error(): float {
        return $this->standarderror;
    }

    public function measure(): float {
        return $this->measure;
    }

    public static function from_floats(float $logit, float $standarderror, float $measure): self {
        return new self(difficulty_logit::from_float($logit), $standarderror, $measure);
    }
}
