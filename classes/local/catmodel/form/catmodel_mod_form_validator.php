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
 * Interface to be implemented when a custom CAT model wants to add some validation to the mod_form.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_adaptivequiz\local\catmodel\form;

interface catmodel_mod_form_validator {

    /**
     * Adds validation to mod_form which may be required for the fields added by the custom CAT model.
     *
     * See the description of parameters and return values for {@see \moodleform::validation()}.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation_callback(array $data, array $files): array;
}
