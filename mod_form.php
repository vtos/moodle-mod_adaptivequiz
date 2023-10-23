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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/adaptivequiz/locallib.php');

use mod_adaptivequiz\local\repository\questions_repository;

/**
 * Definition of activity settings form.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_adaptivequiz_mod_form extends moodleform_mod {

    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;

        $pluginconfig = get_config('adaptivequiz');

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('adaptivequizname', 'adaptivequiz'), ['size' => '64']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'adaptivequizname', 'adaptivequiz');

        // Adding the standard "intro" and "introformat" fields.
        // Use the non deprecated function if it exists.
        if (method_exists($this, 'standard_intro_elements')) {
            $this->standard_intro_elements();
        } else {
            // Deprecated as of Moodle 2.9.
            $this->add_intro_editor();
        }

        // Number of attempts.
        $attemptoptions = ['0' => get_string('unlimited')];
        for ($i = 1; $i <= ADAPTIVEQUIZMAXATTEMPT; $i++) {
            $attemptoptions[$i] = $i;
        }
        $mform->addElement('select', 'attempts', get_string('attemptsallowed', 'adaptivequiz'), $attemptoptions);
        $mform->setDefault('attempts', 0);
        $mform->addHelpButton('attempts', 'attemptsallowed', 'adaptivequiz');

        // Require password to begin adaptivequiz attempt.
        $mform->addElement('passwordunmask', 'password', get_string('requirepassword', 'adaptivequiz'));
        $mform->setType('password', PARAM_TEXT);
        $mform->addHelpButton('password', 'requirepassword', 'adaptivequiz');

        // Browser security choices.
        $options = [
            get_string('no'),
            get_string('yes'),
        ];
        $mform->addElement('select', 'browsersecurity', get_string('browsersecurity', 'adaptivequiz'), $options);
        $mform->addHelpButton('browsersecurity', 'browsersecurity', 'adaptivequiz');
        $mform->setDefault('browsersecurity', 0);

        $mform->addElement('textarea', 'attemptfeedback', get_string('attemptfeedback', 'adaptivequiz'),
            'wrap="virtual" rows="10" cols="50"');
        $mform->addHelpButton('attemptfeedback', 'attemptfeedback', 'adaptivequiz');
        $mform->setType('attemptfeedback', PARAM_NOTAGS);

        $mform->addElement('select', 'showabilitymeasure', get_string('showabilitymeasure', 'adaptivequiz'),
            [get_string('no'), get_string('yes')]);
        $mform->addHelpButton('showabilitymeasure', 'showabilitymeasure', 'adaptivequiz');
        $mform->setDefault('showabilitymeasure', 0);

        $mform->addElement('select', 'showattemptprogress', get_string('modformshowattemptprogress', 'adaptivequiz'),
            [get_string('no'), get_string('yes')]);
        $mform->addHelpButton('showattemptprogress', 'modformshowattemptprogress', 'adaptivequiz');
        $mform->setDefault('showattemptprogress', 0);

        $this->add_cat_model_section_when_applicable($mform);

        // Button to attach JavaScript to to reload the form.
        $mform->registerNoSubmitButton('submitcatmodeloption');
        $mform->addElement('submit', 'submitcatmodeloption', get_string('modformsubmitcatmodeloption', 'adaptivequiz'),
            ['class' => 'd-none', 'data-action' => 'submitCatModel']);

        $mform->addElement('header', 'questionselectionheading', get_string('modformquestionselectionheading', 'adaptivequiz'));

        // Retireve a list of available course categories.
        adaptivequiz_make_default_categories($this->context);
        $options = adaptivequiz_get_question_categories($this->context);
        $selquestcat = adaptivequiz_get_selected_question_cateogires($this->_instance);

        $select = $mform->addElement('select', 'questionpool', get_string('questionpool', 'adaptivequiz'), $options);
        $mform->addHelpButton('questionpool', 'questionpool', 'adaptivequiz');
        $select->setMultiple(true);
        $mform->addRule('questionpool', null, 'required', null, 'client');
        $mform->getElement('questionpool')->setSelected($selquestcat);

        $mform->addElement('text', 'startinglevel', get_string('startinglevel', 'adaptivequiz'),
            ['size' => '3', 'maxlength' => '3']);
        $mform->addHelpButton('startinglevel', 'startinglevel', 'adaptivequiz');
        $mform->addRule('startinglevel', get_string('formelementempty', 'adaptivequiz'), 'required', null, 'client');
        $mform->addRule('startinglevel', get_string('formelementnumeric', 'adaptivequiz'), 'numeric', null, 'client');
        $mform->setType('startinglevel', PARAM_INT);
        $mform->setDefault('startinglevel', $pluginconfig->startinglevel);

        $mform->addElement('text', 'lowestlevel', get_string('lowestlevel', 'adaptivequiz'),
            ['size' => '3', 'maxlength' => '3']);
        $mform->addHelpButton('lowestlevel', 'lowestlevel', 'adaptivequiz');
        $mform->addRule('lowestlevel', get_string('formelementempty', 'adaptivequiz'), 'required', null, 'client');
        $mform->addRule('lowestlevel', get_string('formelementnumeric', 'adaptivequiz'), 'numeric', null, 'client');
        $mform->setType('lowestlevel', PARAM_INT);
        $mform->setDefault('lowestlevel', $pluginconfig->lowestlevel);

        $mform->addElement('text', 'highestlevel', get_string('highestlevel', 'adaptivequiz'),
            ['size' => '3', 'maxlength' => '3']);
        $mform->addHelpButton('highestlevel', 'highestlevel', 'adaptivequiz');
        $mform->addRule('highestlevel', get_string('formelementempty', 'adaptivequiz'), 'required', null, 'client');
        $mform->addRule('highestlevel', get_string('formelementnumeric', 'adaptivequiz'), 'numeric', null, 'client');
        $mform->setType('highestlevel', PARAM_INT);
        $mform->setDefault('highestlevel', $pluginconfig->highestlevel);

        $mform->addElement('header', 'stopingconditionshdr', get_string('stopingconditionshdr', 'adaptivequiz'));

        $mform->addElement('text', 'minimumquestions', get_string('minimumquestions', 'adaptivequiz'),
            ['size' => '3', 'maxlength' => '3']);
        $mform->addHelpButton('minimumquestions', 'minimumquestions', 'adaptivequiz');
        $mform->addRule('minimumquestions', get_string('formelementempty', 'adaptivequiz'), 'required', null, 'client');
        $mform->addRule('minimumquestions', get_string('formelementnumeric', 'adaptivequiz'), 'numeric', null, 'client');
        $mform->setType('minimumquestions', PARAM_INT);
        $mform->setDefault('minimumquestions', $pluginconfig->minimumquestions);

        $mform->addElement('text', 'maximumquestions', get_string('maximumquestions', 'adaptivequiz'),
            ['size' => '3', 'maxlength' => '3']);
        $mform->addHelpButton('maximumquestions', 'maximumquestions', 'adaptivequiz');
        $mform->addRule('maximumquestions', get_string('formelementempty', 'adaptivequiz'), 'required', null, 'client');
        $mform->addRule('maximumquestions', get_string('formelementnumeric', 'adaptivequiz'), 'numeric', null, 'client');
        $mform->setType('maximumquestions', PARAM_INT);
        $mform->setDefault('maximumquestions', $pluginconfig->maximumquestions);

        $mform->addElement('text', 'standarderror', get_string('standarderror', 'adaptivequiz'),
            ['size' => '10', 'maxlength' => '10']);
        $mform->addHelpButton('standarderror', 'standarderror', 'adaptivequiz');
        $mform->addRule('standarderror', get_string('formelementempty', 'adaptivequiz'), 'required', null, 'client');
        $mform->addRule('standarderror', get_string('formelementdecimal', 'adaptivequiz'), 'numeric', null, 'client');
        $mform->setType('standarderror', PARAM_FLOAT);
        $mform->setDefault('standarderror', $pluginconfig->standarderror);

        // Grade settings.
        $this->standard_grading_coursemodule_elements();
        $mform->removeElement('grade');

        // Grading method.
        $mform->addElement('select', 'grademethod', get_string('grademethod', 'adaptivequiz'),
                adaptivequiz_get_grading_options());
        $mform->addHelpButton('grademethod', 'grademethod', 'adaptivequiz');
        $mform->setDefault('grademethod', ADAPTIVEQUIZ_GRADEHIGHEST);
        $mform->disabledIf('grademethod', 'attempts', 'eq', 1);

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    /**
     * Overriding of the parent's method, {@see moodleform_mod::data_preprocessing()}.
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);

        // Run preprocessing hook from the custom CAT model being used (if any).
        if (empty($defaultvalues['catmodel'])) {
            return;
        }

        $catmodel = $defaultvalues['catmodel'];

        $formdatapreprocessorclasses = core_component::get_component_classes_in_namespace(
            "adaptivequizcatmodel_$catmodel",
            'local\catmodel\form'
        );
        if (empty($formdatapreprocessorclasses)) {
            return;
        }

        $classnames = array_keys($formdatapreprocessorclasses);
        foreach ($classnames as $classname) {
            if (!is_subclass_of($classname, '\mod_adaptivequiz\local\catmodel\form\catmodel_mod_form_data_preprocessor')) {
                continue;
            }

            $formdatapreprocessor = new $classname();
            $defaultvalues = $formdatapreprocessor->data_preprocessing_callback($defaultvalues, $this->_form);

            break;
        }
    }

    public function add_completion_rules(): array {
        $form = $this->_form;
        $form->addElement('checkbox', 'completionattemptcompleted', ' ',
            get_string('completionattemptcompletedform', 'adaptivequiz'));

        return ['completionattemptcompleted'];
    }

    public function completion_rule_enabled($data): bool {
        if (!isset($data['completionattemptcompleted'])) {
            return false;
        }

        return $data['completionattemptcompleted'] != 0;
    }

    /**
     * Set up the form depending on current values.
     */
    public function definition_after_data() {
        $form = $this->_form;

        $catmodelvalue = $form->getElementValue('catmodel');
        if (empty($catmodelvalue[0])) {
            return;
        }

        // Remove fields, which relate to the default algorithm and do not make sense with a custom CAT model.
        $form->removeElement('showabilitymeasure');

        $catmodel = $catmodelvalue[0];

        $formmodifierclasses = core_component::get_component_classes_in_namespace(
            "adaptivequizcatmodel_$catmodel",
            'local\catmodel\form'
        );
        if (empty($formmodifierclasses)) {
            return;
        }

        $classnames = array_keys($formmodifierclasses);
        foreach ($classnames as $classname) {
            if (!is_subclass_of($classname, '\mod_adaptivequiz\local\catmodel\form\catmodel_mod_form_modifier')) {
                continue;
            }

            $formmodifier = new $classname();
            $formelements = $formmodifier->definition_after_data_callback($form);
            if (empty($formelements)) {
                break;
            }

            foreach ($formelements as $formelement) {
                $value = $formelement->getValue();
                $formelement = $form->insertElementBefore($form->removeElement($formelement->getName(), false), 'catmodelfieldsmarker');
                if (!empty($value)) {
                    $formelement->setValue($value);
                }
            }

            break;
        }
    }

    /**
     * Overriding of the parent's method, {@see moodleform_mod::validation()}.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // When there's a custom CAT model submitted, we wire up its form validation if exists and skip the default validation.
        if (!empty($data['catmodel'])) {
            return array_merge($errors, $this->validate_cat_model_fields_or_skip($data, $files));
        }

        if (empty($data['questionpool'])) {
            $errors['questionpool'] = get_string('formquestionpool', 'adaptivequiz');
        }

        // Validate for positivity.
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

        if ((float) 0 > (float) $data['standarderror'] || (float) 50 <= (float) $data['standarderror']) {
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

        if ($questionspoolerrormsg = $this->validate_questions_pool($data['questionpool'], $data['startinglevel'])) {
            $errors['questionpool'] = $questionspoolerrormsg;
        }

        return $errors;
    }

    /**
     * @param int[] $qcategoryidlist A list of id of selected questions categories.
     * @return string An error message if any.
     * @throws coding_exception
     */
    private function validate_questions_pool(array $qcategoryidlist, int $startinglevel): string {
        return questions_repository::count_adaptive_questions_in_pool_with_level($qcategoryidlist, $startinglevel) > 0
            ? ''
            : get_string('questionspoolerrornovalidstartingquestions', 'adaptivequiz');
    }

    /**
     * Searches for implementation of form validation by the CAT model plugin and applies it when found.
     *
     * Parameters are same as for {@see moodleform_mod::validation()}.
     *
     * @param array $data
     * @param array $files
     * @return array What {@see moodleform_mod::validation()} usually returns or an empty array if validation isn't implemented.
     */
    private function validate_cat_model_fields_or_skip(array $data, array $files): array {
        $catmodel = $data['catmodel'];

        $formvalidatorclasses = core_component::get_component_classes_in_namespace(
            "adaptivequizcatmodel_$catmodel",
            'local\catmodel\form'
        );
        if (empty($formvalidatorclasses)) {
            return [];
        }

        $classnames = array_keys($formvalidatorclasses);
        foreach ($classnames as $classname) {
            if (!is_subclass_of($classname, '\mod_adaptivequiz\local\catmodel\form\catmodel_mod_form_validator')) {
                continue;
            }

            $validator = new $classname();

            return $validator->validation_callback($data, $files);
        }

        return [];
    }

    /**
     * Checks whether there are CAT model plugins to choose and if that's the case adds related elements to the form.
     *
     * @param MoodleQuickForm $form
     */
    private function add_cat_model_section_when_applicable(MoodleQuickForm $form): void {
        global $PAGE;

        if (!$catmodelplugins = core_component::get_plugin_list('adaptivequizcatmodel')) {
            return;
        }

        $PAGE->requires->js_call_amd('mod_adaptivequiz/cat_model_chooser', 'init');

        $form->addElement('header', 'catmodelheading', get_string('modformcatmodelheading', 'adaptivequiz'));

        $options = ['' => ''];
        foreach (array_keys($catmodelplugins) as $pluginname) {
            $options[$pluginname] = get_string('pluginname', "adaptivequizcatmodel_$pluginname");
        }
        $config = get_config('adaptivequiz');
        $form->addElement('select', 'catmodel', get_string('modformcatmodel', 'adaptivequiz'), $options,
            ['data-on-change-action' => 'reloadForm']);
        $form->setDefault('catmodel', $config->catmodel ?? 0);
        $form->addHelpButton('catmodel', 'modformcatmodel', 'adaptivequiz');

        if (!empty($config->catmodel_locked)) {
            $form->freeze('catmodel');
        }

        // Just a marker to identify the place in form where custom fields should be added.
        $form->addElement('hidden', 'catmodelfieldsmarker');
        $form->setType('catmodelfieldsmarker', PARAM_INT);
    }
}
