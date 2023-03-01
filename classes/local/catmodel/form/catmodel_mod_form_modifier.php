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
 * Interface to add/remove certain fields to adaptive quiz's mod_form.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_adaptivequiz\local\catmodel\form;

use MoodleQuickForm;

interface catmodel_mod_form_modifier {

    /**
     * Called by mod_form in definition_after_data() method.
     *
     * Used to modify the form to add/remove fields as required by the custom CAT model.
     *
     * @param MoodleQuickForm $form
     * @return array An array of form elements added. Each element you add in this method must be returned within this array,
     * so mod_from could handle it.
     */
    public function definition_after_data_callback(MoodleQuickForm $form): array;
}
