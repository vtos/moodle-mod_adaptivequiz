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

namespace mod_adaptivequiz;

/**
 * Defines placeholders available in the custom attempt feedback text.
 *
 * @package    mod_adaptivequiz
 * @copyright  2025 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
enum attempt_feedback_placeholder_option: string implements editor_placeholder_option {

    case ABILITY_MEASURE = 'abilitymeasure';

    case QUESTION_LOWEST_LEVEL = 'qlowestlevel';

    case QUESTION_HIGHEST_LEVEL = 'qhighestlevel';

    /**
     * Implements the interface.
     */
    public function id(): string {
        return $this->value;
    }

    /**
     * Implements the interface.
     */
    public function key(): string {
        return '{{'. $this->value .'}}';
    }

    /**
     * Implements the interface.
     */
    public function description(): string {
        return match ($this) {
            self::ABILITY_MEASURE => get_string('attemptquestion_ability', 'adaptivequiz'),
            self::QUESTION_LOWEST_LEVEL => get_string('lowestlevel', 'adaptivequiz'),
            self::QUESTION_HIGHEST_LEVEL => get_string('highestlevel', 'adaptivequiz'),
        };
    }
}
