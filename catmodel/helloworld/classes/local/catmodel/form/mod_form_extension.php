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

namespace adaptivequizcatmodel_helloworld\local\catmodel\form;

use mod_adaptivequiz\local\catmodel\form\catmodel_mod_form_data_preprocessor;
use mod_adaptivequiz\local\catmodel\form\catmodel_mod_form_modifier;
use mod_adaptivequiz\local\catmodel\form\catmodel_mod_form_validator;
use MoodleQuickForm;

/**
 * Implements interfaces to change behaviour of adaptive quiz's mod_form.
 *
 * @package    adaptivequizcatmodel_helloworld
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
        $formelements = [];

        $formelements[] = $form->addElement('text', 'param1', get_string('param1', 'adaptivequizcatmodel_helloworld'),
            ['size' => '3', 'maxlength' => '3']);
        $form->addHelpButton('param1', 'param1', 'adaptivequizcatmodel_helloworld');
        $form->addRule('param1', get_string('err_required', 'form'), 'required', null, 'client');
        $form->addRule('param1', get_string('err_numeric', 'form'), 'numeric', null, 'client');
        $form->setType('param1', PARAM_INT);

        $formelements[] = $form->addElement('text', 'param2', get_string('param2', 'adaptivequizcatmodel_helloworld'),
            ['size' => '3', 'maxlength' => '3']);
        $form->addHelpButton('param2', 'param2', 'adaptivequizcatmodel_helloworld');
        $form->addRule('param2', get_string('err_required', 'form'), 'required', null, 'client');
        $form->addRule('param2', get_string('err_numeric', 'form'), 'numeric', null, 'client');
        $form->setType('param2', PARAM_INT);

        $form->addElement('hidden', 'catmodelinstanceid', 0);
        $form->setType('catmodelinstanceid', PARAM_INT);

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
        $errors = [];

        if (0 >= $data['param1']) {
            $errors['param1'] = get_string('formelementnegative', 'adaptivequizcatmodel_helloworld');
        }
        if (0 >= $data['param2']) {
            $errors['param2'] = get_string('formelementnegative', 'adaptivequizcatmodel_helloworld');
        }
        if ($data['param1'] >= $data['param2']) {
            $errors['param1'] = get_string('formlowlevelgreaterthan', 'adaptivequizcatmodel_helloworld');
        }

        return $errors;
    }

    /**
     * Implementation of interface, {@see catmodel_mod_form_data_preprocessor::data_preprocessing_callback()}.
     *
     * Fetches id of the custom CAT model's record to enable using it in the form when updating the model's parameters.
     *
     * @param array $formdefaultvalues
     * @param MoodleQuickForm|null $form
     * @return array
     */
    public function data_preprocessing_callback(array $formdefaultvalues, $form = null): array {
        global $DB;

        $formdefaultvalues['catmodelinstanceid'] = $DB->get_field('catmodel_helloworld', 'id',
            ['adaptivequizid' => $formdefaultvalues['instance']], MUST_EXIST);

        return $formdefaultvalues;
    }
}
