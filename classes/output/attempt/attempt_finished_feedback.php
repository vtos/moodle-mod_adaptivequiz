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

namespace mod_adaptivequiz\output\attempt;

use mod_adaptivequiz\local\attempt\cat_model_params;
use mod_adaptivequiz\output\ability_measure;
use renderable;
use renderer_base;
use templatable;
use stdClass;

/**
 * Output object to render the feedback, which is displayed to a test-taker when an attempt is finished.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_finished_feedback implements renderable, templatable {

    /**
     * @var string $feedbacktext
     */
    private $feedbacktext;

    /**
     * @var ability_measure|null $abilitymeasure Depends on the activity settings.
     */
    private $abilitymeasure;

    /**
     * Empty and closed, the factory method must be used instead.
     */
    private function __construct() {
    }

    /**
     * A factory method to properly instantiate an object of the class.
     *
     * @param stdClass $attemptrecord A record from the {adaptivequiz_attempt} table.
     * @param stdClass $adaptivequiz A record from the {adaptivequiz} table.
     * @return self
     */
    public static function for_attempt_on_adaptive_quiz(stdClass $attemptrecord, stdClass $adaptivequiz): self {
        global $PAGE;

        $feedback = new self();

        $feedback->feedbacktext = !empty($adaptivequiz->attemptfeedback)
            ? $adaptivequiz->attemptfeedback
            : get_string('attemptfeedbackdefaulttext', 'adaptivequiz');

        $displayabilitymeasure = !$adaptivequiz->catmodel && $adaptivequiz->showabilitymeasure;
        if (!$displayabilitymeasure) {
            return $feedback;
        }

        // Prepare a specific object to get the measure value formatted.
        $formatmeasurearg = new stdClass();
        $formatmeasurearg->measure = cat_model_params::for_attempt($attemptrecord->id)->get('measure');
        $formatmeasurearg->highestlevel = $adaptivequiz->highestlevel;
        $formatmeasurearg->lowestlevel = $adaptivequiz->lowestlevel;

        $feedback->abilitymeasure = ability_measure::of_attempt_on_adaptive_quiz(
            $adaptivequiz,
            $PAGE->get_renderer('mod_adaptivequiz')->format_measure($formatmeasurearg)

        );

        return $feedback;
    }

    /**
     * Implements the interface.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        return [
            'feedbacktext' => s($this->feedbacktext),
            'abilitymeasure' => !is_null($this->abilitymeasure) ? $output->render($this->abilitymeasure) : null,
        ];
    }
}
