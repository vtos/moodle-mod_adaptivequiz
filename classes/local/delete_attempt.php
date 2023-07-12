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

namespace mod_adaptivequiz\local;

use context_module;
use mod_adaptivequiz\local\attempt\attempt;
use mod_adaptivequiz\local\attempt\cat_model_params;
use question_engine;
use stdClass;

/**
 * High-level API class for attempts deletion.
 *
 * Contains only one public method, which performs security checks, all the extra operations required and can be called from pages,
 * external endpoints, etc.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class delete_attempt {

    /**
     * Deletes an attempt and performs all the extra operations required.
     *
     * @param int $id
     */
    public static function with_id(int $id): void {
        global $DB;

        $attempt = attempt::get_by_id($id);

        $adaptivequiz = $DB->get_record('adaptivequiz', ['id' => $attempt->read_attempt_data()->instance], '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($adaptivequiz, 'adaptivequiz', $adaptivequiz->course);

        $context = context_module::instance($cm->id);

        require_capability('mod/adaptivequiz:viewreport', $context);

        self::delete_catmodel_data($adaptivequiz, $attempt);

        question_engine::delete_questions_usage_by_activity($attempt->read_attempt_data()->uniqueid);

        $attempt->delete();

        adaptivequiz_update_grades($adaptivequiz, $attempt->read_attempt_data()->userid);
    }

    /**
     * Removes CAT model data.
     *
     * In case a custom CAT model is used, wires up its callback. Otherwise, removes the default algorithm's data.
     *
     * @param stdClass $adaptivequiz
     * @param attempt $attempt
     * @return void
     */
    private static function delete_catmodel_data(stdClass $adaptivequiz, attempt $attempt): void {
        if (!$adaptivequiz->catmodel) {
            $catmodelparams = cat_model_params::for_attempt($attempt->read_attempt_data()->id);
            $catmodelparams->delete();

            return;
        }

        $catmodelcomponentname = 'adaptivequizcatmodel_' . $adaptivequiz->catmodel;
        $pluginswithfunction = get_plugin_list_with_function('adaptivequizcatmodel', 'post_delete_attempt_callback');
        if (!array_key_exists($catmodelcomponentname, $pluginswithfunction)) {
            return;
        }

        $functionname = $pluginswithfunction[$catmodelcomponentname];
        $functionname($adaptivequiz, $attempt);
    }
}
