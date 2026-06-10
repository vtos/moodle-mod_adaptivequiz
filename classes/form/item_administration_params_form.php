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

namespace mod_adaptivequiz\form;

use context;
use context_module;
use core_form\dynamic_form;
use mod_adaptivequiz\item_administration_params_helper;
use mod_adaptivequiz\item_bank_helper;
use moodle_url;

/**
 * Edits item administration settings for an 'adaptivequiz' instance.
 *
 * @package    mod_adaptivequiz
 * @copyright  2026 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_administration_params_form extends dynamic_form {

    #[\Override]
    protected function definition() {
        $form = $this->_form;

        $levelattrs = ['size' => '3', 'maxlength' => '3'];

        $form->addElement('text', 'highestlevel', get_string('highestlevel', 'adaptivequiz'), $levelattrs);
        $form->addHelpButton('highestlevel', 'highestlevel', 'adaptivequiz');
        $form->addRule('highestlevel', get_string('formelementempty', 'adaptivequiz'), 'required', null, 'client');
        $form->addRule('highestlevel', get_string('formelementnumeric', 'adaptivequiz'), 'numeric', null, 'client');
        $form->setType('highestlevel', PARAM_INT);

        $form->addElement('text', 'lowestlevel', get_string('lowestlevel', 'adaptivequiz'), $levelattrs);
        $form->addHelpButton('lowestlevel', 'lowestlevel', 'adaptivequiz');
        $form->addRule('lowestlevel', get_string('formelementempty', 'adaptivequiz'), 'required', null, 'client');
        $form->addRule('lowestlevel', get_string('formelementnumeric', 'adaptivequiz'), 'numeric', null, 'client');
        $form->setType('lowestlevel', PARAM_INT);

        $form->addElement('text', 'startinglevel', get_string('startinglevel', 'adaptivequiz'), $levelattrs);
        $form->addHelpButton('startinglevel', 'startinglevel', 'adaptivequiz');
        $form->addRule('startinglevel', get_string('formelementempty', 'adaptivequiz'), 'required', null, 'client');
        $form->addRule('startinglevel', get_string('formelementnumeric', 'adaptivequiz'), 'numeric', null, 'client');
        $form->setType('startinglevel', PARAM_INT);

        $qnumattrs = ['size' => '3', 'maxlength' => '3'];

        $form->addElement('text', 'minimumquestions', get_string('minimumquestions', 'adaptivequiz'), $qnumattrs);
        $form->addHelpButton('minimumquestions', 'minimumquestions', 'adaptivequiz');
        $form->addRule('minimumquestions', get_string('formelementempty', 'adaptivequiz'), 'required', null, 'client');
        $form->addRule('minimumquestions', get_string('formelementnumeric', 'adaptivequiz'), 'numeric', null, 'client');
        $form->setType('minimumquestions', PARAM_INT);

        $form->addElement('text', 'maximumquestions', get_string('maximumquestions', 'adaptivequiz'), $qnumattrs);
        $form->addHelpButton('maximumquestions', 'maximumquestions', 'adaptivequiz');
        $form->addRule('maximumquestions', get_string('formelementempty', 'adaptivequiz'), 'required', null, 'client');
        $form->addRule('maximumquestions', get_string('formelementnumeric', 'adaptivequiz'), 'numeric', null, 'client');
        $form->setType('maximumquestions', PARAM_INT);

        $standarderrorattrs = ['size' => '10', 'maxlength' => '10'];
        $form->addElement('text', 'standarderror', get_string('standarderror', 'adaptivequiz'), $standarderrorattrs);
        $form->addHelpButton('standarderror', 'standarderror', 'adaptivequiz');
        $form->addRule('standarderror', get_string('formelementempty', 'adaptivequiz'), 'required', null, 'client');
        $form->addRule('standarderror', get_string('formelementdecimal', 'adaptivequiz'), 'numeric', null, 'client');
        $form->setDefault('standarderror', 5.0);
        $form->setType('standarderror', PARAM_FLOAT);

        $form->addElement('hidden', 'id');
        $form->setType('id', PARAM_INT);
    }

    /**
     * Implements the abstract method.
     */
    public function process_dynamic_submission(): void {
        $id = $this->optional_param('id', null, PARAM_INT);
        $cm = get_coursemodule_from_id('adaptivequiz', $id, 0, false, MUST_EXIST);

        $data = $this->get_data();
        $data->id = $cm->instance;

        adaptivequiz_update_item_administration_params($data);
    }

    /**
     * Implements the abstract method.
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;

        $id = $this->optional_param('id', null, PARAM_INT);

        $cm = get_coursemodule_from_id('adaptivequiz', $id, 0, false, MUST_EXIST);
        $adaptivequiz  = $DB->get_record('adaptivequiz', ['id' => $cm->instance], '*', MUST_EXIST);

        $fields = item_administration_params_helper::fields();

        $formdata = ['id' => $id];
        foreach ($fields as $field) {
            $formdata[$field] = $adaptivequiz->{$field};
        }

        $this->set_data($formdata);
    }

    /**
     * Implements the abstract method.
     */
    protected function get_context_for_dynamic_submission(): context {
        $id = $this->optional_param('id', null, PARAM_INT);
        $cm = get_coursemodule_from_id('adaptivequiz', $id, 0, false, MUST_EXIST);

        return context_module::instance($cm->id);
    }

    /**
     * Implements the abstract method.
     */
    protected function check_access_for_dynamic_submission(): void {
        // TODO.
    }

    /**
     * Implements the abstract method.
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $id = $this->optional_param('id', null, PARAM_INT);

        return new moodle_url('/mod/adaptivequiz/itembank.php', ['id' => $id]);
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (0 >= $data['minimumquestions']) {
            $errors['minimumquestions'] = get_string('formelementnegative', 'adaptivequiz');
        }

        if (0 >= $data['maximumquestions']) {
            $errors['maximumquestions'] = get_string('formelementnegative', 'adaptivequiz');
        }

        if (0 >= $data['startinglevel']) {
            $errors['startinglevel'] = get_string('formelementnegative', 'adaptivequiz');
        }

        if (0 >= $data['lowestlevel']) {
            $errors['lowestlevel'] = get_string('formelementnegative', 'adaptivequiz');
        }

        if (0 >= $data['highestlevel']) {
            $errors['highestlevel'] = get_string('formelementnegative', 'adaptivequiz');
        }

        if (0.0 > (float) $data['standarderror'] || 50.0 <= (float) $data['standarderror']) {
            $errors['standarderror'] = get_string('formstderror', 'adaptivequiz');
        }

        // Validate higher and lower values.
        if ($data['minimumquestions'] >= $data['maximumquestions']) {
            $errors['minimumquestions'] = get_string('formminquestgreaterthan', 'adaptivequiz');
        }

        if ($data['lowestlevel'] >= $data['highestlevel']) {
            $errors['lowestlevel'] = get_string('formlowlevelgreaterthan', 'adaptivequiz');
        }

        if (!($data['startinglevel'] >= $data['lowestlevel'] && $data['startinglevel'] <= $data['highestlevel'])) {
            $errors['startinglevel'] = get_string('formstartleveloutofbounds', 'adaptivequiz');
        }

        // No need to trigger the database when there's some basic error already.
        if ($errors) {
            return $errors;
        }

        $cm = get_coursemodule_from_id('adaptivequiz', $data['id'], 0, false, MUST_EXIST);

        $lowestlevelnum = item_bank_helper::count_adaptivequiz_questions_with_difficulty_level(
            $cm->instance,
            $data['lowestlevel']
        );

        if (!$lowestlevelnum) {
            $errors['lowestlevel'] = get_string('itembankinvalidlevel', 'adaptivequiz');
        }

        $highestlevelnum = item_bank_helper::count_adaptivequiz_questions_with_difficulty_level(
            $cm->instance,
            $data['highestlevel']
        );

        if (!$highestlevelnum) {
            $errors['highestlevel'] = get_string('itembankinvalidlevel', 'adaptivequiz');
        }

        // TODO: further validation.

        return $errors;
    }
}
