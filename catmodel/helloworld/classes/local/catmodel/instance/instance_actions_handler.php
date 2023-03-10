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

namespace adaptivequizcatmodel_helloworld\local\catmodel\instance;

use mod_adaptivequiz\local\catmodel\instance\catmodel_add_instance_handler;
use mod_adaptivequiz\local\catmodel\instance\catmodel_delete_instance_handler;
use mod_adaptivequiz\local\catmodel\instance\catmodel_update_instance_handler;
use mod_adaptivequiz_mod_form;
use stdClass;

/**
 * Contains implementations of interfaces to be picked up when creating/updating/deleting an adaptive quiz instance.
 *
 * @package    adaptivequizcatmodel_helloworld
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instance_actions_handler implements
    catmodel_add_instance_handler, catmodel_delete_instance_handler, catmodel_update_instance_handler {

    /**
     * Implementation of {@see catmodel_add_instance_handler::add_instance_callback()}.
     *
     * Creates a record for the custom CAT model using the mod_form data.
     *
     * @param stdClass $adaptivequiz
     * @param mod_adaptivequiz_mod_form|null $form
     */
    public function add_instance_callback(stdClass $adaptivequiz, ?mod_adaptivequiz_mod_form $form = null): void {
        global $DB;

        $catmodel = new stdClass();
        $catmodel->adaptivequizid = $adaptivequiz->id;
        $catmodel->param1 = $adaptivequiz->param1;
        $catmodel->param2 = $adaptivequiz->param2;

        $DB->insert_record('catmodel_helloworld', $catmodel);
    }

    /**
     * Implementation of {@see catmodel_update_instance_handler::update_instance_callback()}.
     *
     * Updates a record for the custom CAT model using the mod_form data.
     *
     * @param stdClass $adaptivequiz
     * @param mod_adaptivequiz_mod_form|null $form
     */
    public function update_instance_callback(stdClass $adaptivequiz, ?mod_adaptivequiz_mod_form $form = null): void {
        global $DB;

        $catmodelrecord = new stdClass();
        $catmodelrecord->id = $adaptivequiz->catmodelinstanceid;
        $catmodelrecord->param1 = $adaptivequiz->param1;
        $catmodelrecord->param2 = $adaptivequiz->param2;

        $DB->update_record('catmodel_helloworld', $catmodelrecord);
    }

    /**
     * Implementation of {@see catmodel_delete_instance_handler::delete_instance_callback()}.
     *
     * Cleans up what was created for the custom CAT model.
     *
     * @param stdClass $adaptivequiz
     */
    public function delete_instance_callback(stdClass $adaptivequiz): void {
        global $DB;

        $DB->delete_records('catmodel_helloworld', ['adaptivequizid' => $adaptivequiz->id]);
    }
}
