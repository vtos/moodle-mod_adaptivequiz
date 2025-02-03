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

use context_module;
use mod_adaptivequiz\attempt_feedback_placeholders_helper;
use mod_adaptivequiz\external\ability_measure_exporter;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Output object to render the page which is displayed when an attempt is finished.
 *
 * @package    mod_adaptivequiz
 * @copyright  2024 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_feedback implements renderable, templatable {

    /**
     * @var stdClass $adaptivequiz
     */
    private $adaptivequiz;

    /**
     * @var stdClass $cm
     */
    private $cm;

    /**
     * @var stdClass $attempt
     */
    private $attempt;

    /**
     * @var ability_measure|null $abilitymeasure
     */
    private $abilitymeasure = null;

    /**
     * Empty and closed, the factory method must be used instead.
     */
    private function __construct() {
    }

    /**
     * Implements the interface.
     *
     * @param renderer_base $output
     * @return \stdClass|array
     */
    public function export_for_template(renderer_base $output) {
        $feedbacktext = call_user_func(function (stdClass $adaptivequiz, stdClass $cm): string {
            if ($adaptivequiz->attemptfeedbackenable == -1) {
                // -1 means the feedback text was inherited from the previous version of the plugin and hasn't yet been reviewed
                // on the instance's settings page. In this case, if the text isn't empty, we return it as it was in
                // the previous version.
                if (!empty(trim($adaptivequiz->attemptfeedback))) {
                    return s($adaptivequiz->attemptfeedback);
                }
            }

            // The case when the feedback was set using the editor.
            if ($adaptivequiz->attemptfeedbackenable == 1) {
                $abilitymeasureexporter = (new ability_measure_exporter([
                    'highestlevel' => $this->adaptivequiz->highestlevel,
                    'lowestlevel' => $this->adaptivequiz->lowestlevel,
                ], [
                    'attempt' => $this->attempt,
                ]));

                $placeholdershelper = attempt_feedback_placeholders_helper::configured();
                $feedbacktext = $placeholdershelper->format_feedback_text($adaptivequiz->attemptfeedback, $abilitymeasureexporter);

                $context = context_module::instance($cm->id);
                $options = ['noclean' => true, 'para' => false, 'filter' => true, 'context' => $context, 'overflowdiv' => true];
                $feedbacktext = file_rewrite_pluginfile_urls($feedbacktext, 'pluginfile.php', $context->id,
                    'mod_adaptivequiz', 'attemptfeedback', 0);
                $feedbacktext = trim(format_text($feedbacktext, $adaptivequiz->attemptfeedbackformat, $options));

                return $feedbacktext;
            }

            // The fall-back case: just the default text.
            return get_string('attemptfeedbackdefaulttext', 'adaptivequiz');
        }, $this->adaptivequiz, $this->cm);

        $abilitymeasure = [];
        if ($this->adaptivequiz->showabilitymeasurefeedback) {
            $abilitymeasure = $this->abilitymeasure->export_for_template($output);
        }

        return array_merge([
            'feedbacktext' => $feedbacktext,
            'showabilitymeasure' => $this->adaptivequiz->showabilitymeasurefeedback,
        ], $abilitymeasure);
    }

    /**
     * A factory method to wrap proper instantiation of the renderable.
     *
     * @param stdClass $adaptivequiz
     * @param stdClass $cm
     * @param stdClass $attempt
     */
    public static function create(stdClass $adaptivequiz, stdClass $cm, stdClass $attempt): self {
        $feedback = new self();
        $feedback->adaptivequiz = $adaptivequiz;
        $feedback->cm = $cm;
        $feedback->attempt = $attempt;

        if ($adaptivequiz->showabilitymeasurefeedback) {
            $feedback->abilitymeasure = new ability_measure($adaptivequiz, $attempt);
        }

        return $feedback;
    }
}
