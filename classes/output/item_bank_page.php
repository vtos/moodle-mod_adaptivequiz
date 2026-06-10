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

use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Output object to render the page with item bank management.
 *
 * @package    mod_adaptivequiz
 * @copyright  2026 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_bank_page implements renderable, templatable {

    /**
     * The constructor.
     */
    public function __construct(
        private readonly item_bank_notification $notification,
        private readonly item_bank_qbanks $qbanks,
        private readonly item_bank_qcategories $qcategories,
        private readonly item_administration_params $params) {
    }

    /**
     * Implements the interface.
     *
     * @param renderer_base $output
     * @return stdClass|array
     */
    public function export_for_template(renderer_base $output) {
        return array_merge(
            $this->notification->export_for_template($output),
            $this->qbanks->export_for_template($output),
            $this->qcategories->export_for_template($output),
            $this->params->export_for_template($output),
        );
    }
}
