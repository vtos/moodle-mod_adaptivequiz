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
 * Module to manage item bank.
 *
 * @module     mod_adaptivequiz/item_bank
 * @copyright  2026 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import {getString} from 'core/str';

/**
 * DOM selectors.
 *
 * @constant
 * @type Object
 */
const SELECTORS = {
    showQuestionBanksDialog: '[data-action="show-question-banks-dialog"]',
};

/**
 * Entry point of the module.
 */
export const init = () => {
    document.querySelector(SELECTORS.showQuestionBanksDialog).addEventListener('click', (e) => {
        const idFormArg = e.target.dataset.id;
        const courseIdFormArg = e.target.dataset.courseId;

        const form = new ModalForm({
            formClass: "mod_adaptivequiz\\form\\assign_question_bank_form",
            args: {
                id: idFormArg,
                course: courseIdFormArg,
            },
            modalConfig: {
                title: getString('itembankeditqbanks', 'adaptivequiz'),
            },
            saveButtonText: getString('itembankaddqbankbn', 'adaptivequiz'),
        });

        form.addEventListener(form.events.FORM_SUBMITTED, () => window.location.reload());
        form.show();

    });
};
