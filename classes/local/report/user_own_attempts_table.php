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

use coding_exception;
use help_icon;
use mod_adaptivequiz\local\attempt\attempt_state;
use mod_adaptivequiz_renderer;
use moodle_url;
use stdClass;
use table_sql;

/**
 * A class to display a table with user's own attempts on the activity's view page.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_own_attempts_table extends table_sql {

    /**
     * @var mod_adaptivequiz_renderer $renderer
     */
    private $renderer;

    /**
     * The constructor.
     *
     * Closed, the factory method must be used instead.
     */
    private function __construct() {
        parent::__construct('userattemptstable');
    }

    /**
     * A factory method to wrap initialization of the table.
     *
     * @param mod_adaptivequiz_renderer $renderer
     * @param moodle_url $baseurl
     * @param stdClass $adaptivequiz A record form {adaptivequiz}.
     * @return self
     * @throws coding_exception
     */
    public static function init(mod_adaptivequiz_renderer $renderer, moodle_url $baseurl, stdClass $adaptivequiz): self {
        global $USER;

        $showabilitymeasure = empty($adaptivequiz->catmodel) && $adaptivequiz->showabilitymeasure;

        $table = new self();
        $table->renderer = $renderer;

        $columns = ['attemptstate', 'timemodified'];
        if ($showabilitymeasure) {
            $columns[] = 'measure';
        }
        $table->define_columns($columns);

        $headers = [
            get_string('attempt_state', 'adaptivequiz'),
            get_string('attemptfinishedtimestamp', 'adaptivequiz'),
        ];
        if ($showabilitymeasure) {
            $headers[] = get_string('abilityestimated', 'adaptivequiz') . ' / ' .
                $adaptivequiz->lowestlevel . ' - ' . $adaptivequiz->highestlevel;
        }
        $table->define_headers($headers);

        $table->set_attribute('class', 'generaltable userattemptstable');
        $table->is_downloadable(false);
        $table->collapsible(false);
        $table->sortable(false, 'timemodified', SORT_DESC);
        $table->define_help_for_headers(
            [2 => new help_icon('abilityestimated', 'adaptivequiz')]
        );
        $table->set_column_css_classes();
        $table->set_content_alignment_in_columns();
        $table->define_baseurl($baseurl);

        $sqlresolver = user_own_attempts_sql_resolver::init($showabilitymeasure);
        $sqlandparams = $sqlresolver->sql_and_params_for_user($adaptivequiz->id, $USER->id);
        $table->set_sql($sqlandparams->fields, $sqlandparams->from, $sqlandparams->where, $sqlandparams->params);

        return $table;
    }

    /**
     * Handles value for the attempt state column.
     *
     * @param stdClass $row
     * @return string
     */
    protected function col_attemptstate(stdClass $row): string {
        return get_string('recent' . $row->attemptstate, 'adaptivequiz');
    }

    /**
     * Handles value for the column with the attempt finish time value.
     *
     * @param stdClass $row
     * @return string
     */
    protected function col_timemodified(stdClass $row): string {
        if ($row->attemptstate != attempt_state::COMPLETED) {
            return '';
        }

        return userdate($row->timemodified);
    }

    /**
     * Handles value for the measure column.
     *
     * @param stdClass $row
     * @return string
     */
    protected function col_measure(stdClass $row): string {
        return $this->renderer->format_measure($row);
    }

    /**
     * Applies required alignment to certain columns.
     */
    private function set_content_alignment_in_columns(): void {
        foreach (array_keys($this->columns) as $columnname) {
            $this->column_class[$columnname] .= ' text-center';
        }
    }

    /**
     * Sets CSS classes for columns where required.
     */
    private function set_column_css_classes(): void {
        $this->column_class['attemptstate'] .= ' statecol';

        if (array_key_exists('measure', $this->columns)) {
            $this->column_class['measure'] .= ' abilitymeasurecol';
        }
    }
}
