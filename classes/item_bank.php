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

use cm_info;
use context_module;
use core\context;
use core\context_helper;
use core\exception\invalid_parameter_exception;
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
     * @param int[] $qbankcontextidlist List of question bank module context ID to link.
     */
    public static function assign_qbanks_to_adaptivequiz(int $adaptivequizid, array $qbankcontextidlist): void {
        global $DB;

        // Filter out what's added already.

        $linkedqbankcontextidlist = $DB->get_fieldset(
            'adaptivequiz_qbank',
            'qbankcontextid',
            ['adaptivequizid' => $adaptivequizid]
        );

        $assignmentexists = array_intersect($qbankcontextidlist, $linkedqbankcontextidlist);
        if ($assignmentexists) {
            throw new invalid_parameter_exception('Attempt to add existing question bank assignments');
        }

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequizid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        // A way to validate the questions banks being assigned. We fetch what's available to match the passed
        // question banks against.

        // Define the set of courses to get the query limited by.
        $incourseids = [];
        context_helper::preload_contexts_by_id($qbankcontextidlist);
        foreach ($qbankcontextidlist as $qbankcontextid) {
            // A question bank module must always have a course context, we're OK to fatal here when it doesn't.
            $incourseids[] = context::instance_by_id($qbankcontextid)->get_course_context()->instanceid;
        }

        $capoptions = ['moodle/question:useall'];

        $availableqbanks = question_bank_helper::get_activity_instances_with_shareable_questions(
            incourseids: $incourseids,
            havingcap: $capoptions,
            filtercontext: $context
        );

        $availableqbankcontextidlist = array_map(
            // The types returned by get_activity_instances_with_shareable_questions() are different across 5.x
            // versions.
            fn ($qbank): int => class_exists('\core_question\local\bank\formatted_bank')
                ? $qbank->get_formatted()->contextid
                : $qbank->contextid,
            $availableqbanks
        );

        $notavailable = array_diff($qbankcontextidlist, $availableqbankcontextidlist);
        if ($notavailable) {
            throw new invalid_parameter_exception('Attempt to assign question bank with no permissions');
        }

        $insert = array_map(function (int $qbankcontextid) use ($adaptivequizid): stdClass {
            return (object) [
                'adaptivequizid' => $adaptivequizid,

                // This field will be deprecated in future versions.
                'qbankid' => 0,

                'qbankcontextid' => $qbankcontextid,
            ];
        }, $qbankcontextidlist);

        $DB->insert_records('adaptivequiz_qbank', $insert);
    }

    /**
     * Unlinks the given question bank from the adaptive quiz instance.
     *
     * Can be used as a high-level API method, contains all necessary permissions checks.
     *
     * @param int $adaptivequizid ID of the adaptive quiz instance to unlink the question bank from.
     * @param int $qbankcmid ID of the question bank course module.
     */
    public static function unassign_qbank_from_adaptivequiz(int $adaptivequizid, int $qbankcmid): void {
        global $DB;

        $cm = get_coursemodule_from_instance(
            modulename: 'adaptivequiz',
            instance: $adaptivequizid,
            strictness: MUST_EXIST
        );

        $context = context_module::instance($cm->id);
        require_capability('mod/adaptivequiz:manage', $context);

        $qbankcontext = context_module::instance($qbankcmid);

        $DB->delete_records(
            'adaptivequiz_qbank',
            ['adaptivequizid' => $adaptivequizid, 'qbankcontextid' => $qbankcontext->id]
        );
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
     * @param int|null $incourseid A specific course question banks must be from.
     * @param int|null $notincourseid A specific course question banks must not be from.
     * @return cm_info[] A cm_info object per question bank.
     */
    public static function get_question_banks_assigned_to_adaptivequiz(
        int $adaptivequizid,
        ?int $incourseid = null,
        ?int $notincourseid = null
    ): array {
        global $DB;

        $whereextra = '';
        $paramsextra = [];

        if ($incourseid) {
            $whereextra .= " AND cm.course = :incourse";
            $paramsextra['incourse'] = $incourseid;
        }

        if ($notincourseid) {
            $whereextra .= " AND cm.course != :notincourse";
            $paramsextra['notincourse'] = $notincourseid;
        }

        $sql = "SELECT cm.id, cm.course
                  FROM {adaptivequiz_qbank} aqb
                  JOIN {context} ctx ON ctx.id = aqb.qbankcontextid
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                 WHERE aqb.adaptivequizid = :instance
                       AND cm.deletioninprogress = :deletion
                       {$whereextra}";
        $paramsbase = ['contextlevel' => CONTEXT_MODULE, 'instance' => $adaptivequizid, 'deletion' => 0];

        $records = $DB->get_records_sql($sql, array_merge($paramsbase, $paramsextra));

        $return = array_map(fn (stdClass $record): cm_info => cm_info::create($record), $records);
        sort($return);

        return $return;
    }

    /**
     * Provides information about single question categories linked to the given adaptive quiz activity.
     *
     * Besides the question category properties, each returned item will contain a 'qbank' property
     * with the question bank the current category is from. The 'qbank' property will be an instance of {@see \cm_info}.
     *
     * @param int $adaptivequizid
     * @param int|null $incourseid Return only from this course.
     * @param int|null $notincourseid Return outside this course only.
     * @return stdClass[] Each item is a record from {question_categories} + 'qbank' property.
     */
    public static function get_question_categories_assigned_to_adaptivequiz(
        int $adaptivequizid,
        ?int $incourseid = null,
        ?int $notincourseid = null
    ): array {
        global $DB;

        $whereextra = '';
        $paramsextra = [];

        if ($incourseid) {
            $whereextra .= "AND cm.course = :incourse";
            $paramsextra['incourse'] = $incourseid;
        }

        if ($notincourseid) {
            $whereextra .= "AND cm.course != :notincourse";
            $paramsextra['notincourse'] = $notincourseid;
        }

        $cmfieldprefix = 'cmf_';

        $sql = "SELECT qc.*, cm.id AS {$cmfieldprefix}id, cm.course AS {$cmfieldprefix}course
                  FROM {adaptivequiz_question} aq
                  JOIN {question_categories} qc ON qc.id = aq.questioncategory
                  JOIN {context} c ON c.id = qc.contextid
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                 WHERE aq.instance = :instance
                       AND cm.deletioninprogress = :deletion
                       {$whereextra}";
        $paramsbase = ['contextlevel' => CONTEXT_MODULE, 'instance' => $adaptivequizid, 'deletion' => 0];

        $return = $DB->get_records_sql($sql, array_merge($paramsbase, $paramsextra));

        array_walk($return, function (stdClass $qcat) use ($cmfieldprefix) {
            $cm = new stdClass();
            foreach ($qcat as $property => $value) {
                if (!str_contains($property, $cmfieldprefix)) {
                    continue;
                }

                $cmproperty = str_replace($cmfieldprefix, '', $property);
                $cm->{$cmproperty} = $value;
                unset($qcat->{$property});
            }

            $qcat->qbank = cm_info::create($cm);
        });
        sort($return);

        return $return;
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
