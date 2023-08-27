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

namespace mod_adaptivequiz\output;

use help_icon;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * An output object to present the ability measure to a user.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ability_measure implements renderable, templatable {

    /**
     * @var string $abilitymeasure
     */
    private $measurevalue;

    /**
     * @var string $lowestquestiondifficulty
     */
    private $lowestquestiondifficulty;

    /**
     * @var string $highestquestiondifficulty
     */
    private $highestquestiondifficulty;

    /**
     * @var help_icon $helpicon
     */
    private $helpicon;

    /**
     * Empty and closed, the factory method must be used instead.
     */
    private function __construct() {
    }

    /**
     * A named constructor to set up the object and increase code readability.
     *
     * @param stdClass $adaptivequiz A record from the {adaptivequiz} table.
     * @param string $measurevalue A ready for output value.
     * @return self
     */
    public static function of_attempt_on_adaptive_quiz(stdClass $adaptivequiz, string $measurevalue): self {
        $return = new self();
        $return->measurevalue = $measurevalue;
        $return->lowestquestiondifficulty = $adaptivequiz->lowestlevel;
        $return->highestquestiondifficulty = $adaptivequiz->highestlevel;
        $return->helpicon = new help_icon('abilityestimated', 'adaptivequiz');

        return $return;
    }

    /**
     * Implements the interface.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        return [
            'abilitymeasurevalue' => $this->measurevalue,
            'lowestdifficulty' => $this->lowestquestiondifficulty,
            'highestdifficulty' => $this->highestquestiondifficulty,
            'helpicon' => $output->render($this->helpicon),
        ];
    }
}
