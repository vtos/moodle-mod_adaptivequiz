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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/adaptivequiz/locallib.php');

use context_module;
use core_question\local\bank\question_bank_helper;
use stdClass;

/**
 * A high level class to manage item banks for adaptive quizzes.
 *
 * It contains only static methods wrapping operations as a whole with no explicit dependencies. The class is intended
 * to be used for item bank management only.
 *
 * @package    mod_adaptivequiz
 * @copyright  2026 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_bank {

    /**
     * Creates new links between the given adaptive quiz instance and questions banks.
     *
     * @param int $adaptivequizid ID of the adaptive quiz instance to link the question banks to.
     * @param int[] $qbankidlist List of ID of question bank instances to link.
     */
    public static function assign_qbanks_to_adaptivequiz(int $adaptivequizid, array $qbankidlist): void {
        global $DB;

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequizid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        // A way to validate the questions banks being assigned. We fetch what's available to match the passed
        // question banks against.

        $availableqbanks = question_bank_helper::get_activity_instances_with_shareable_questions(
            filtercontext: $context
        );

        $qbankidlist = array_intersect(
            $qbankidlist,
            array_map(fn($qbank): int => $qbank->cminfo->instance, $availableqbanks)
        );

        // Filter out what's added already.

        $linkedqbankidlist = $DB->get_fieldset('adaptivequiz_qbank', 'qbankid', ['adaptivequizid' => $adaptivequizid]);
        $qbankidlist = array_diff($qbankidlist, $linkedqbankidlist);

        $insert = array_map(function(int $qbankid) use ($adaptivequizid): stdClass {
            $qbankcm = get_coursemodule_from_instance('qbank', $qbankid, 0, false, MUST_EXIST);
            $qbankcontext = context_module::instance($qbankcm->id);

            return (object) [
                'adaptivequizid' => $adaptivequizid,
                'qbankid' => $qbankid,
                'qbankcontextid' => $qbankcontext->id,
            ];
        }, $qbankidlist);

        $DB->insert_records('adaptivequiz_qbank', $insert);
    }

    /**
     * Unlinks the given question bank from the adaptive quiz instance.
     *
     * Can be used as a high-level API method, contains all necessary permissions checks.
     *
     * @param int $adaptivequizid ID of the adaptive quiz instance to unlink the question bank from.
     * @param int $qbankid ID of th question bank to unlink.
     */
    public static function unassign_qbank_from_adaptivequiz(int $adaptivequizid, int $qbankid): void {
        global $DB;

        $cm = get_coursemodule_from_instance(
            modulename: 'adaptivequiz',
            instance: $adaptivequizid,
            strictness: MUST_EXIST
        );

        $context = context_module::instance($cm->id);
        require_capability('mod/adaptivequiz:manage', $context);

        $DB->delete_records('adaptivequiz_qbank', ['adaptivequizid' => $adaptivequizid, 'qbankid' => $qbankid]);
    }

    /**
     * Unlinks the given question category from the adaptive quiz instance.
     *
     * Can be used as a high-level API method, contains all necessary permissions checks.
     *
     * @param int $adaptivequizid ID of the adaptive quiz instance to unlink the question category from.
     * @param int $qcatid ID of th question category to unlink.
     */
    public static function unassign_question_category_from_adaptivequiz(int $adaptivequizid, int $qcatid): void {
        global $DB;

        $cm = get_coursemodule_from_instance(
            modulename: 'adaptivequiz',
            instance: $adaptivequizid,
            strictness: MUST_EXIST
        );

        $context = context_module::instance($cm->id);
        require_capability('mod/adaptivequiz:manage', $context);

        $DB->delete_records('adaptivequiz_question', ['instance' => $adaptivequizid, 'questioncategory' => $qcatid]);
    }

    /**
     * Returns the list of question banks assigned to the given adaptive quiz instance.
     *
     * It may distinguish question banks by courses: either fetch from a particular course or skip question banks in
     * a particular course. If no course parameters are specified it fetches all the question banks assigned for
     * the adaptive quiz instance.
     *
     * @param int $adaptivequizid
     * @param string $fields Comma separated list of fields to return for each item.
     * @param int|null $incourseid A specific course question banks must be from.
     * @param int|null $notincourseid A specific course question banks must not be from.
     * @return stdClass[] An array of question bank instances.
     */
    public static function get_question_banks_assigned_to_adaptivequiz(
        int $adaptivequizid,
        string $fields = '*',
        ?int $incourseid = null,
        ?int $notincourseid = null
    ): array {
        global $DB;

        $whereextra = '';
        $paramsextra = [];

        if ($incourseid) {
            $whereextra .= "AND qb.course = ?";
            $paramsextra[] = $incourseid;
        }

        if ($notincourseid) {
            $whereextra .= "AND qb.course != ?";
            $paramsextra[] = $notincourseid;
        }

        $sql = "SELECT qb.{$fields}
                  FROM {adaptivequiz_qbank} aqb
                  JOIN {qbank} qb ON qb.id = aqb.qbankid
                 WHERE aqb.adaptivequizid = ?
                       {$whereextra}";
        $params = array_merge([$adaptivequizid], $paramsextra);

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Provides information about single question categories linked to the given adaptive quiz activity.
     *
     * @return stdClass[] Each item is a record from {question_categories} + 'cmid' and 'qbankname' fields.
     */
    public static function get_question_categories_assigned_to_adaptivequiz(
        int $adaptivequizid,
        string $fields = '*',
        ?int $incourseid = null,
        ?int $notincourseid = null
    ): array {
        global $DB;

        $whereextra = '';
        $paramsextra = [];

        if ($incourseid) {
            $whereextra .= "AND cm.course = ?";
            $paramsextra[] = $incourseid;
        }

        if ($notincourseid) {
            $whereextra .= "AND cm.course != ?";
            $paramsextra[] = $notincourseid;
        }

        // Add a prefix to the fields to be returned.
        $fieldlist = array_map(fn (string $field) => 'qc.' . $field, explode(',', $fields));
        $fields = implode(',', $fieldlist);

        // TODO: consider post-loading of cm_info instances for categories' qbanks.

        $sql = "SELECT {$fields}, cm.id AS cmid, qb.name AS qbankname
                  FROM {adaptivequiz_question} aq
                  JOIN {question_categories} qc ON qc.id = aq.questioncategory
                  JOIN {context} c ON c.id = qc.contextid
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = ?
                  JOIN {qbank} qb ON qb.id = cm.instance
                 WHERE aq.instance = ?
                       {$whereextra}";
        $params = array_merge([CONTEXT_MODULE, $adaptivequizid], $paramsextra);

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Creates new links between the given adaptive quiz instance and question categories.
     *
     * @param int $adaptivequizid ID of the adaptive quiz instance to link the question categories to.
     * @param array $qcategoryidlist List of category IDs (in format 'categoryid,contextid') to link.
     */
    public static function assign_question_categories_to_adaptivequiz(int $adaptivequizid, array $qcategoryidlist): void {
        global $DB;

        if (empty($qcategoryidlist)) {
            return;
        }

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequizid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        // Parse the categoryid,contextid format and validate each category.
        $validcategoryids = [];
        foreach ($qcategoryidlist as $categoryidcontextid) {
            [$categoryid, $contextid] = explode(',', $categoryidcontextid);
            $categoryid = (int) $categoryid;
            $contextid = (int) $contextid;

            // Verify the category exists and is in a valid context.
            if ($DB->record_exists('question_categories', ['id' => $categoryid, 'contextid' => $contextid])) {
                $validcategoryids[] = $categoryid;
            }
        }

        if (empty($validcategoryids)) {
            return;
        }

        // Filter out what's already added.
        $linkedcategoryidlist = $DB->get_fieldset(
            'adaptivequiz_question',
            'questioncategory',
            ['instance' => $adaptivequizid]
        );
        $newcategoryidlist = array_diff($validcategoryids, $linkedcategoryidlist);

        if (empty($newcategoryidlist)) {
            return;
        }

        // Insert new category assignments.
        $insert = array_map(function (int $categoryid) use ($adaptivequizid): stdClass {
            return (object) [
                'instance' => $adaptivequizid,
                'questioncategory' => $categoryid,
            ];
        }, $newcategoryidlist);

        $DB->insert_records('adaptivequiz_question', $insert);
    }

    /**
     * A wrapper method to know whether the adaptive quiz has any question banks or single question categories assigned.
     *
     * @param int $adaptivequizid ID of the adaptive quiz instance.
     */
    public static function adaptive_quiz_instance_has_question_banks_or_categories_linked(int $adaptivequizid): bool {
        global $DB;

        $hasqbanks = $DB->record_exists('adaptivequiz_qbank', ['adaptivequizid' => $adaptivequizid]);
        $hasqcats = $DB->record_exists('adaptivequiz_question', ['instance' => $adaptivequizid]);

        return $hasqbanks || $hasqcats;
    }
}
