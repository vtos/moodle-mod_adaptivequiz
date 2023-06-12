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

/**
 * A data class to shape pieces of SQL and its parameters required for fetching user attempts.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_own_attempts_sql_and_params {

    /**
     * @var string $fields
     */
    public $fields;

    /**
     * @var string $from
     */
    public $from;

    /**
     * @var string $where
     */
    public $where;

    /**
     * @var array $params A regular array of conditions to pass to a {@see moodle_database::get_records()} call, for example.
     */
    public $params;
}
