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

namespace mod_adaptivequiz\local\report\users_attempts\sql;

use core\dml\sql_join;
use core_user\fields;
use mod_adaptivequiz\local\attempt\attempt_state;

/**
 * The class contains all possible sql options needed to build the users' attempts table.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sql_and_params {

    /**
     * @var string $fields
     */
    private $fields;

    /**
     * @var string $from
     */
    private $from;

    /**
     * @var string $where
     */
    private $where;

    /**
     * @var string|null $where
     */
    private $groupby;

    /**
     * @var array $params Normal array with query parameters as in {@see \moodle_database::get_records_sql()}, for instance.
     */
    private $params;

    /**
     * @var string $countsql Complete sql statement to pass to {@see \table_sql::set_count_sql()}.
     */
    private $countsql;

    /**
     * @var array $params Same format as for {@see self::$params} above.
     */
    private $countsqlparams;

    /**
     * The constructor, closed.
     *
     * @param string $fields
     * @param string $from
     * @param string $where
     * @param string|null $groupby
     * @param array $params
     * @param string|null $countsql
     * @param array|null $countsqlparams
     */
    private function __construct(
        string $fields,
        string $from,
        string $where,
        ?string $groupby,
        array $params,
        ?string $countsql,
        ?array $countsqlparams
    ) {
        $this->fields = $fields;
        $this->from = $from;
        $this->where = $where;
        $this->params = $params;
        $this->groupby = $groupby;
        $this->countsql = $countsql;
        $this->countsqlparams = $countsqlparams;
    }

    /**
     * Property getter.
     *
     * @return string
     */
    public function fields(): string {
        return $this->fields;
    }

    /**
     * Property getter.
     *
     * @return string
     */
    public function from(): string {
        return $this->from;
    }

    /**
     * Property getter.
     *
     * @return string
     */
    public function where(): string {
        return $this->where;
    }

    /**
     * Property getter.
     *
     * @return string
     */
    public function group_by(): ?string {
        return $this->groupby;
    }

    /**
     * Property getter.
     *
     * @return array
     */
    public function params(): array {
        return $this->params;
    }

    /**
     * Property getter.
     *
     * @return string|null
     */
    public function count_sql(): ?string {
        return $this->countsql;
    }

    /**
     * Property getter.
     *
     * @return array|null
     */
    public function count_sql_params(): ?array {
        return $this->countsqlparams;
    }

    /**
     * Instantiates a proper object when filtering by group is required.
     *
     * @param int $groupid
     * @return self
     */
    public function with_group_filtering(int $groupid): self {
        $from = $this->from . ' INNER JOIN {groups_members} gm ON u.id = gm.userid';
        $where = $this->where . ' AND gm.groupid = :groupid';
        $params = array_merge(['groupid' => $groupid], $this->params);

        return new self($this->fields, $from, $where, $this->groupby, $params, $this->countsql, $this->countsqlparams);
    }

    /**
     * Instantiates an object for the case when no extra filtering is needed.
     *
     * @param int $adaptivequizid
     * @return self
     */
    public static function default(int $adaptivequizid): self {
        list ($attemptsql, $params) = self::attempt_sql_and_params();

        $fields = fields::for_name()
            ->including('id', 'email')
            ->get_sql('u', false, '', '', false)->selects
            . ', ' . $attemptsql;

        $from = '{adaptivequiz_attempt} aa JOIN {user} u ON u.id = aa.userid';
        $where = self::base_where_sql() . ' AND aa.instance = :instance';
        $params = array_merge($params, ['instance' => $adaptivequizid]);

        $sqlcount = "SELECT COUNT(DISTINCT u.id) FROM $from WHERE $where";

        return new self($fields, $from, $where, self::group_by_sql(), $params, $sqlcount, $params);
    }

    /**
     * Instantiates an object to fetch enrolled users who didn't attempt the quiz.
     *
     * @param int $adaptivequizid
     * @param sql_join $enrolledjoin
     * @return self
     */
    public static function for_enrolled_with_no_attempts(int $adaptivequizid, sql_join $enrolledjoin): self {
        $fields = 'DISTINCT u.id' . fields::for_name()->including('email')->get_sql('u')->selects
            . ', NULL as attemptsnum, NULL AS uniqueid, NULL AS attempttimefinished, NULL AS measure, NULL AS stderror';
        $from = "
            {user} u
            $enrolledjoin->joins
            LEFT JOIN {adaptivequiz_attempt} aa ON (aa.userid = u.id AND aa.instance = :instance)
        ";
        $where = $enrolledjoin->wheres . ' AND aa.id IS NULL';
        $params = array_merge(['instance' => $adaptivequizid], $enrolledjoin->params);

        $sqlcount = "SELECT COUNT(DISTINCT u.id) FROM $from WHERE $where";

        return new self($fields, $from, $where, null, $params, $sqlcount, $params);
    }

    /**
     * Instantiates an object to fetch enrolled users who made attempted the quiz.
     *
     * @param int $adaptivequizid
     * @param sql_join $enrolledjoin
     * @return self
     */
    public static function for_enrolled_with_attempts(int $adaptivequizid, sql_join $enrolledjoin): self {
        list ($attemptsql, $params) = self::attempt_sql_and_params();

        $fields = 'DISTINCT u.id' . fields::for_name()->including('email')->get_sql('u')->selects
            . ', ' . $attemptsql;
        $from = "
            {user} u
            $enrolledjoin->joins
            JOIN {adaptivequiz_attempt} aa ON (aa.userid = u.id AND aa.instance = :instance)
        ";
        $where = $enrolledjoin->wheres;
        $params = array_merge($params, ['instance' => $adaptivequizid], $enrolledjoin->params);

        $sqlcount = "SELECT COUNT(DISTINCT u.id) FROM $from WHERE $where";

        return new self($fields, $from, $where, self::group_by_sql(), $params, $sqlcount, $params);
    }

    /**
     * Instantiates an object to fetch not enrolled users, but who attempted the quiz previously.
     *
     * @param int $adaptivequizid
     * @param sql_join $enrolledjoin
     * @return self
     */
    public static function for_not_enrolled_with_attempts(int $adaptivequizid, sql_join $enrolledjoin): self {
        list ($attemptsql, $params) = self::attempt_sql_and_params();

        $fields = 'DISTINCT u.id' . fields::for_name()->including('email')->get_sql('u')->selects
            . ', ' . $attemptsql;
        $from = '{adaptivequiz_attempt} aa JOIN {user} u ON u.id = aa.userid';
        $where = self::base_where_sql() . "
            AND aa.instance = :instance AND NOT EXISTS (
                SELECT DISTINCT u.id FROM {user} u
                $enrolledjoin->joins
                WHERE u.id = aa.userid AND $enrolledjoin->wheres
            )
        ";
        $params = array_merge($params, ['instance' => $adaptivequizid], $enrolledjoin->params);

        $sqlcount = "SELECT COUNT(DISTINCT u.id) FROM $from WHERE $where";

        return new self($fields, $from, $where, self::group_by_sql(), $params, $sqlcount, $params);
    }

    /**
     * Returns a piece of SQL and its parameters to get attempts data.
     *
     * @return array Normal Moodle's array with SQL statement and its parameters.
     */
    private static function attempt_sql_and_params(): array {
        return [
            '(
                SELECT COUNT(*) FROM {adaptivequiz_attempt} caa
                WHERE caa.userid = u.id AND caa.instance = aa.instance
            ) AS attemptsnum,
            (
                SELECT acp.measure
                FROM {adaptivequiz_attempt} maa, {adaptivequiz_cat_params} acp
                WHERE maa.instance = aa.instance AND maa.userid = u.id AND maa.id = acp.attempt
                    AND maa.attemptstate = :attemptstate1 AND acp.standarderror > 0.0
                ORDER BY acp.measure DESC
                LIMIT 1
            ) AS measure,
            (
                SELECT acp.standarderror
                FROM {adaptivequiz_attempt} saa, {adaptivequiz_cat_params} acp
                WHERE saa.instance = aa.instance AND saa.userid = u.id AND saa.id = acp.attempt
                    AND saa.attemptstate = :attemptstate2 AND acp.standarderror > 0.0
                ORDER BY acp.measure DESC
                LIMIT 1
            ) AS stderror,
            (
                SELECT taa.timemodified
                FROM {adaptivequiz_attempt} taa, {adaptivequiz_cat_params} acp
                WHERE taa.instance = aa.instance AND taa.userid = u.id AND taa.id = acp.attempt
                    AND taa.attemptstate = :attemptstate3 AND acp.standarderror > 0.0
                ORDER BY acp.measure DESC
                LIMIT 1
            ) AS attempttimefinished,
            (
                SELECT iaa.id
                FROM {adaptivequiz_attempt} iaa, {adaptivequiz_cat_params} acp
                WHERE iaa.instance = aa.instance AND iaa.userid = u.id AND iaa.id = acp.attempt
                    AND iaa.attemptstate = :attemptstate4 AND acp.standarderror > 0.0
                ORDER BY acp.measure DESC
                LIMIT 1
            ) AS attemptid'
            ,
            [
                'attemptstate1' => attempt_state::COMPLETED,
                'attemptstate2' => attempt_state::COMPLETED,
                'attemptstate3' => attempt_state::COMPLETED,
                'attemptstate4' => attempt_state::COMPLETED,
            ]
        ];
    }

    /**
     * Returns fields for the basic GROUP BY clause.
     *
     * @return string
     */
    private static function group_by_sql(): string {
        return 'u.id, aa.instance';
    }

    /**
     * Returns basic SQL condition for the WHERE clause.
     *
     * @return string
     */
    private static function base_where_sql(): string {
        return 'u.deleted = 0';
    }
}
