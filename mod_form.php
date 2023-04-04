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

        $this->questions_pool_selector($mform);

        $mform->addElement('header', 'algorithmsettingsheading', get_string('mod_form:algorithmsettingsheading', 'adaptivequiz'));
        $mform->setExpanded('algorithmsettingsheading', true);

        $this->custom_cat_model_selector_if_applicable($mform);

        $this->default_cat_algorithm_fields_section($mform, $pluginconfig);

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
            $defaultvalues = $formdatapreprocessor->data_preprocessing_callback($defaultvalues);

            break;
        }
    }

    /**
     * A system method required for the completion API.
     *
     * @return string[] List of added elements.
     * @throws coding_exception
     */
    public function add_completion_rules(): array {
        $form = $this->_form;
        $form->addElement('checkbox', 'completionattemptcompleted', ' ',
            get_string('completionattemptcompletedform', 'adaptivequiz'));

        return ['completionattemptcompleted'];
    }

    /**
     * A system method required for the completion API.
     *
     * @param array $data Input data not yet validated.
     */
    public function completion_rule_enabled($data): bool {
        if (!isset($data['completionattemptcompleted'])) {
            return false;
        }

        return $data['completionattemptcompleted'] != 0;
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

        if (empty($data['questionpool'])) {
            $errors['questionpool'] = get_string('formquestionpool', 'adaptivequiz');
        }

        // When there's a custom CAT model submitted, we wire up its form validation if exists and skip the default validation.
        if (!empty($data['catmodel'])) {
            return array_merge($errors, $this->validate_custom_cat_model_fields_or_skip($data, $files));
        }

        $errors = array_merge($errors, $this->validate_cat_algorithm_fields($data));
        if ($errors) {
            return $errors;
        }

        if ($questionspoolerrormsg = $this->validate_questions_pool($data['questionpool'], $data['startinglevel'])) {
            $errors['questionpool'] = $questionspoolerrormsg;
        }

        return $errors;
    }

    /**
     * Initializes questions pool selector.
     *
     * @param MoodleQuickForm $form
     */
    private function questions_pool_selector(MoodleQuickForm $form): void {
        adaptivequiz_make_default_categories($this->context);

        $options = adaptivequiz_get_question_categories($this->context);
        $select = $form->addElement('select', 'questionpool', get_string('questionpool', 'adaptivequiz'), $options);
        $select->setMultiple(true);

        $selquestcat = adaptivequiz_get_selected_question_cateogires($this->_instance);
        $form->getElement('questionpool')->setSelected($selquestcat);

        $form->addHelpButton('questionpool', 'questionpool', 'adaptivequiz');
        $form->addRule('questionpool', get_string('err_required', 'form'), 'required', null, 'client');
    }

    /**
     * Checks whether there are CAT model plugins to choose and if that's the case adds related elements to the form.
     *
     * @param MoodleQuickForm $form
     */
    private function custom_cat_model_selector_if_applicable(MoodleQuickForm $form): void {
        if (!$catmodelplugins = core_component::get_plugin_list('adaptivequizcatmodel')) {
            return;
        }

        $options = ['' => ''];
        foreach (array_keys($catmodelplugins) as $pluginname) {
            $options[$pluginname] = get_string('pluginname', "adaptivequizcatmodel_$pluginname");
        }
        $form->addElement('select', 'catmodel', get_string('modformcatmodel', 'adaptivequiz'), $options);
        $form->addHelpButton('catmodel', 'modformcatmodel', 'adaptivequiz');
    }

    /**
     * Definition of fields related to the default CAT algorithm.
     *
     * Each field is set to have a hide-if dependency on the custom CAT model selector.
     * The method also calls the form definition callback for a CAT model plugin if selected.
     *
     * @param MoodleQuickForm $form
     * @param stdClass $config Plugin's global config.
     */
    private function default_cat_algorithm_fields_section(MoodleQuickForm $form, stdClass $config): void {
        $form->addElement('text', 'startinglevel', get_string('startinglevel', 'adaptivequiz'),
            ['size' => '3', 'maxlength' => '3']);
        $form->addHelpButton('startinglevel', 'startinglevel', 'adaptivequiz');
        $form->setType('startinglevel', PARAM_INT);
        $form->setDefault('startinglevel', $config->startinglevel);
        $form->hideIf('startinglevel', 'catmodel', 'neq', '');

        $form->addElement('text', 'lowestlevel', get_string('lowestlevel', 'adaptivequiz'),
            ['size' => '3', 'maxlength' => '3']);
        $form->addHelpButton('lowestlevel', 'lowestlevel', 'adaptivequiz');
        $form->setType('lowestlevel', PARAM_INT);
        $form->setDefault('lowestlevel', $config->lowestlevel);
        $form->hideIf('lowestlevel', 'catmodel', 'neq', '');

        $form->addElement('text', 'highestlevel', get_string('highestlevel', 'adaptivequiz'),
            ['size' => '3', 'maxlength' => '3']);
        $form->addHelpButton('highestlevel', 'highestlevel', 'adaptivequiz');
        $form->setType('highestlevel', PARAM_INT);
        $form->setDefault('highestlevel', $config->highestlevel);
        $form->hideIf('highestlevel', 'catmodel', 'neq', '');

        $form->addElement('text', 'minimumquestions', get_string('minimumquestions', 'adaptivequiz'),
            ['size' => '3', 'maxlength' => '3']);
        $form->addHelpButton('minimumquestions', 'minimumquestions', 'adaptivequiz');
        $form->setType('minimumquestions', PARAM_INT);
        $form->setDefault('minimumquestions', $config->minimumquestions);
        $form->hideIf('minimumquestions', 'catmodel', 'neq', '');

        $form->addElement('text', 'maximumquestions', get_string('maximumquestions', 'adaptivequiz'),
            ['size' => '3', 'maxlength' => '3']);
        $form->addHelpButton('maximumquestions', 'maximumquestions', 'adaptivequiz');
        $form->setType('maximumquestions', PARAM_INT);
        $form->setDefault('maximumquestions', $config->maximumquestions);
        $form->hideIf('maximumquestions', 'catmodel', 'neq', '');

        $form->addElement('text', 'standarderror', get_string('standarderror', 'adaptivequiz'),
            ['size' => '10', 'maxlength' => '10']);
        $form->addHelpButton('standarderror', 'standarderror', 'adaptivequiz');
        $form->setType('standarderror', PARAM_FLOAT);
        $form->setDefault('standarderror', $config->standarderror);
        $form->hideIf('standarderror', 'catmodel', 'neq', '');

        $this->custom_cat_model_definitions($form);
    }

    /**
     * Searches for implementations of definition callbacks for all CAT model plugins and wires them up.
     *
     * @param MoodleQuickForm $form
     */
    private function custom_cat_model_definitions(MoodleQuickForm $form): void {
        if (!$catmodelplugins = core_component::get_plugin_list('adaptivequizcatmodel')) {
            return;
        }

        foreach (array_keys($catmodelplugins) as $pluginshortname) {
            $formmodifierclasses = core_component::get_component_classes_in_namespace(
                "adaptivequizcatmodel_$pluginshortname",
                'local\catmodel\form'
            );
            if (empty($formmodifierclasses)) {
                continue;
            }

            $classnames = array_keys($formmodifierclasses);
            foreach ($classnames as $classname) {
                if (!is_subclass_of($classname, '\mod_adaptivequiz\local\catmodel\form\catmodel_mod_form_modifier')) {
                    continue;
                }

                $formmodifier = new $classname();
                $formmodifier->definition_callback($form);

                break;
            }
        }
    }

    /**
     * Validates selected questions pool.
     *
     * @param int[] $qcategoryidlist A list of id of selected questions categories.
     * @param int $startinglevel
     * @return string An error message if any.
     */
    private function validate_questions_pool(array $qcategoryidlist, int $startinglevel): string {
        return questions_repository::count_adaptive_questions_in_pool_with_level($qcategoryidlist, $startinglevel) > 0
            ? ''
            : get_string('questionspoolerrornovalidstartingquestions', 'adaptivequiz');
    }

    /**
     * Validates fields related to the CAT algorithm.
     *
     * @param array $data
     * @return array
     */
    private function validate_cat_algorithm_fields(array $data): array {
        $errors = [];

        $startinglevel = (int) $data['startinglevel'];
        if (0 >= $startinglevel) {
            $errors['startinglevel'] = get_string('modform:entervalidcatinteger', 'adaptivequiz');
        }

        $lowestlevel = (int) $data['lowestlevel'];
        if (0 >= $lowestlevel) {
            $errors['lowestlevel'] = get_string('modform:entervalidcatinteger', 'adaptivequiz');
        }

        $highestlevel = $data['highestlevel'];
        if (0 >= $highestlevel) {
            $errors['highestlevel'] = get_string('modform:entervalidcatinteger', 'adaptivequiz');
        }

        $minimumquestions = (int) $data['minimumquestions'];
        if (0 >= $minimumquestions) {
            $errors['minimumquestions'] = get_string('modform:entervalidcatinteger', 'adaptivequiz');
        }

        $maximumquestions = (int) $data['maximumquestions'];
        if (0 >= $maximumquestions) {
            $errors['maximumquestions'] = get_string('modform:entervalidcatinteger', 'adaptivequiz');
        }

        $standarderror = (float) $data['standarderror'];
        if (0.0 > $standarderror || 50.0 <= $standarderror) {
            $errors['standarderror'] = get_string('formstderror', 'adaptivequiz');
        }

        if (empty($errors['lowestlevel']) ) {
            if ($lowestlevel >= $highestlevel) {
                $errors['lowestlevel'] = get_string('formlowlevelgreaterthan', 'adaptivequiz');
            }
        }

        if (empty($errors['startinglevel']) ) {
            if (!($startinglevel >= $lowestlevel && $startinglevel <= $highestlevel)) {
                $errors['startinglevel'] = get_string('formstartleveloutofbounds', 'adaptivequiz');
            }
        }

        if (empty($errors['minimumquestions'])) {
            if ($minimumquestions >= $maximumquestions) {
                $errors['minimumquestions'] = get_string('formminquestgreaterthan', 'adaptivequiz');
            }
        }

        return $errors;
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
    private function validate_custom_cat_model_fields_or_skip(array $data, array $files): array {
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
}
