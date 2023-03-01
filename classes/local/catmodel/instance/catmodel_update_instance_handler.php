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

/**
 * Interface to add custom behaviour when an instance of adaptive quiz is updated.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_adaptivequiz\local\catmodel\instance;

use mod_adaptivequiz_mod_form;
use stdClass;

interface catmodel_update_instance_handler {

    /**
     * Called when an instance of adaptive quiz activity is updated.
     *
     * Accepts submitted data from mod_form and the form object itself to perform custom steps required by a custom CAT model.
     *
     * @param stdClass $adaptivequiz Submitted instance data from mod_form.
     * @param mod_adaptivequiz_mod_form|null $form
     */
    public function update_instance_callback(stdClass $adaptivequiz, ?mod_adaptivequiz_mod_form $form = null): void;
}
