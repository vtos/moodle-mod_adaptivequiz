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
 * External function and service definitions.
 *
 * @package   mod_adaptivequiz
 * @copyright 2026 Vitaly Potenko <potenkov@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_adaptivequiz_search_question_banks' => [
        'classname' => '\mod_adaptivequiz\external\search_question_banks',
        'description' => 'Get a list of filtered question banks.',
        'type' => 'read',
        'ajax' => true,
    ],
    'mod_adaptivequiz_search_question_categories' => [
        'classname' => '\mod_adaptivequiz\external\search_question_categories',
        'description' => 'Get a list of question categories for a given question bank.',
        'type' => 'read',
        'ajax' => true,
    ],
];
