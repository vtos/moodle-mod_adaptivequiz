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

use cm_info;
use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use mod_adaptivequiz\item_bank;
use moodle_url;
use stdClass;

/**
 * Output class to render question banks linked to the activity's item bank.
 *
 * @package    mod_adaptivequiz
 * @copyright  2026 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_bank_qbanks implements renderable, templatable {

    /**
     * The constructor.
     *
     * @param stdClass $adaptivequiz An instance of the adaptive quiz activity.
     * @param cm_info $cm The adaptivequiz course module.
     */
    public function __construct(readonly stdClass $adaptivequiz, readonly cm_info $cm) {
    }

    /**
     * Implements the interface.
     *
     * @param renderer_base $output
     * @return \stdClass|array
     */
    public function export_for_template(renderer_base $output) {
        $thiscourseqbanks = item_bank::get_question_banks_assigned_to_adaptivequiz(
            adaptivequizid: $this->adaptivequiz->id,
            fields: 'id, name',
            incourseid: $this->adaptivequiz->course
        );

        // TODO: preload a cm in the item_bank API.
        array_walk(
            $thiscourseqbanks,
            function($qbank) {
                $cm = get_coursemodule_from_instance('qbank', $qbank->id);
                $qbank->cminfo = cm_info::create($cm);
            }
        );

        // Reset keys for the template.
        sort($thiscourseqbanks);

        $othercoursesqbanks = item_bank::get_question_banks_assigned_to_adaptivequiz(
            adaptivequizid:$this->adaptivequiz->id,
            fields: 'id, name',
            notincourseid: $this->adaptivequiz->course
        );

        // TODO: preload a cm in the item_bank API.
        array_walk(
            $othercoursesqbanks,
            function($qbank) {
                $cm = get_coursemodule_from_instance('qbank', $qbank->id);
                $qbank->cminfo = cm_info::create($cm);
            }
        );

        // Reset keys for the template.
        sort($othercoursesqbanks);

        return [
            'id' => $this->cm->id,
            'courseid' => $this->adaptivequiz->course,
            'hasthiscourseqbanks' => $thiscourseqbanks !== [],

            // TODO: consider extracting this to an exporter.
            'thiscourseqbanks' => array_map(
                fn(stdClass $qbank): array => [
                    'name' => $qbank->name,
                    'url' => $qbank->cminfo->get_url(),
                    'actions' => [
                        [
                            'url' => new moodle_url(
                                '/mod/adaptivequiz/itembank.php',
                                ['id' => $this->cm->id, 'unassignqbank' => $qbank->id]
                            ),
                            'icon' => ['key' => 't/delete', 'component' => 'core'],
                            'title' => get_string('itembankunlinkqbank', 'adaptivequiz'),
                        ],
                    ],
                ],
                $thiscourseqbanks
            ),
            'hasothercoursesqbanks' => $othercoursesqbanks !== [],

            // TODO: consider extracting this to an exporter.
            'othercoursesqbanks' => array_map(
                fn(stdClass $qbank): array => [
                    'name' => $qbank->name,
                    'url' => $qbank->cminfo->get_url(),
                    'actions' => [
                        [
                            'url' => new moodle_url(
                                '/mod/adaptivequiz/itembank.php',
                                ['id' => $this->cm->id, 'unassignqbank' => $qbank->id]
                            ),
                            'icon' => ['key' => 't/delete', 'component' => 'core'],
                            'title' => get_string('itembankunlinkqbank', 'adaptivequiz'),
                        ],
                    ],
                ],
                $othercoursesqbanks
            ),
            'hasanyqbanks' => $thiscourseqbanks !== [] || $othercoursesqbanks !== [],
        ];
    }
}
