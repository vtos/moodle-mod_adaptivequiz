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
 * Definition of plugin's system functions.
 *
 * @package    adaptivequizcatmodel_catquiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_catquiz\catquiz_handler;
use mod_adaptivequiz\local\attempt\attempt;
use mod_adaptivequiz\local\attempt\cat_model_params;

/**
 * Callback to execute when a fresh attempt on adaptive quiz has been created.
 *
 * Picked up by mod_adaptivequiz component only.
 *
 * @param stdClass $adaptivequiz
 * @param attempt $attempt
 */
function adaptivequizcatmodel_catquiz_post_create_attempt_callback(stdClass $adaptivequiz, attempt $attempt): void {
        cat_model_params::create_new_for_attempt($attempt->read_attempt_data()->id);
        catquiz_handler::purge_attemptquestions_cache(intval($adaptivequiz->id), 'mod_adaptivequiz');
}

/**
 * Callback to execute when a question answer is processed.
 *
 * Picked up by mod_adaptivequiz component only.
 *
 * @param stdClass $adaptivequiz
 * @param attempt $attempt
 */
function adaptivequizcatmodel_catquiz_post_process_item_result_callback(stdClass $adaptivequiz, attempt $attempt): void {
}
