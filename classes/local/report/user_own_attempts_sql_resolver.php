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

namespace mod_adaptivequiz\local\report;

use core\dml\sql_join;
use core\dml\table as dml_table;

/**
 * A class to wrap building of SQL and its parameters to use for fetching data for the attempts table.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_own_attempts_sql_resolver {

    /**
     * @var dml_table $attemptstable
     */
    private $attemptstable;

    /**
     * @var sql_join $join
     */
    private $join;

    /**
     * @var bool $withabilitymeasure Whether to fetch info about ability measure for the attempt.
     */
    private $withabilitymeasure;

    /**
     * The constructor.
     *
     * Closed, the factory method must be used instead.
     */
    private function __construct() {
    }

    /**
     * Returns proper pieces of SQL and its parameters for fetching attempts.
     *
     * @param int $adaptivequizid
     * @param int $userid
     * @return user_own_attempts_sql_and_params
     */
    public function sql_and_params_for_user(int $adaptivequizid, int $userid): user_own_attempts_sql_and_params {
        $fields = $this->attemptstable->get_field_select();
        if ($this->withabilitymeasure) {
            $fields .= ', aq.highestlevel, aq.lowestlevel, acp.measure';
        }

        $sqlandparams = new user_own_attempts_sql_and_params();
        $sqlandparams->fields = $fields;
        $sqlandparams->from = "{adaptivequiz_attempt} a\n". $this->join->joins;
        $sqlandparams->where = 'aq.id = :adaptivequizid AND a.userid = :userid';
        $sqlandparams->params = ['adaptivequizid' => $adaptivequizid, 'userid' => $userid];

        return $sqlandparams;
    }

    /**
     * A named constructor.
     *
     * @param bool $withabilitymeasure
     * @return self
     */
    public static function init(bool $withabilitymeasure): self {
        $joins = 'JOIN {adaptivequiz} aq ON a.instance = aq.id';
        if ($withabilitymeasure) {
            $joins .= "\nJOIN {adaptivequiz_cat_params} acp ON a.id = acp.attempt";
        }

        $resolver = new self();
        $resolver->attemptstable = new dml_table('adaptivequiz_attempt', 'a', '');
        $resolver->join = new sql_join($joins);
        $resolver->withabilitymeasure = $withabilitymeasure;

        return $resolver;
    }
}
