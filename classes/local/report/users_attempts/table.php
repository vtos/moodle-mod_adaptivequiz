<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * A class to display a table with all attempts made by users.
 *
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_adaptivequiz\local\report\users_attempts;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use core_user\fields;
use dml_exception;
use html_writer;
use mod_adaptivequiz_renderer;
use moodle_exception;
use moodle_url;
use stdClass;
use table_sql;

final class table extends table_sql {

    public const UNIQUE_ID = 'usersattemptstable';

    /**
     * @var mod_adaptivequiz_renderer $renderer
     */
    private $renderer;

    /**
     * @var int $cmid
     */
    private $cmid;

    /**
     * @var filter $filter
     */
    private $filter;

    /**
     * @throws coding_exception
     */
    public function __construct(mod_adaptivequiz_renderer $renderer, int $cmid, moodle_url $baseurl, filter $filter) {
        parent::__construct(self::UNIQUE_ID);

        $this->renderer = $renderer;
        $this->cmid = $cmid;
        $this->filter = $filter;

        $this->init($baseurl);
    }

    /**
     * {@inheritdoc}
     * @throws dml_exception
     */
    public function query_db($pagesize, $useinitialsbar=true): void {
        global $DB;

        if (!$this->is_downloading()) {
            if ($this->countsql === NULL) {
                $this->countsql = 'SELECT COUNT(1) FROM '.$this->sql->from.' WHERE '.$this->sql->where;
                $this->countparams = $this->sql->params;
            }
            $grandtotal = $DB->count_records_sql($this->countsql, $this->countparams);
            if ($useinitialsbar && !$this->is_downloading()) {
                $this->initialbars(true);
            }

            list($wsql, $wparams) = $this->get_sql_where();
            if ($wsql) {
                $this->countsql .= ' AND ' . $wsql;
                $this->countparams = array_merge($this->countparams, $wparams);

                $this->sql->where .= ' AND ' . $wsql;
                $this->sql->params = array_merge($this->sql->params, $wparams);

                $total  = $DB->count_records_sql($this->countsql, $this->countparams);
            } else {
                $total = $grandtotal;
            }

            $this->pagesize($pagesize, $total);
        }

        $groupby = $this->sql_group_by_clause();

        $sort = $this->get_sql_sort();
        if ($sort) {
            $sort = "ORDER BY $sort";
        }
        $sql = "SELECT
                {$this->sql->fields}
                FROM {$this->sql->from}
                WHERE {$this->sql->where}
                {$groupby}
                {$sort}";

        if (!$this->is_downloading()) {
            $this->rawdata = $DB->get_records_sql($sql, $this->sql->params, $this->get_page_start(), $this->get_page_size());
        } else {
            $this->rawdata = $DB->get_records_sql($sql, $this->sql->params);
        }
    }

    protected function col_attemptsnum(stdClass $row): string {
        if (!$this->is_downloading()) {
            return html_writer::link(
                new moodle_url('/mod/adaptivequiz/viewattemptreport.php', ['userid' => $row->id, 'cmid' => $this->cmid]),
                $row->attemptsnum
            );
        }

        return $row->attemptsnum;
    }

    /**
     * @throws moodle_exception
     */
    protected function col_measure(stdClass $row): string {
        $measure = $this->renderer->format_measure($row);
        if (!$row->uniqueid) {
            return $measure;
        }

        if (!$this->is_downloading()) {
            return html_writer::link(
                new moodle_url(
                    '/mod/adaptivequiz/reviewattempt.php',
                    ['userid' => $row->id, 'uniqueid' => $row->uniqueid, 'cmid' => $this->cmid]
                ),
                $measure
            );
        }

        return $measure;
    }

    protected function col_stderror(stdClass $row): string {
        $rendered = $this->renderer->format_standard_error($row);
        if (!$this->is_downloading()) {
            return $rendered;
        }

        return html_entity_decode($rendered,ENT_QUOTES,'UTF-8');
    }

    /**
     * @throws coding_exception
     */
    protected function col_attempttimefinished(stdClass $row): string {
        return intval($row->attempttimefinished)
            ? userdate($row->attempttimefinished)
            : get_string('na', 'adaptivequiz');
    }

    /**
     * A convenience method to call a bunch of init methods.
     *
     * @param moodle_url $baseurl
     * @throws coding_exception
     */
    private function init(moodle_url $baseurl): void {
        $this->define_columns([
            'fullname', 'email', 'attemptsnum', 'measure', 'stderror', 'attempttimefinished',
        ]);
        $this->define_headers([
            get_string('fullname'),
            get_string('email'),
            get_string('numofattemptshdr', 'adaptivequiz'),
            get_string('bestscore', 'adaptivequiz'),
            get_string('bestscorestderror', 'adaptivequiz'),
            get_string('attemptfinishedtimestamp', 'adaptivequiz'),
        ]);
        $this->define_baseurl($baseurl);
        $this->set_attribute('class', $this->attributes['class'] . ' usersattemptstable');
        $this->set_content_alignment_in_columns();
        $this->collapsible(false);
        $this->sortable(true, 'lastname');
        $this->is_downloadable(true);

        $sqlfrom = '
            {adaptivequiz_attempt} aa
            JOIN {user} u ON u.id = aa.userid
            JOIN {adaptivequiz} a ON a.id = aa.instance
        ';
        $sqlwhere = 'aa.instance = :instance';
        $sqlparams = [
            'attemptstate1' => ADAPTIVEQUIZ_ATTEMPT_COMPLETED,
            'attemptstate2' => ADAPTIVEQUIZ_ATTEMPT_COMPLETED,
            'attemptstate3' => ADAPTIVEQUIZ_ATTEMPT_COMPLETED,
            'attemptstate4' => ADAPTIVEQUIZ_ATTEMPT_COMPLETED,
            'instance' => $this->filter->adaptivequizid,
        ];
        if ($this->filter->groupid) {
            $sqlfrom .= ' INNER JOIN {groups_members} gm ON u.id = gm.userid';
            $sqlwhere .= ' AND gm.groupid = :groupid';
            $sqlparams['groupid'] = $this->filter->groupid;
        }
        $this->set_sql(
            '
                u.id' . fields::for_name()->get_sql('u')->selects . ', u.email, a.highestlevel, a.lowestlevel,
                (
                    SELECT COUNT(*)
                    FROM {adaptivequiz_attempt} caa
                    WHERE caa.userid = u.id
                    AND caa.instance = aa.instance
                ) AS attemptsnum,
                (
                    SELECT maa.measure
                    FROM {adaptivequiz_attempt} maa
                    WHERE maa.instance = a.id
                    AND maa.userid = u.id
                    AND maa.attemptstate = :attemptstate1
                    AND maa.standarderror > 0.0
                    ORDER BY measure DESC
                    LIMIT 1
                ) AS measure,
                (
                    SELECT saa.standarderror
                    FROM {adaptivequiz_attempt} saa
                    WHERE saa.instance = a.id
                    AND saa.userid = u.id
                    AND saa.attemptstate = :attemptstate2
                    AND saa.standarderror > 0.0
                    ORDER BY measure DESC
                    LIMIT 1
                ) AS stderror,
                (
                    SELECT taa.timemodified
                    FROM {adaptivequiz_attempt} taa
                    WHERE taa.instance = a.id
                    AND taa.userid = u.id
                    AND taa.attemptstate = :attemptstate3
                    AND taa.standarderror > 0.0
                    ORDER BY measure DESC
                    LIMIT 1
                ) AS attempttimefinished,
                (
                    SELECT iaa.uniqueid
                    FROM {adaptivequiz_attempt} iaa
                    WHERE iaa.instance = a.id
                    AND iaa.userid = u.id
                    AND iaa.attemptstate = :attemptstate4
                    AND iaa.standarderror > 0.0
                    ORDER BY measure DESC
                    LIMIT 1
                ) AS uniqueid
            ',
            $sqlfrom,
            $sqlwhere,
            $sqlparams
        );

        $sqlcountfrom = '
            {adaptivequiz_attempt} aa
            JOIN {user} u ON u.id = aa.userid
        ';
        $sqlcountwhere = 'instance = :instance';
        $sqlcountparams = ['instance' => $this->filter->adaptivequizid];
        if ($this->filter->groupid) {
            $sqlcountfrom .= ' INNER JOIN {groups_members} gm ON aa.userid = gm.userid';
            $sqlcountwhere .= ' AND gm.groupid = :groupid';
            $sqlcountparams['groupid'] = $this->filter->groupid;
        }
        $this->set_count_sql("SELECT COUNT(DISTINCT aa.userid) FROM $sqlcountfrom WHERE $sqlcountwhere",
            $sqlcountparams);
    }

    private function sql_group_by_clause(): string {
        return 'GROUP BY u.id, aa.instance, a.id, a.highestlevel, a.lowestlevel';
    }

    private function set_content_alignment_in_columns(): void {
        $this->column_class['attemptsnum'] .= ' text-center';
        $this->column_class['measure'] .= ' text-center';
        $this->column_class['stderror'] .= ' text-center';
    }
}
