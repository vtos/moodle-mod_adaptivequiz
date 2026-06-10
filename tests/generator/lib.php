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

use core\exception\coding_exception;
use mod_adaptivequiz\attempt as read_attempt;
use mod_adaptivequiz\item_bank;
use mod_adaptivequiz\local\attempt as attempt_entity;

/**
 * Generator for the module.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2024 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_adaptivequiz_generator extends testing_module_generator {

    /**
     * Creates new module instance.
     *
     * For params description see the parent's method.
     * IMPORTANT: do not throw any exceptions on missing data, as this method may be called by Moodle's core tests. Try to
     * find a proper default value for each property.
     *
     * @param array|stdClass|null $record
     * @param array|null $options
     * @return stdClass
     */
    public function create_instance($record = null, array $options = null) {
        global $CFG;

        require_once($CFG->dirroot .'/mod/adaptivequiz/locallib.php');

        $record = (object)(array)$record;

        $defaultsettings = [
            'introformat' => FORMAT_MOODLE,
            'attempts' => 0,
            'grademethod' => ADAPTIVEQUIZ_GRADEHIGHEST,
            'password' => '',
            'attemptfeedbackenable' => 0,
            'attemptfeedback' => '',
            'attemptfeedbackformat' => FORMAT_MOODLE,
            'showabilitymeasurefeedback' => 0,
            'showabilitymeasuresummary' => 0,
            'attemptonlast' => 0,
            'highestlevel' => 111,
            'lowestlevel' => 1,
            'minimumquestions' => 1,
            'maximumquestions' => 111,
            'standarderror' => 1.1,
            'startinglevel' => 11,
            'timecreated' => time(),
            'timemodified' => time(),
            'debuginfoenable' => 0,
        ];

        foreach ($defaultsettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        return parent::create_instance($record, $options);
    }

    /**
     * Generates links between the given adaptive quiz instance and question banks.
     *
     * Note, the question banks data passed is not validated.
     *
     * @param array $data 'adaptivequizid' and 'qbankid' are the required keys.
     */
    public function create_link_with_question_bank(array $data): void {
        if (!array_key_exists('adaptivequizid', $data)) {
            throw new coding_exception("Id of an 'adaptivequiz' instance is required when linking qbanks");
        }

        if (!array_key_exists('qbankid', $data)) {
            throw new coding_exception("Id of a 'qbank' instance is required when linking qbanks");
        }

        item_bank::assign_qbanks_to_adaptivequiz($data['adaptivequizid'], [$data['qbankid']]);
    }

    /**
     * Generates links between the given adaptive quiz instance and a single question category.
     *
     * Note, the passed data is not validated.
     *
     * @param array $data 'adaptivequizid' and 'qcategoryid' are the required keys.
     */
    public function create_link_with_question_category(array $data): void {
        global $DB;

        if (!array_key_exists('adaptivequizid', $data)) {
            throw new coding_exception("Id of an 'adaptivequiz' instance is required when linking question categories");
        }

        if (!array_key_exists('qcategoryid', $data)) {
            throw new coding_exception("Id of a question category is required when linking question categories");
        }

        $DB->insert_record('adaptivequiz_question', [
            'instance' => $data['adaptivequizid'],
            'questioncategory' => $data['qcategoryid'],
        ]);
    }

    /**
     * Generates a 'fresh' attempt for user.
     *
     * Note, that this runs the entire logic of starting an attempt, etc. This means it's assumed that the adaptive quiz instance
     * is set up with a proper questions pool containing a minimal number of actual questions, etc.
     *
     * @param int $userid
     * @param int $adaptivequizid
     * @param context_module $context
     */
    public function create_in_progress_attempt(int $userid, int $adaptivequizid, context_module $context): read_attempt {
        global $DB;

        $adaptivequiz = $DB->get_record('adaptivequiz', ['id' => $adaptivequizid], '*', MUST_EXIST);

        $adaptivequizforattempt = clone($adaptivequiz);
        $adaptivequizforattempt->context = $context;

        $attempt = new attempt_entity($adaptivequizforattempt, $userid);
        $attempt->set_level($adaptivequiz->startinglevel);
        $attempt->start_attempt();

        $uniqueid = $attempt->get_quba()->get_id();

        return read_attempt::get_record(['uniqueid' => $uniqueid], MUST_EXIST);
    }

    /**
     * Generates a completed attempt for user.
     *
     * The note from {@see self::create_in_progress_attempt()} is applicable here as well.
     *
     * @param int $userid
     * @param int $adaptivequizid
     * @param context_module $context
     * @param array $attemptdata Raw values for the following fields can be passed: 'standarderror', 'measure'.
     * @param string $stoppagereason
     */
    public function create_completed_attempt(
        int $userid,
        int $adaptivequizid,
        context_module $context,
        array $attemptdata,
        string $stoppagereason
    ): read_attempt {
        global $DB;

        $adaptivequiz = $DB->get_record('adaptivequiz', ['id' => $adaptivequizid], '*', MUST_EXIST);

        $adaptivequizforattempt = clone($adaptivequiz);
        $adaptivequizforattempt->context = $context;

        $attempt = new attempt_entity($adaptivequizforattempt, $userid);
        $attempt->set_level($adaptivequiz->startinglevel);
        $attempt->start_attempt();

        $uniqueid = $attempt->get_quba()->get_id();

        $standarderror = array_key_exists('standarderror', $attemptdata) ? $attemptdata['standarderror'] : 0.0;

        if (array_key_exists('measure', $attemptdata)) {
            adaptivequiz_update_attempt_data($uniqueid, $adaptivequizid, $userid, $difflogit = 0, $standarderror,
                $attemptdata['measure']);
        }

        adaptivequiz_complete_attempt(uniqueid: $uniqueid, adaptivequiz: $adaptivequiz, context: $context, userid: $userid,
            standarderror: $standarderror, statusmessage: $stoppagereason);

        return read_attempt::get_record(['uniqueid' => $uniqueid], MUST_EXIST);
    }
}
