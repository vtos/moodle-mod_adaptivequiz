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
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_adaptivequiz\local\report\individual_user_attempts;

require_once($CFG->libdir . '/tablelib.php');

use html_writer;
use mod_adaptivequiz\local\report\questions_difficulty_range;
use mod_adaptivequiz_renderer;
use moodle_url;
use stdClass;
use table_sql;

final class table extends table_sql {

    /**
     * @var mod_adaptivequiz_renderer $renderer
     */
    private $renderer;

    /**
     * @var filter $filter
     */
    private $filter;

    /**
     * @var questions_difficulty_range $questionsdifficultyrange
     */
    private $questionsdifficultyrange;

    /**
     * @var int $cmid
     */
    private $cmid;

    public function __construct(
        mod_adaptivequiz_renderer $renderer,
        filter $filter,
        moodle_url $baseurl,
        questions_difficulty_range $questionsdifficultyrange,
        int $cmid
    ) {
        parent::__construct('individualuserattemptstable');

        $this->renderer = $renderer;
        $this->filter = $filter;
        $this->questionsdifficultyrange = $questionsdifficultyrange;
        $this->cmid = $cmid;

        $this->init($baseurl);
    }

    public function get_sql_sort() {
        return 'timemodified DESC';
    }

    protected function col_attemptstate(stdClass $row): string {
        if (0 == strcmp('inprogress', $row->attemptstate)) {
            return get_string('recentinprogress', 'adaptivequiz');
        }

        return get_string('recentcomplete', 'adaptivequiz');
    }

    protected function col_score(stdClass $row): string {
        if ($row->measure === null || $row->stderror === null || $row->stderror == 0.0) {
            return 'n/a';
        }

        $formatmeasureparams = new stdClass();
        $formatmeasureparams->measure = $row->measure;
        $formatmeasureparams->highestlevel = $this->questionsdifficultyrange->highest_level();
        $formatmeasureparams->lowestlevel = $this->questionsdifficultyrange->lowest_level();

        return $this->renderer->format_measure($formatmeasureparams) .
            ' ' . $this->renderer->format_standard_error($row);
    }

    protected function col_timecreated(stdClass $row): string {
        return userdate($row->timecreated);
    }

    protected function col_timemodified(stdClass $row): string {
        return userdate($row->timemodified);
    }

    protected function col_actions(stdClass $row): string {
        $return = html_writer::link(
            new moodle_url(
                '/mod/adaptivequiz/reviewattempt.php',
                ['uniqueid' => $row->uniqueid, 'cmid' => $this->cmid, 'userid' => $row->userid]
            ),
            get_string('reviewattempt', 'adaptivequiz')
        );
        $return .= '&nbsp;&nbsp;';

        if ($row->attemptstate != ADAPTIVEQUIZ_ATTEMPT_COMPLETED) {
            $return .= html_writer::link(
                new moodle_url(
                    '/mod/adaptivequiz/closeattempt.php',
                    ['uniqueid' => $row->uniqueid, 'cmid' => $this->cmid, 'userid' => $row->userid]
                ),
                get_string('closeattempt', 'adaptivequiz')
            );
            $return .= '&nbsp;&nbsp;';
        }

        $return .= html_writer::link(
            new moodle_url(
                '/mod/adaptivequiz/delattempt.php',
                ['uniqueid' => $row->uniqueid, 'cmid' => $this->cmid, 'userid' => $row->userid]
            ),
            get_string('deleteattemp', 'adaptivequiz')
        );

        return $return;
    }

    private function init(moodle_url $baseurl): void {
        $this->define_columns(['attemptstate', 'attemptstopcriteria', 'questionsattempted', 'score',
            'timecreated', 'timemodified', 'actions']);
        $this->define_headers([
            get_string('attemptstate', 'adaptivequiz'),
            get_string('attemptstopcriteria', 'adaptivequiz'),
            get_string('questionsattempted', 'adaptivequiz'),
            get_string('score', 'adaptivequiz'),
            get_string('attemptstarttime', 'adaptivequiz'),
            get_string('attemptfinishedtimestamp', 'adaptivequiz'),
            '',
        ]);
        $this->set_content_alignment_in_columns();
        $this->define_baseurl($baseurl);
        $this->set_attribute('class', $this->attributes['class'] . ' ' . $this->uniqueid);
        $this->is_downloadable(false);
        $this->collapsible(false);
        $this->sortable(false);

        $this->set_sql(
            'id, userid, uniqueid, attemptstopcriteria, measure, attemptstate, questionsattempted,timemodified,
            standarderror AS stderror, timecreated',
            '{adaptivequiz_attempt}',
            'instance = :adaptivequiz AND userid = :userid',
            ['adaptivequiz' => $this->filter->adaptivequizid, 'userid' => $this->filter->userid]
        );
    }

    private function set_content_alignment_in_columns(): void {
        foreach (array_keys($this->columns) as $column) {
            $this->column_class[$column] .= ' text-center';
        }
    }
}
