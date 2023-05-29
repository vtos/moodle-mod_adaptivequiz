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
 * @package    adaptivequizcatmodel_helloworld
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_adaptivequiz\local\attempt\attempt;

/**
 * Callback to execute when a fresh attempt on adaptive quiz has been created.
 *
 * @param stdClass $adaptivequiz
 * @param attempt $attempt
 */
function adaptivequizcatmodel_helloworld_post_create_attempt_callback(stdClass $adaptivequiz, attempt $attempt): void {
    global $DB;

    // Set some parameters in the database.
    $record = new stdClass();
    $record->adaptivequizattempt = $attempt->read_attempt_data()->id;
    $record->stateparam1 = 5.6;
    $record->stateparam2 = 124.54;
    $DB->insert_record('catmodel_helloworld_state', $record);
}
