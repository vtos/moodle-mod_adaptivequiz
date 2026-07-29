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

use context;
use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_question\local\bank\question_version_status;
use qbank_managecategories\helper;
use stdClass;

/**
 * Returns a list of question categories for a given question bank.
 *
 * @package    mod_adaptivequiz
 * @copyright  2026 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_question_categories extends external_api {

    /**
     * @var int The maximum number of categories to return.
     */
    const MAX_RESULTS = 50;

    /**
     * Return values definition for the external service.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'categories' => new external_multiple_structure(
                new external_single_structure([
                    'value' => new external_value(PARAM_RAW, 'Combined ID and context of the category (id,contextid).'),
                    'label' => new external_value(PARAM_TEXT, 'Formatted category name with question count.'),
                ]),
                'List of question categories',
            ),
        ]);
    }

    /**
     * Parameters definition for the external service.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'Context ID of the adaptive quiz module.'),
            'bankid' => new external_value(PARAM_INT, 'Question bank instance ID.'),
            'search' => new external_value(PARAM_TEXT, 'Search terms by which to filter categories.', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Main external service function to retrieve question categories for a given bank.
     *
     * @param int $contextid Context ID of the adaptive quiz module.
     * @param int $bankid Question bank instance ID.
     * @param string $search String to filter results by category name.
     * @return array Array with 'categories' key containing list of category options.
     */
    public static function execute(
        int $contextid,
        int $bankid,
        string $search = ''
    ): array {
        [
            'contextid' => $contextid,
            'bankid' => $bankid,
            'search' => $search,
        ] = self::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
            'bankid' => $bankid,
            'search' => $search,
        ]);

        // Validate that the adaptive quiz module exists and get the context.
        $context = context::instance_by_id($contextid);
        self::validate_context($context);

        // Get the question bank course module to access its context.
        $qbankcm = get_coursemodule_from_instance('qbank', $bankid, 0, false, MUST_EXIST);
        $qbankcontext = context_module::instance($qbankcm->id);

        // Get all categories for the question bank's context.
        $categories = self::get_categories_for_bank($qbankcontext, $search);

        // Format categories as options.
        $suggestions = array_map(function ($category) {
            $combinedid = helper::combine_id_context($category);
            return [
                'value' => $combinedid,
                'label' => self::format_category_label($category),
            ];
        }, $categories);

        // Sort by label.
        usort($suggestions, function ($a, $b) {
            return strcmp($a['label'], $b['label']);
        });

        // Limit results if there are too many.
        if (count($suggestions) > self::MAX_RESULTS) {
            $suggestions = array_slice($suggestions, 0, self::MAX_RESULTS);
        }

        return [
            'categories' => $suggestions,
        ];
    }

    /**
     * Retrieves question categories for a given question bank context.
     *
     * @param context_module $qbankcontext The context of the question bank.
     * @param string $search Search string to filter categories by name.
     * @return stdClass[] Array of category objects with relevant fields.
     */
    private static function get_categories_for_bank(context_module $qbankcontext, string $search = ''): array {
        global $DB;

        // Get all categories in the question bank's context, excluding the top-level category.
        $sql = "SELECT id, name, contextid, parent, sortorder, idnumber
                  FROM {question_categories}
                 WHERE contextid = :contextid
                   AND parent != 0
              ORDER BY sortorder ASC, name ASC";

        $params = ['contextid' => $qbankcontext->id];

        $categories = $DB->get_records_sql($sql, $params);

        // Filter by search string if provided.
        if (!empty($search)) {
            $search = strtolower($search);
            $categories = array_filter($categories, function ($category) use ($search) {
                return strpos(strtolower($category->name), $search) !== false;
            });
        }

        // Add question count to each category.
        foreach ($categories as $category) {
            $category->questioncount = self::count_questions_in_category($category->id);
        }

        return array_values($categories);
    }

    /**
     * Counts the number of questions in a given category.
     *
     * Only counts "ready" questions (excludes draft and hidden versions).
     *
     * @param int $categoryid The question category ID.
     * @return int The number of questions in the category.
     */
    private static function count_questions_in_category(int $categoryid): int {
        global $DB;

        $sql = "SELECT COUNT(DISTINCT q.id)
                  FROM {question} q
                  JOIN {question_versions} qv ON qv.questionid = q.id
                  JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                 WHERE qbe.questioncategoryid = :categoryid
                   AND qv.status = :status";

        // Use only the latest version of questions (ready status).
        $params = [
            'categoryid' => $categoryid,
            'status' => question_version_status::QUESTION_STATUS_READY,
        ];

        return (int) $DB->count_records_sql($sql, $params);
    }

    /**
     * Formats a category label for display in the autocomplete list.
     *
     * @param stdClass $category The category object with id, name, and questioncount.
     * @return string Formatted category label.
     */
    private static function format_category_label(stdClass $category): string {
        $label = format_string($category->name);

        // Append question count if available.
        if (isset($category->questioncount) && $category->questioncount > 0) {
            $label .= ' (' . $category->questioncount . ')';
        }

        return $label;
    }
}
