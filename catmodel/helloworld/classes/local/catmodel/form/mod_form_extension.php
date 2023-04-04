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
    catmodel_mod_form_modifier,
    catmodel_mod_form_validator,
    catmodel_mod_form_data_preprocessor {

    /**
     * Implementation of interface, {@see catmodel_mod_form_modifier::definition_after_data_callback()}.
     *
     * Adds several custom elements to the form.
     *
     * @param MoodleQuickForm $form
     */
    public function definition_callback(MoodleQuickForm $form): void {
        $form->addElement('text', 'catmodel_helloworld_param1', get_string('param1', 'adaptivequizcatmodel_helloworld'),
            ['size' => '3', 'maxlength' => '3']);
        $form->addHelpButton('catmodel_helloworld_param1', 'param1', 'adaptivequizcatmodel_helloworld');
        $form->setType('catmodel_helloworld_param1', PARAM_INT);
        $form->hideIf('catmodel_helloworld_param1', 'catmodel', 'neq', 'helloworld');

        $form->addElement('text', 'catmodel_helloworld_param2', get_string('param2', 'adaptivequizcatmodel_helloworld'),
            ['size' => '3', 'maxlength' => '3']);
        $form->addHelpButton('catmodel_helloworld_param2', 'param2', 'adaptivequizcatmodel_helloworld');
        $form->setType('catmodel_helloworld_param2', PARAM_INT);
        $form->hideIf('catmodel_helloworld_param2', 'catmodel', 'neq', 'helloworld');

        $form->addElement('hidden', 'catmodel_helloworld_catmodelinstanceid', 0);
        $form->setType('catmodel_helloworld_catmodelinstanceid', PARAM_INT);
        $form->hideIf('catmodel_helloworld_catmodelinstanceid', 'catmodel', 'neq', 'helloworld');
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

        $param1 = (int) $data['catmodel_helloworld_param1'];
        if (0 >= $param1) {
            $errors['catmodel_helloworld_param1'] = get_string('modform:entervalidcatinteger', 'adaptivequizcatmodel_helloworld');
        }

        $param2 = (int) $data['catmodel_helloworld_param2'];
        if (0 >= $param2) {
            $errors['catmodel_helloworld_param2'] = get_string('modform:entervalidcatinteger', 'adaptivequizcatmodel_helloworld');
        }

        if ($param1 >= $param2) {
            $errors['catmodel_helloworld_param1'] = get_string('modform:param1mustbelessthanparam2',
                'adaptivequizcatmodel_helloworld');
        }

        return $errors;
    }

    /**
     * Implementation of interface, {@see catmodel_mod_form_data_preprocessor::data_preprocessing_callback()}.
     *
     * Fetches custom CAT model's record to populate the related form fields.
     *
     * @param array $formdefaultvalues
     * @return array
     */
    public function data_preprocessing_callback(array $formdefaultvalues): array {
        global $DB;

        $helloworldsettings = $DB->get_record('catmodel_helloworld', ['adaptivequizid' => $formdefaultvalues['instance']], '*',
            MUST_EXIST);

        $formdefaultvalues['catmodel_helloworld_catmodelinstanceid'] = $helloworldsettings->id;
        $formdefaultvalues['catmodel_helloworld_param1'] = $helloworldsettings->param1;
        $formdefaultvalues['catmodel_helloworld_param2'] = $helloworldsettings->param2;

        return $formdefaultvalues;
    }
}
