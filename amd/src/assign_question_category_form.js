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
 * Form module to handle cascading bank and category selectors.
 *
 * @module     mod_adaptivequiz/assign_question_category_form
 * @copyright  2026 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {call as fetchMany} from 'core/ajax';
import Notification from 'core/notification';

/**
 * DOM selectors.
 *
 * @constant
 * @type Object
 */
const SELECTORS = {
    bankSelector: '#selectbank',
    categorySelector: '#selectcategories',
    loadingSpinner: '[data-role="loading-spinner"]',
};

/**
 * Initialize the form handler.
 *
 * @param {int} contextid The context ID of the adaptive quiz module.
 */
export const init = (contextid) => {
    const bankSelector = document.querySelector(SELECTORS.bankSelector);
    const categorySelector = document.querySelector(SELECTORS.categorySelector);

    if (!bankSelector || !categorySelector) {
        return;
    }

    // Initially disable category selector.
    categorySelector.disabled = true;

    // Listen for bank selection changes.
    bankSelector.addEventListener('change', function() {
        const selectedBank = this.value;

        // Clear existing categories.
        categorySelector.innerHTML = '';
        categorySelector.disabled = true;

        if (!selectedBank) {
            return;
        }

        // Extract bank ID from the value (format: "bankid" or similar).
        loadCategoriesForBank(parseInt(selectedBank), contextid, categorySelector);
    });
};

/**
 * Load categories for the selected bank via AJAX.
 *
 * @param {int} bankid The ID of the selected question bank.
 * @param {int} contextid The context ID of the adaptive quiz module.
 * @param {HTMLElement} categorySelector The select element to populate.
 */
function loadCategoriesForBank(bankid, contextid, categorySelector) {
    // Show loading state.
    categorySelector.disabled = true;
    categorySelector.innerHTML = '<option selected disabled>Loading...</option>';

    fetchMany([{
        methodname: 'mod_adaptivequiz_search_question_categories',
        args: {
            bankid: bankid,
            contextid: contextid,
        },
    }])[0]
        .then((result) => {
            if (!result || !result.categories) {
                throw new Error('Invalid response from web service');
            }

            const categories = result.categories;

            // Clear loading message.
            categorySelector.innerHTML = '';

            if (categories.length === 0) {
                const option = document.createElement('option');
                option.textContent = 'No categories available';
                option.disabled = true;
                categorySelector.appendChild(option);
                categorySelector.disabled = true;
                return;
            }

            // Populate category selector.
            categories.forEach((category) => {
                const option = document.createElement('option');
                option.value = category.value;
                option.textContent = category.label;
                categorySelector.appendChild(option);
            });

            // Enable the selector.
            categorySelector.disabled = false;
        })
        .catch((error) => {
            categorySelector.innerHTML = '';
            const option = document.createElement('option');
            option.textContent = 'Error loading categories';
            option.disabled = true;
            categorySelector.appendChild(option);
            Notification.exception(error);
        });
}
