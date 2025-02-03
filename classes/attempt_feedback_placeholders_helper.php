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

use mod_adaptivequiz\external\ability_measure_exporter;

/**
 * A class to manage placeholders in custom attempt feedback's text editor.
 *
 * @package    mod_adaptivequiz
 * @copyright  2025 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_feedback_placeholders_helper {

    /**
     * @var attempt_feedback_placeholder_option[] $options Placeholders configuration.
     */
    private array $options = [];

    /**
     * Empty and closed, the factory method must be used instead.
     */
    public function __construct() {
    }

    /**
     * Returns an instance of placeholders collection populated with the options.
     */
    public function placeholder_options(): editor_placeholders {
        $placeholders = new editor_placeholders();
        foreach ($this->options as $option) {
            $placeholders->add_option($option);
        }

        return $placeholders;
    }

    /**
     * Searches for placeholders in the given attempt feedback text and replaces it with corresponding values.
     *
     * @param string $text Text with placeholder.
     * @param ability_measure_exporter $abilitymeasureexporter Contains the placeholders' values.
     * @return string The text with replaced placeholders.
     */
    public function format_feedback_text(string $text, ability_measure_exporter $abilitymeasureexporter): string {
        global $PAGE;

        $output = $PAGE->get_renderer('core');
        $abilitymeasuredata = $abilitymeasureexporter->export($output);

        $abilitymeasuredatamap = [
            attempt_feedback_placeholder_option::ABILITY_MEASURE->key() => $abilitymeasuredata->abilitymeasurevalue,
            attempt_feedback_placeholder_option::QUESTION_LOWEST_LEVEL->key() => $abilitymeasuredata->lowestlevel,
            attempt_feedback_placeholder_option::QUESTION_HIGHEST_LEVEL->key() => $abilitymeasuredata->highestlevel,
        ];

        $toreplace = array_keys($abilitymeasuredatamap);
        $replacewith = array_values($abilitymeasuredatamap);

        return str_replace($toreplace, $replacewith, $text);
    }

    /**
     * Contains the default instantiation of the placeholder options.
     */
    public static function configured(): self {
        $helper = new self();
        $helper->options = [
            attempt_feedback_placeholder_option::ABILITY_MEASURE,
            attempt_feedback_placeholder_option::QUESTION_LOWEST_LEVEL,
            attempt_feedback_placeholder_option::QUESTION_HIGHEST_LEVEL,
        ];

        return $helper;
    }
}
