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

namespace adaptivequizcatmodel_catquiz\local\catmodel\form;

use local_catquiz\catquiz_handler;
use mod_adaptivequiz\local\catmodel\form\catmodel_mod_form_data_preprocessor;
use mod_adaptivequiz\local\catmodel\form\catmodel_mod_form_modifier;
use mod_adaptivequiz\local\catmodel\form\catmodel_mod_form_validator;
use MoodleQuickForm;

/**
 * Implements interfaces to change behaviour of adaptive quiz's mod_form.
 *
 * @package    adaptivequizcatmodel_catquiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class mod_form_extension implements
    catmodel_mod_form_modifier, catmodel_mod_form_validator, catmodel_mod_form_data_preprocessor {

    /**
     * Implementation of interface, {@see catmodel_mod_form_modifier::definition_after_data_callback()}.
     *
     * Adds several custom elements to the form.
     *
     * @param MoodleQuickForm $form
     * @return array
     */
    public function definition_after_data_callback(MoodleQuickForm $form): array {

        $data = $form->exportValues();

        if ($data['catmodel'] === 'catquiz') {
            $formelements = catquiz_handler::instance_form_definition($form);

            // At this point, we also apply the values we get from the template to the whole mform.

            catquiz_handler::set_data_after_definition($form);

        }

        // Remove some default form fields the sub-plugin does not use.
        $defaultelementstodrop = ['startinglevel', 'stopingconditionshdr', 'minimumquestions', 'maximumquestions', 'standarderror',
            'questionpool', 'lowestlevel', 'highestlevel', 'questionselectionheading'];
        foreach ($defaultelementstodrop as $elementname) {

            if ($form->elementExists($elementname)) {
                $form->removeElement($elementname);
            }
        }

        return $formelements;
    }

    /**
     * Implementation of interface, {@see catmodel_mod_form_validator::validation_callback()}.
     *
     * Validation of fields introduced by this CAT model.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation_callback(array $data, array $files): array {

        if ($data['catmodel'] === 'catquiz') {
            $errors = catquiz_handler::instance_form_validation($data, $files);
        }

        return $errors;
    }

    /**
     * Implementation of interface, {@see catmodel_mod_form_data_preprocessor::data_preprocessing_callback()}.
     *
     * Fetches id of the custom CAT model's record to enable using it in the form when updating the model's parameters.
     *
     * @param array $formdefaultvalues
     * @param MoodleQuickForm $form
     * @return array
     */
    public function data_preprocessing_callback(array $formdefaultvalues, ?MoodleQuickForm $form = null): array {

        catquiz_handler::data_preprocessing($formdefaultvalues, $form);

        return $formdefaultvalues;
    }
}
