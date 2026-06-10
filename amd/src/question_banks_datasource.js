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
 * Autocomplete data source for question bank selectors.
 *
 * @module     mod_adaptivequiz/question_banks_datasource
 * @copyright  2026 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {call as fetchMany} from 'core/ajax';
import Notification from 'core/notification';

export default {

    /**
     * Source of data for Ajax element.
     *
     * @param {String} selector The selector of the auto complete element.
     * @param {String} query The query string.
     * @param {Function} callback A callback function receiving an array of results.
     */
    transport: function(selector, query, callback) {
        const element = document.querySelector(selector);
        const contextId = element.dataset.contextid;
        const inCourseId = element.dataset.incourseid;
        const notInCourseId = element.dataset.notincourseid;

        if (!contextId) {
            throw new Error('The attribute data-contextid is required on ' + selector);
        }

        fetchMany([{
            methodname: 'mod_adaptivequiz_search_question_banks',
            args: {
                contextid: contextId,
                incourseid: inCourseId,
                notincourseid: notInCourseId,
                search: query,
            },
        }])[0]
            .then(callback)
            .catch(Notification.exception);
    },

    /**
     * Process the results for auto complete elements.
     *
     * @param {String} selector The selector of the auto complete element.
     * @param {Array} results An array or results.
     * @return {Array} New array of results.
     */
    processResults: (selector, results) => {
        return results.questionbanks;
    },
};
