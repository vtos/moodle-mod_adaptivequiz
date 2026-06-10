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
 * Definition of activity settings form.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/adaptivequiz/locallib.php');

use mod_adaptivequiz\attempt_feedback_placeholders_helper;
use mod_adaptivequiz\output\editor_placeholders;

/**
 * Module instance settings form
 */
class mod_adaptivequiz_mod_form extends moodleform_mod {

    /**
     * Form definition.
     */
    public function definition() {
        global $OUTPUT;

        $mform = $this->_form;

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

        $mform->addElement('select', 'showattemptprogress', get_string('modformshowattemptprogress', 'adaptivequiz'),
            [get_string('no'), get_string('yes')]);
        $mform->addHelpButton('showattemptprogress', 'modformshowattemptprogress', 'adaptivequiz');
        $mform->setDefault('showattemptprogress', 0);

        $mform->addElement('select', 'showabilitymeasuresummary', get_string('showabilitymeasuresummary', 'adaptivequiz'),
            [get_string('no'), get_string('yes')]);
        $mform->addHelpButton('showabilitymeasuresummary', 'showabilitymeasuresummary', 'adaptivequiz');
        $mform->setDefault('showabilitymeasuresummary', 0);

        $mform->addElement('header', 'attemptfeedbackhdr', get_string('attemptfeedbackhdr', 'adaptivequiz'));

        $isnewinstance = !$this->current->instance;
        if (!$isnewinstance) {
            $customfeedbackenabled = $this->current->attemptfeedbackenable;
            if ($customfeedbackenabled == 1 || $customfeedbackenabled == -1) {
                $mform->setExpanded('attemptfeedbackhdr');
            }
        }

        $mform->addElement('advcheckbox', 'attemptfeedbackenable', get_string('attemptfeedbackenable', 'adaptivequiz'));

        $mform->addElement('editor', 'attemptfeedbackeditor', get_string('attemptfeedback', 'adaptivequiz'),
            ['rows' => 10],
            ['maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $this->context, 'subdirs' => true]);
        $mform->setType('attemptfeedbackeditor', PARAM_RAW);
        $mform->addHelpButton('attemptfeedbackeditor', 'attemptfeedback', 'adaptivequiz');
        $mform->disabledIf('attemptfeedbackeditor', 'attemptfeedbackenable', 'notchecked');

        $feedbackplaceholders = new editor_placeholders(attempt_feedback_placeholders_helper::configured()->placeholder_options());
        $feedbackplaceholderscontent = $OUTPUT->render_from_template('mod_adaptivequiz/editor_placeholders_desc',
            $feedbackplaceholders->export_for_template($OUTPUT));
        $mform->addElement('static', 'attemptfeedbackplaceholdersdesc', '', $feedbackplaceholderscontent);
        $mform->addHelpButton('attemptfeedbackplaceholdersdesc', 'attemptfeedbackplaceholdersdesc', 'adaptivequiz');

        $mform->addElement('select', 'showabilitymeasurefeedback', get_string('showabilitymeasurefeedback', 'adaptivequiz'),
            [get_string('no'), get_string('yes')]);
        $mform->addHelpButton('showabilitymeasurefeedback', 'showabilitymeasurefeedback', 'adaptivequiz');
        $mform->setDefault('showabilitymeasurefeedback', 0);

        $mform->addElement('header', 'advancedhdr', get_string('advanced'));
        $mform->addElement('advcheckbox', 'debuginfoenable', get_string('debuginfoenable', 'adaptivequiz'));
        $mform->addHelpButton('debuginfoenable', 'debuginfoenable', 'adaptivequiz');

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
     * Custom completion rules support.
     */
    public function add_completion_rules(): array {
        $form = $this->_form;
        $form->addElement('checkbox', 'completionattemptcompleted', ' ',
            get_string('completionattemptcompletedform', 'adaptivequiz'));

        return ['completionattemptcompleted'];
    }

    /**
     * Custom completion rules support.
     */
    public function completion_rule_enabled($data): bool {
        if (!isset($data['completionattemptcompleted'])) {
            return false;
        }

        return $data['completionattemptcompleted'] != 0;
    }

    /**
     * Overrides the parent's method.
     *
     * @param array $defaultvalues Passed by reference, the parameter's original name is changed to meet the code style.
     */
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);

        $isnewinstance = !$this->current->instance;
        if ($isnewinstance) {
            return;
        }

        // Whether the instance is in 'transition state' to start using the editor-powered custom feedback.
        $newcustomfeedbackpending = $this->current->attemptfeedbackenable == -1;
        if ($newcustomfeedbackpending) {
            $legacycustomfeedbackenabled = !empty($this->current->attemptfeedback);
            if ($legacycustomfeedbackenabled) {
                $defaultvalues['attemptfeedbackenable'] = 1;
                $defaultvalues['attemptfeedbackeditor'] = [
                    'text' => $defaultvalues['attemptfeedback'],
                    'format' => FORMAT_HTML,
                ];

                return;
            }

            $defaultvalues['attemptfeedbackenable'] = 0;
            $defaultvalues['attemptfeedbackeditor'] = [
                'text' => '',
                'format' => FORMAT_HTML,
            ];

            return;
        }

        $feedbackdraftitemid = file_get_submitted_draft_itemid('attemptfeedback');
        if (!empty($defaultvalues['attemptfeedback'])) {
            $defaultvalues['attemptfeedbackeditor'] = [
                'text' => file_prepare_draft_area($feedbackdraftitemid, $this->context->id, 'mod_adaptivequiz', 'attemptfeedback',
                    0, ['subdirs' => 0], $defaultvalues['attemptfeedback']),
                'itemid' => $feedbackdraftitemid,
                'format' => $defaultvalues['attemptfeedbackformat'],
            ];
        }
    }
}
