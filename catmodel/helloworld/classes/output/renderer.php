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

namespace adaptivequizcatmodel_helloworld\output;

use html_writer;
use moodle_url;
use pix_icon;
use plugin_renderer_base;

/**
 * Plugin's renderer.
 *
 * @package    adaptivequizcatmodel_helloworld
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Renders actions, available for an attempt in attempts report.
     *
     * @param int $cmid
     * @param int $attemptid
     * @param int $userid
     * @return string
     */
    public function attempts_report_attempt_actions(int $cmid, int $attemptid, int $userid): string {
        $url = new moodle_url('/mod/adaptivequiz/delattempt.php', [
            'id' => $cmid,
            'attempt' => $attemptid,
            'user' => $userid,
            'return' => (new moodle_url('/mod/adaptivequiz/catmodel/helloworld/report.php', ['id' => $cmid]))->out(),
        ]);

        return html_writer::link(
            $url,
            $this->render(new pix_icon('t/delete', '')),
            ['title' => get_string('deleteattemp', 'adaptivequiz')]
        );
    }
}
