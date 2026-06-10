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
 * Behat data generator.
 *
 * @package    mod_adaptivequiz
 * @copyright  2026 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_adaptivequiz_generator extends behat_generator_base {

    /**
     * Get a list of the entities that can be created for this component.
     */
    protected function get_creatable_entities(): array {
        return [
            'links with question banks' => [
                'singular' => 'link with question bank',
                'datagenerator' => 'link_with_question_bank',
                'required' => ['adaptivequiz', 'idnumber'],
                'switchids' => ['adaptivequiz' => 'adaptivequizid'],
            ],
        ];
    }

    /**
     * Looks up the ID of an adaptive quiz from its name.
     *
     * @param string $name
     * @return int The ID.
     */
    protected function get_adaptivequiz_id(string $name): int {
        global $DB;

        return $DB->get_field('adaptivequiz', 'id', ['name' => $name], MUST_EXIST);
    }

    /**
     * Preprocess discussion data.
     *
     * @param array $data Raw data.
     * @return array Processed data.
     */
    protected function preprocess_link_with_question_bank(array $data) {
        $cminfo = $this->get_cm_by_activity_name('qbank', $data['idnumber']);

        unset($data['idnumber']);
        $data['qbankid'] = $cminfo->instance;

        return $data;
    }
}
