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
 * Assigns question categories from question banks to an adaptive quiz's item bank.
 *
 * @package    mod_adaptivequiz
 * @copyright  2026 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_question_category_form extends dynamic_form {
    /**
     * Implements the abstract method. Defines the form fields for bank and category selection.
     */
    protected function definition(): void {
        $form = $this->_form;

        $contextid = $this->get_context_for_dynamic_submission()->id;

        // Bank selector field.
        $bankname = get_string('selectquestionbank', 'adaptivequiz');
        $bankoptions = [
            'ajax' => 'mod_adaptivequiz/question_banks_datasource',
            'multiple' => false,
            'data-contextid' => $contextid,
            'data-incourseid' => $this->optional_param('course', null, PARAM_INT),
            'id' => 'selectbank',
            'placeholder' => get_string('selectquestionbankplaceholder', 'adaptivequiz'),
        ];
        $form->addElement('autocomplete', 'bankid', $bankname, [], $bankoptions);
        $form->addRule('bankid', get_string('required'), 'required', null, 'client');

        // Category selector field (multi-select autocomplete).
        $categoryname = get_string('selectcategories', 'adaptivequiz');
        $categoryoptions = [
            'ajax' => 'mod_adaptivequiz/question_categories_datasource',
            'multiple' => true,
            'data-contextid' => $contextid,
            'id' => 'selectcategories',
            'placeholder' => get_string('selectcategoriesplaceholder', 'adaptivequiz'),
        ];
        $form->addElement('autocomplete', 'categoryids', $categoryname, [], $categoryoptions);
        $form->setType('categoryids', PARAM_NOTAGS);
        $form->addRule('categoryids', get_string('required'), 'required', null, 'client');

        // Hidden field for module context ID.
        $form->addElement('hidden', 'id');
        $form->setType('id', PARAM_INT);
    }

    /**
     * Implements the abstract method. Processes the form submission and assigns categories.
     */
    public function process_dynamic_submission(): void {
        global $DB;

        $id = $this->optional_param('id', null, PARAM_INT);

        $cm = get_coursemodule_from_id('adaptivequiz', $id, 0, false, MUST_EXIST);
        $adaptivequiz = $DB->get_record('adaptivequiz', ['id' => $cm->instance], '*', MUST_EXIST);

        $data = $this->get_data();

        // Debug: Log what we're receiving.
        $categoryidlist = $data->categoryids ?? null;

        // Ensure we have an array.
        if (!is_array($categoryidlist)) {
            if (is_string($categoryidlist) && !empty($categoryidlist)) {
                $categoryidlist = [$categoryidlist];
            } else {
                $categoryidlist = [];
            }
        }

        // Assign the selected categories to the adaptive quiz.
        if (!empty($categoryidlist)) {
            item_bank::assign_question_categories_to_adaptivequiz($adaptivequiz->id, $categoryidlist);
        }

        notification::success(get_string('categoriesassignedflash', 'adaptivequiz'));
    }

    /**
     * Implements the abstract method. Sets initial data for the form.
     */
    public function set_data_for_dynamic_submission(): void {
        $this->set_data((object) ['id' => $this->optional_param('id', null, PARAM_INT)]);
    }

    /**
     * Implements the abstract method. Returns the context for dynamic submission.
     *
     * @return context The context for dynamic submission.
     */
    protected function get_context_for_dynamic_submission(): context {
        $id = $this->optional_param('id', null, PARAM_INT);
        $cm = get_coursemodule_from_id('adaptivequiz', $id, 0, false, MUST_EXIST);
        /** @var context $context */
        $context = context_module::instance($cm->id);

        return $context;
    }

    /**
     * Implements the abstract method. Validates access for dynamic submission.
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability('mod/adaptivequiz:manage', $this->get_context_for_dynamic_submission());
    }

    /**
     * Implements the abstract method. Returns the page URL for dynamic submission.
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $id = $this->optional_param('id', null, PARAM_INT);

        return new moodle_url('/mod/adaptivequiz/itembank.php', ['id' => $id]);
    }
}
