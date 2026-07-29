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
use core\notification;
use core\output\html_writer;
use core_form\dynamic_form;
use mod_adaptivequiz\item_bank;
use moodle_url;

/**
 * Assigns Moodle question banks to an adaptive quiz's item bank.
 *
 * @package    mod_adaptivequiz
 * @copyright  2026 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_question_bank_form extends dynamic_form {
    /**
     *  Implements the abstract method.
     */
    protected function definition() {
        $form = $this->_form;

        $form->addElement('html', html_writer::tag('h5', get_string('itembankthiscourseqbanks', 'adaptivequiz')));

        $name = get_string('itembankselectqbank', 'adaptivequiz');
        $options = [
            'ajax' => 'mod_adaptivequiz/question_banks_datasource',
            'multiple' => true,
            'data-contextid' => $this->get_context_for_dynamic_submission()->id,
            'data-incourseid' => $this->optional_param('course', null, PARAM_INT),
            'id' => 'addqbanksthis',
        ];
        $form->addElement('autocomplete', 'addqbanksthis', $name, [], $options);

        $form->addElement('html', html_writer::tag('h5', get_string('itembankothercoursesqbanks', 'adaptivequiz')));

        $name = get_string('itembankselectqbank', 'adaptivequiz');
        $options = [
            'ajax' => 'mod_adaptivequiz/question_banks_datasource',
            'multiple' => true,
            'data-contextid' => $this->get_context_for_dynamic_submission()->id,
            'data-notincourseid' => $this->optional_param('course', null, PARAM_INT),
            'id' => 'addqbanksother',
        ];
        $form->addElement('autocomplete', 'addqbanksother', $name, [], $options);

        $form->addElement('hidden', 'id');
        $form->setType('id', PARAM_INT);
    }

    /**
     * Implements the abstract method.
     */
    public function process_dynamic_submission(): void {
        global $DB;

        $id = $this->optional_param('id', null, PARAM_INT);

        $cm = get_coursemodule_from_id('adaptivequiz', $id, 0, false, MUST_EXIST);
        $adaptivequiz  = $DB->get_record('adaptivequiz', ['id' => $cm->instance], '*', MUST_EXIST);

        $data = $this->get_data();

        $addqbankidlist = array_merge($data->addqbanksthis, $data->addqbanksother);
        item_bank::assign_qbanks_to_adaptivequiz($adaptivequiz->id, $addqbankidlist);

        notification::success(get_string('itembanknewassignflash', 'adaptivequiz'));
    }

    /**
     * Implements the abstract method.
     */
    public function set_data_for_dynamic_submission(): void {
        $this->set_data((object) ['id' => $this->optional_param('id', null, PARAM_INT)]);
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
        require_capability('mod/adaptivequiz:manage', $this->get_context_for_dynamic_submission());
    }

    /**
     * Implements the abstract method.
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $id = $this->optional_param('id', null, PARAM_INT);

        return new moodle_url('/mod/adaptivequiz/itembank.php', ['id' => $id]);
    }
}
