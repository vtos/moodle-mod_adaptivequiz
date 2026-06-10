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

namespace mod_adaptivequiz\output;

use core\output\notification;
use core\output\single_button;
use renderable;
use renderer_base;
use templatable;

/**
 * Output object to render controls to start/continue an attempt or a notification if an attempt is not possible.
 *
 * @package    mod_adaptivequiz
 * @copyright  2026 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class start_attempt implements renderable, templatable {

    /**
     * The constructor.
     *
     * @param single_button|null $startbutton Starts new or continues the previous attempt.
     * @param notification|null $notification A notification in case attempting the adaptive quiz is not available.
     */
    public function __construct(
        private readonly ?single_button $startbutton = null,
        private readonly ?notification $notification = null
    ) {
    }

    /**
     * Implements the interface.
     *
     * @param renderer_base $output
     * @return \stdClass|array
     */
    public function export_for_template(renderer_base $output) {
        $return = [];

        if ($this->startbutton) {
            $return['startbutton'] = $this->startbutton->export_for_template($output);
        }
        if ($this->notification) {
            $return['startnotification'] = $this->notification->export_for_template($output);
        }

        return $return;
    }
}
