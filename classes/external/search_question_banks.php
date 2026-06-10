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

namespace mod_adaptivequiz\external;

use core\context;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_question\local\bank\question_bank_helper;
use mod_adaptivequiz\item_bank;
use stdClass;

/**
 * Returns a list of filtered question banks.
 *
 * @package    mod_adaptivequiz
 * @copyright  2026 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_question_banks extends external_api {

    /**
     * @var int The maximum number of banks to return.
     */
    const MAX_RESULTS = 20;

    /**
     * Return values definition.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'questionbanks' => new external_multiple_structure(
                new external_single_structure([
                    'value' => new external_value(PARAM_INT, 'ID of the qbank instance.'),
                    'label' => new external_value(PARAM_TEXT, 'Formatted bank name'),
                ]),
                'List of question banks',
            ),
        ]);
    }

    /**
     * Parameters definition.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'Context ID of the adaptive quiz module.'),
            'incourseid' => new external_value(
                PARAM_INT,
                'Course ID to get question banks from.',
                VALUE_DEFAULT,
                default: null,
            ),
            'notincourseid' => new external_value(
                PARAM_INT,
                'Course ID to exclude.',
                VALUE_DEFAULT,
                default: null,
            ),
            'search' => new external_value(PARAM_TEXT, 'Search terms by which to filter the banks.', default: ''),
        ]);
    }

    /**
     * Main.
     *
     * @param int $contextid Context ID of the adaptive quiz module.
     * @param string $search String to filter results by question bank name.
     * @param int|null $incourseid Specific course ID to get banks from.
     * @param int|null $notincourseid Course ID to exclude.
     */
    public static function execute(
        int $contextid,
        ?int $incourseid = null,
        ?int $notincourseid = null,
        string $search = ''
    ): array {
        [
            'contextid' => $contextid,
            'incourseid' => $incourseid,
            'notincourseid' => $notincourseid,
            'search' => $search,
        ] = self::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
            'incourseid' => $incourseid,
            'notincourseid' => $notincourseid,
            'search' => $search,
        ]);

        $context = context::instance_by_id($contextid);
        self::validate_context($context);

        $cm = get_coursemodule_from_id(modulename: 'adaptivequiz', cmid: $context->instanceid, strictness: MUST_EXIST);
        $currentassignments = item_bank::get_question_banks_assigned_to_adaptivequiz(
            $cm->instance,
            'id',
            $incourseid,
            $notincourseid
        );

        $excludeqbankidlist = array_map(fn (stdClass $qbank) => $qbank->id, $currentassignments);

        $qbanks = question_bank_helper::get_activity_instances_with_shareable_questions(
            incourseids: $incourseid ? [$incourseid] : [],
            notincourseids: $notincourseid ? [$notincourseid] : [],
            filtercontext: $context,
            search: $search,
            limit: self::MAX_RESULTS + 1, // Return up to 1 extra result, so we know there are more.
        );

        $qbanks = array_filter($qbanks, function ($qbank) use ($excludeqbankidlist) {
            return !in_array($qbank->cminfo->instance, $excludeqbankidlist);
        });

        $suggestions = array_map(function ($qbank) {
            // The types returned by get_activity_instances_with_shareable_questions() are different across 5.x
            // versions.
            if ($qbank instanceof stdClass) {
                return ['value' => $qbank->cminfo->instance, 'label' => $qbank->coursenamebankname];
            }

            /** @var \core_question\local\bank\formatted_bank $qbank */
            return ['value' => $qbank->cminfo->instance, 'label' => $qbank->get_formatted()->coursenamebankname];
        }, $qbanks);

        sort($suggestions);

        if (count($suggestions) > self::MAX_RESULTS) {
            // If there are too many results, replace the last one with a placeholder.
            $suggestions[array_key_last($suggestions)] = [
                'value' => 0,
                'label' => get_string('otherquestionbankstoomany', 'question', self::MAX_RESULTS),
            ];
        }

        return [
            'questionbanks' => $suggestions,
        ];
    }
}
