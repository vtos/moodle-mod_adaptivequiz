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

namespace mod_adaptivequiz\local\attempt;

use coding_exception;
use context_module;
use mod_adaptivequiz\event\attempt_completed;
use question_usage_by_activity;
use stdClass;

/**
 * This class contains information about the attempt parameters
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt {

    /**
     * Database table to store attempt state.
     */
    private const TABLE = 'adaptivequiz_attempt';

    /**
     * The behaviour to use by default.
     */
    const ATTEMPTBEHAVIOUR = 'deferredfeedback';

    /**
     * Flag to denote developer debugging is enabled and this class should write message to the debug
     * wrap on multiple lines
     * @var bool
     */
    protected $debugenabled = false;

    /** @var array $debug debugging array of messages */
    protected $debug = array();

    /** @var stdClass $adpqattempt object, properties come from the adaptivequiz_attempt table */
    protected $adpqattempt;

    /** @var int $userid user id */
    protected $userid;

    /** @var array $tags an array of tags that used to identify eligible questions for the attempt */
    protected $tags = array();

    /**
     * @var stdClass $adaptivequiz Record from the {adaptivequiz} table.
     */
    private $adaptivequiz;

    /**
     * The constructor.
     *
     * @param stdClass $adaptivequiz A record from the {adaptivequiz} table.
     * @param int $userid
     * @param string[] $tags An array of acceptable tags.
     */
    private function __construct(stdClass $adaptivequiz, int $userid, array $tags = array()) {
        $this->adaptivequiz = $adaptivequiz;
        $this->userid = $userid;
        $this->tags = $tags;
        $this->tags[] = ADAPTIVEQUIZ_QUESTION_TAG;

        if (debugging('', DEBUG_DEVELOPER)) {
            $this->debugenabled = true;
        }
    }

    /**
     * This function returns the debug array
     * @return array array of debugging messages
     */
    public function get_debug() {
        return $this->debug;
    }

    /**
     * This function determins whether the user answered the question correctly or incorrectly.
     * If the answer is partially correct it is seen as correct.
     * @param question_usage_by_activity $quba an object loaded with the unique id of the attempt
     * @param int $slotid the slot id of the question
     * @return float a float representing the user's mark.  Or null if there was no mark
     */
    public function get_question_mark($quba, $slotid) {
        $mark = $quba->get_question_mark($slotid);

        if (is_float($mark)) {
            return $mark;
        }

        $this->print_debug('get_question_mark() - Question mark was not a float slot id: '.$slotid.'.  Returning zero');

        return 0;
    }

    /**
     * Sets the appropriate state for the attempt when a question has been answered.
     *
     * @param int $time Current timestamp.
     * @throws coding_exception
     */
    public function update_after_question_answered(int $time): void {
        if ($this->adpqattempt === null) {
            throw new coding_exception('attempt record must be set already when updating an attempt with any data');
        }

        $record = $this->adpqattempt;
        $record->questionsattempted = (int) $record->questionsattempted + 1;
        $this->adpqattempt = $record;

        $this->save($time);
    }

    /**
     * Sets the attempt as complete.
     *
     * @param context_module $context
     * @param string $statusmessage
     * @param int $time Current timestamp.
     * @return void
     */
    public function complete(context_module $context, string $statusmessage, int $time): void {
        // Need to keep the record as it is before triggering the event below.
        $attemptrecordsnapshot = clone $this->adpqattempt;

        $this->adpqattempt->attemptstate = attempt_state::COMPLETED;
        $this->adpqattempt->attemptstopcriteria = $statusmessage;
        $this->save($time);

        adaptivequiz_update_grades($this->adaptivequiz, $this->userid);

        $event = attempt_completed::create([
            'objectid' => $this->adpqattempt->id,
            'context' => $context,
            'userid' => $this->adpqattempt->userid
        ]);
        $event->add_record_snapshot('adaptivequiz_attempt', $attemptrecordsnapshot);
        $event->add_record_snapshot('adaptivequiz', $this->adaptivequiz);
        $event->trigger();
    }

    /**
     * Sets quba id for the attempt.
     *
     * @param int $id
     */
    public function set_quba_id(int $id): void {
        $this->adpqattempt->uniqueid = $id;

        $this->save(time());
    }

    /**
     * Returns the attempt record from {adaptivequiz_attempt}.
     *
     * @return stdClass {@see self::$adpqattempt}.
     */
    public function read_attempt_data(): stdClass {
        return $this->adpqattempt;
    }

    /**
     * Checks whether the user has a completed attempt for the specified adaptive quiz instance.
     *
     * @param int $adaptivequizid
     * @param int $userid
     * @return bool
     */
    public static function user_has_completed_on_quiz(int $adaptivequizid, int $userid): bool {
        global $DB;

        return $DB->record_exists(self::TABLE,
            ['userid' => $userid, 'instance' => $adaptivequizid, 'attemptstate' => attempt_state::COMPLETED]);
    }

    /**
     * Returns an in-progress attempt for the user, returns null when no such attempt was found.
     *
     * @param stdClass $adaptivequiz
     * @param int $userid
     * @return self|null
     */
    public static function find_in_progress_for_user(stdClass $adaptivequiz, int $userid): ?self {
        global $DB;

        $record = $DB->get_record(
            'adaptivequiz_attempt',
            ['instance' => $adaptivequiz->id, 'userid' => $userid, 'attemptstate' => attempt_state::IN_PROGRESS]
        );
        if (!$record) {
            return null;
        }

        $attempt = new self($adaptivequiz, $userid);
        $attempt->adpqattempt = $record;

        return $attempt;
    }

    /**
     * Returns an attempt by its id.
     *
     * @param int $id
     * @param stdClass $adaptivequiz
     * @return self
     */
    public static function get_by_id(int $id, stdClass $adaptivequiz): self {
        global $DB;

        $record = $DB->get_record('adaptivequiz_attempt', ['id' => $id], '*', MUST_EXIST);

        $attempt = new self($adaptivequiz, $record->userid);
        $attempt->adpqattempt = $record;

        return $attempt;
    }

    /**
     * Created an instance of attempt, saves it in the database and returns as the result.
     *
     * @param stdClass $adaptivequiz A record from {adaptivequiz}.
     * @param int $userid
     * @return self
     */
    public static function create(stdClass $adaptivequiz, int $userid): self {
        global $DB;

        $time = time();

        $record = new stdClass();
        $record->instance = $adaptivequiz->id;
        $record->userid = $userid;
        $record->uniqueid = 0;
        $record->attemptstate = attempt_state::IN_PROGRESS;
        $record->attemptstopcriteria = '';
        $record->questionsattempted = 0;
        $record->timecreated = $time;
        $record->timemodified = $time;

        $record->id = $DB->insert_record(self::TABLE, $record);

        $attempt = new self($adaptivequiz, $userid);
        $attempt->adpqattempt = $record;

        return $attempt;
    }

    /**
     * This function adds a message to the debugging array
     * @param string $message details of the debugging message
     */
    protected function print_debug($message = '') {
        if ($this->debugenabled) {
            $this->debug[] = $message;
        }
    }

    /**
     * Saves the attempt in its current state to the database.
     *
     * @param int $time Current timestamp.
     * @return void
     */
    private function save(int $time): void {
        global $DB;

        $this->adpqattempt->timemodified = $time;

        $DB->update_record(self::TABLE, $this->adpqattempt);
    }
}
