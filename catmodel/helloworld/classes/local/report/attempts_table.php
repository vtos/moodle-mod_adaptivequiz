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

namespace adaptivequizcatmodel_helloworld\local\report;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

use adaptivequizcatmodel_helloworld\output\renderer as helloworld_renderer;
use core_user\fields;
use moodle_url;
use stdClass;
use table_sql;

/**
 * Definition of the table class to display attempts to a manager.
 *
 * @package    adaptivequizcatmodel_helloworld
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class attempts_table extends table_sql {

    /**
     * @var helloworld_renderer $renderer
     */
    private $renderer;

    /**
     * @var stdClass $cm A course module record.
     */
    private $cm;

    /**
     * @var attempts_filter $filter
     */
    private $filter;

    /**
     * The constructor.
     *
     * @param stdClass $cm
     * @param moodle_url $baseurl
     */
    public function __construct(helloworld_renderer $renderer, stdClass $cm, attempts_filter $filter, moodle_url $baseurl) {
        parent::__construct('attemptstable');

        $this->renderer = $renderer;
        $this->cm = $cm;
        $this->filter = $filter;

        $this->init($baseurl);
    }

    /**
     * Returns an SQL fragment that can be used in an ORDER BY clause.
     *
     * @return string
     */
    public function get_sql_sort(): string {
        return 'a.timemodified DESC';
    }

    /**
     * Handles value for the column with the user's name.
     *
     * @param stdClass $row
     * @return string
     */
    protected function col_user(stdClass $row): string {
        return fullname($row);
    }

    /**
     * Handles value for the column with the time when attempt was finished.
     *
     * @param stdClass $row
     * @return string
     */
    protected function col_timefinished(stdClass $row): string {
        return userdate($row->timemodified);
    }

    /**
     * A hook to format the output of actions column for an attempt row.
     *
     * @param stdClass $row A row from the {adaptivequiz_attempt}.
     * @return string
     */
    protected function col_actions(stdClass $row): string {
        return $this->renderer->attempts_report_attempt_actions($this->cm->id, $row->id, $row->userid);
    }

    /**
     * A convenience method to call a bunch of init methods.
     *
     * @param moodle_url $baseurl
     */
    private function init(moodle_url $baseurl): void {
        $this->define_columns(['user', 'timefinished', 'actions']);
        $this->define_headers([
            get_string('attemptsreport:user', 'adaptivequizcatmodel_helloworld'),
            get_string('attemptsreport:timefinished', 'adaptivequizcatmodel_helloworld'),
            '',
        ]);
        $this->set_content_alignment_in_columns();
        $this->define_baseurl($baseurl);
        $this->set_attribute('class', $this->attributes['class'] . ' ' . $this->uniqueid);
        $this->is_downloadable(false);
        $this->collapsible(false);
        $this->sortable(false);
        $this->set_sql(
            fields::for_name()
                ->including('id', 'email')
                ->get_sql('u', false, '', '', false)->selects
            . ', a.timemodified, a.id, a.userid',
            '{adaptivequiz_attempt} a JOIN {user} u ON u.id = a.userid',
            'a.instance = :adaptivequiz AND u.deleted = 0',
            ['adaptivequiz' => $this->filter->adaptivequizid]
        );
    }

    /**
     * Applies required alignment to certain columns.
     */
    private function set_content_alignment_in_columns(): void {
        $this->column_class['timefinished'] .= ' text-center';
        $this->column_class['actions'] .= ' text-center';
    }
}
