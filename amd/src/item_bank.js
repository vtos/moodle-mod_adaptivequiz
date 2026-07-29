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
    showQuestionCategoriesDialog: '[data-action="show-question-categories-dialog"]',
};

/**
 * Entry point of the module.
 */
export const init = () => {
    // Use event delegation to handle both bank and category dialog buttons
    // This prevents duplicate listeners even if init is called multiple times
    document.addEventListener('click', (e) => {
        const bankButton = e.target.closest(SELECTORS.showQuestionBanksDialog);
        if (bankButton) {
            e.preventDefault();
            handleBankDialogClick(bankButton);
            return;
        }

        const categoryButton = e.target.closest(SELECTORS.showQuestionCategoriesDialog);
        if (categoryButton) {
            e.preventDefault();
            handleCategoryDialogClick(categoryButton);
            return;
        }
    });
};

/**
 * Handle click on bank dialog button.
 *
 * @param {HTMLElement} button The clicked button element.
 */
function handleBankDialogClick(button) {
    const idFormArg = button.dataset.id;
    const courseIdFormArg = button.dataset.courseId;

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
}

/**
 * Handle click on category dialog button.
 *
 * @param {HTMLElement} button The clicked button element.
 */
function handleCategoryDialogClick(button) {
    const idFormArg = button.dataset.id;
    const courseIdFormArg = button.dataset.courseId;

    const form = new ModalForm({
        formClass: "mod_adaptivequiz\\form\\assign_question_category_form",
        args: {
            id: idFormArg,
            course: courseIdFormArg,
        },
        modalConfig: {
            title: getString('itembankassignqcat', 'adaptivequiz'),
        },
        saveButtonText: getString('itembankaddqbankbn', 'adaptivequiz'),
    });

    form.addEventListener(form.events.FORM_SUBMITTED, () => window.location.reload());
    form.show();
}
