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

use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use mod_adaptivequiz\item_administration_params_helper;
use mod_adaptivequiz\item_bank;
use stdClass;

/**
 * Output class to render information about item administration parameters.
 *
 * @package    mod_adaptivequiz
 * @copyright  2026 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_administration_params implements renderable, templatable {

    /**
     * The constructor.
     *
     * @param stdClass $adaptivequiz An instance of the adaptive quiz activity.
     */
    public function __construct(private readonly stdClass $adaptivequiz) {
    }

    /**
     * Implements the interface.
     *
     * @param renderer_base $output
     * @return \stdClass|array
     */
    public function export_for_template(renderer_base $output) {
        $hasqbanks = item_bank::adaptive_quiz_instance_has_question_banks_or_categories_linked($this->adaptivequiz->id);

        $paramset = item_administration_params_helper::is_all_set_for_adaptivequiz($this->adaptivequiz);

        $paramsinfo = [];
        if ($paramset) {
            $paramsvalidation = item_administration_params_helper::get_validation_results_for_adaptivequiz(
                $this->adaptivequiz
            );

            foreach ($paramsvalidation as $field => $validationresult) {
                $paramsinfo[] = [
                    // TODO: consider an exporter for this data.
                    'name' => get_string($field, 'adaptivequiz'),
                    'value' => $this->adaptivequiz->{$field},
                    'error' => $validationresult,
                ];
            }
        }

        $invalidparams = false;
        foreach ($paramsinfo as $paraminfo) {
            if ($paraminfo['error']) {
                $invalidparams = true;

                break;
            }
        }

        return [
            'hasqbanks' => $hasqbanks,
            'noqbanksnotification' => [
                'message' => get_string('itembankitemadmnoqbanks', 'adaptivequiz'),
            ],

            'paramset' => $paramset,
            'noparamsnotification' => !$paramset
                ? ['message' => get_string('itembankitemadmnoparams', 'adaptivequiz')]
                : null,
            'invalidparamsnotification' => $invalidparams
                ? ['message' => get_string('itembankitemadminvalidparams', 'adaptivequiz')]
                : null,

            // TODO.
            'itemadministrationparams' => $paramsinfo,
        ];
    }
}
