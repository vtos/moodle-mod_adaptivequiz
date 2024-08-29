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
 * @copyright  2022 Vitaly Potenko <potenkov@gmail.com>
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
     * The constructor, closed, factory methods are expected to be used instead.
     *
     * @param string $fields
     * @param string $from
     * @param string $where
     * @param array $params
     * @param string|null $countsql
     * @param array|null $countsqlparams
     */
    private function __construct(
        string $fields,
        string $from,
        string $where,
        array $params,
        ?string $countsql,
        ?array $countsqlparams
    ) {
        $this->fields = $fields;
        $this->from = $from;
        $this->where = $where;
        $this->params = $params;
        $this->countsql = $countsql;
        $this->countsqlparams = $countsqlparams;
    }

    /**
     * A getter method.
     */
    public function fields(): string {
        return $this->fields;
    }

    /**
     * A getter method.
     */
    public function from(): string {
        return $this->from;
    }

    /**
     * A getter method.
     */
    public function where(): string {
        return $this->where;
    }

    /**
     * A getter method.
     */
    public function params(): array {
        return $this->params;
    }

    /**
     * A getter method.
     */
    public function count_sql(): ?string {
        return $this->countsql;
    }

    /**
     * A getter method.
     */
    public function count_sql_params(): ?array {
        return $this->countsqlparams;
    }

    /**
     * Transforms the instance to respect the group filtering requested.
     *
     * @param int $groupid
     */
    public function with_group_filtering(int $groupid): self {
        $from = $this->from . ' INNER JOIN {groups_members} gm ON u.id = gm.userid';
        $where = $this->where . ' AND gm.groupid = :groupid';
        $params = array_merge(['groupid' => $groupid], $this->params);

        return new self($this->fields, $from, $where, $params, $this->countsql, $this->countsqlparams);
    }

    /**
     * Provides a configured instance for the default report - all users who ever made an attempt.
     *
     * @param int $adaptivequizid
     */
    public static function default(int $adaptivequizid): self {
        $fields = fields::for_name()
            ->including('id', 'email')
            ->get_sql('u', false, '', '', false)->selects
            . ', ' . self::attempt_fields();
        $from = '{adaptivequiz_attempt} aa
            JOIN {user} u ON u.id = aa.userid';

        list($where, $params) = self::base_where_sql_and_params($adaptivequizid);

        $sqlcount = "SELECT COUNT(DISTINCT u.id) FROM $from WHERE $where";
        $countparams = $params;

        list($attemptwhere, $attemptparams) = self::attempt_where_sql_and_params();
        $where .= " AND $attemptwhere";
        $params = array_merge($params, $attemptparams);

        return new self($fields, $from, $where, $params, $sqlcount, $countparams);
    }

    /**
     * Provides a configured instance for the 'course participants with no attempts' option.
     *
     * Note: the method doesn't use {@see self::base_where_sql_and_params()}, as it builds a specific query for the case.
     *
     * @param int $adaptivequizid
     * @param sql_join $enrolledjoin
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

        return new self($fields, $from, $where, $params, $sqlcount, $params);
    }

    /**
     * Provides a configured instance for the 'course participants with attempts made' option.
     *
     * @param int $adaptivequizid
     * @param sql_join $enrolledjoin
     */
    public static function for_enrolled_with_attempts(int $adaptivequizid, sql_join $enrolledjoin): self {
        $fields = 'DISTINCT u.id' . fields::for_name()->including('email')->get_sql('u')->selects
            . ', ' . self::attempt_fields();
        $from = "
            {user} u
            $enrolledjoin->joins
            JOIN {adaptivequiz_attempt} aa ON (aa.userid = u.id)
        ";

        list($where, $params) = self::base_where_sql_and_params($adaptivequizid);
        $where .= " AND $enrolledjoin->wheres";
        $params = array_merge($params, $enrolledjoin->params);

        $sqlcount = "SELECT COUNT(DISTINCT u.id) FROM $from WHERE $where";
        $countparams = $params;

        list($attemptwhere, $attemptparams) = self::attempt_where_sql_and_params();
        $where .= " AND $attemptwhere";
        $params = array_merge($params, $attemptparams);

        return new self($fields, $from, $where, $params, $sqlcount, $countparams);
    }

    /**
     * Provides a configured instance for the 'attempts made by not enrolled users' option.
     *
     * @param int $adaptivequizid
     * @param sql_join $enrolledjoin
     */
    public static function for_not_enrolled_with_attempts(int $adaptivequizid, sql_join $enrolledjoin): self {
        $fields = 'DISTINCT u.id' . fields::for_name()->including('email')->get_sql('u')->selects
            . ', ' . self::attempt_fields();

        $from = '{adaptivequiz_attempt} aa JOIN {user} u ON u.id = aa.userid';

        list($where, $params) = self::base_where_sql_and_params($adaptivequizid);
        $where .= " AND NOT EXISTS (
            SELECT DISTINCT u.id
            FROM {user} u
            $enrolledjoin->joins
            WHERE u.id = aa.userid AND $enrolledjoin->wheres
        )";
        $params = array_merge($params, $enrolledjoin->params);

        $sqlcount = "SELECT COUNT(DISTINCT u.id) FROM $from WHERE $where";
        $countparams = $params;

        list($attemptwhere, $attemptparams) = self::attempt_where_sql_and_params();
        $where .= " AND $attemptwhere";
        $params = array_merge($params, $attemptparams);

        return new self($fields, $from, $where, $params, $sqlcount, $countparams);
    }

    /**
     * Fields related to the attempt data.
     *
     * @return string SQL for the fields, 'aa' is a field's alias by default.
     */
    private static function attempt_fields(): string {
        return 'aa.id AS attemptid,
            aa.measure,
            aa.standarderror AS stderror,
            aa.timemodified AS attempttimefinished,
            (
                SELECT COUNT(*)
                FROM {adaptivequiz_attempt} caa
                WHERE caa.userid = u.id
                    AND caa.instance = aa.instance
            ) AS attemptsnum';
    }

    /**
     * Prepares the base piece of SQL and its parameters used in several cases.
     *
     * @param int $adaptivequizid
     * @return array Two elements: SQL (string) and its parameters (array).
     */
    private static function base_where_sql_and_params(int $adaptivequizid): array {
        return [
            'u.deleted = :userdeleted AND aa.instance = :instance', [
                'userdeleted' => 0,
                'instance' => $adaptivequizid,
            ],
        ];
    }

    /**
     * Prepares the base piece of SQL and its parameters used in several cases.
     *
     * @return array Two elements: SQL (string) and its parameters (array).
     */
    private static function attempt_where_sql_and_params(): array {
        return [
            'aa.attemptstate = :attemptstate
            AND aa.measure = (
                SELECT MAX(measure)
                FROM {adaptivequiz_attempt}
                WHERE userid = aa.userid
                    AND instance = aa.instance
                GROUP BY userid
            )', [
                'attemptstate' => attempt_state::COMPLETED,
            ],
        ];
    }
}
