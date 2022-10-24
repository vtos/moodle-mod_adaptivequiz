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
 * This class contains information about the attempt parameters
 *
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_adaptivequiz\local\attempt;

use coding_exception;
use context_module;
use dml_exception;
use mod_adaptivequiz\event\attempt_completed;
use mod_adaptivequiz\local\fetchquestion;
use moodle_exception;
use question_bank;
use question_engine;
use question_state_gaveup;
use question_state_gradedpartial;
use question_state_gradedright;
use question_state_gradedwrong;
use question_usage_by_activity;
use stdClass;

class attempt {

    private const TABLE = 'adaptivequiz_attempt';

    /**
     * The name of the module
     */
    const MODULENAME = 'mod_adaptivequiz';

    /**
     * The behaviour to use be default
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

    /** @var int $uniqueid a unique number identifying the activity usage of questions */
    protected $uniqueid;

    /** @var int $questionsattempted the total of question attempted */
    protected $questionsattempted;

    /** @var float $standarderror the standard error of the attempt  */
    protected $standarderror;

    /** @var int $slot - a question slot number */
    protected $slot = 0;

    /** @var array $tags an array of tags that used to identify eligible questions for the attempt */
    protected $tags = array();

    /** @var array $status status message storing the reason why the attempt was stopped */
    protected $status = '';

    /** @var int $level the difficulty level the attempt is currently set at */
    protected $level = 0;

    /** @var int $lastdifficultylevel the last difficulty level used in the attempt if any */
    protected $lastdifficultylevel = null;

    /**
     * @var stdClass $adaptivequiz Record from the {adaptivequiz} table.
     */
    private $adaptivequiz;

    /**
     * @var attempt_state $attemptstate
     */
    private $attemptstate;

    /**
     * Constructor initializes required data to process the attempt
     * @param stdClass $adaptivequiz adaptivequiz record object from adaptivequiz table
     * @param int $userid user id
     * @param array $tags an array of acceptible tags
     */
    public function __construct($adaptivequiz, $userid, $tags = array()) {
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
     * This function returns the adaptivequiz property
     * @return stdClass adaptivequiz record
     */
    public function get_adaptivequiz() {
        return $this->adaptivequiz;
    }

    /**
     * This function returns the $level property
     * @return int level property
     */
    public function get_level() {
        return $this->level;
    }

    /**
     * This function sets the $level property
     * @param int $level difficulty level to fetch
     */
    public function set_level($level) {
        $this->level = $level;
    }

    /**
     * Set the last difficulty level that was used.
     * This may influence the next question chosing process.
     *
     * @param int $lastdifficultylevel
     * @return void
     */
    public function set_last_difficulty_level($lastdifficultylevel) {
        if (is_null($lastdifficultylevel)) {
            $this->lastdifficultylevel = null;
        } else {
            $this->lastdifficultylevel = (int) $lastdifficultylevel;
        }
    }

    /**
     * This function returns the current slot number set for the attempt
     * @return int question slot number
     */
    public function get_question_slot_number() {
        return $this->slot;
    }

    /**
     * This function sets the current slot number set for the attempt
     * @throws coding_exception - exception is thrown the argument is not a positive integer
     * @param int $slot slot number
     */
    public function set_question_slot_number($slot) {
        if (!is_int($slot) || 0 >= $slot) {
            throw new coding_exception('adaptiveattempt: Argument 1 is not an positive integer', 'Slot must be a positive integer');
        }

        $this->slot = $slot;
    }

    /**
     * This function checks to see if the difficulty level is out of the boundries set for the attempt
     * @param int $level the difficulty level requested
     * @param stdClass $adaptivequiz an adaptivequiz record
     * @return bool true if the level is in bounds, otherwise false
     */
    public function level_in_bounds($level, $adaptivequiz) {
        if ($adaptivequiz->lowestlevel <= $level && $adaptivequiz->highestlevel >= $level) {
            return true;
        }

        return false;
    }

    /**
     * This function returns the currently set status message.
     *
     * @return string The status message property.
     */
    public function get_status() {
        return $this->status;
    }

    /**
     * This function does the work of initializing data required to fetch a new question for the attempt.
     *
     * @return bool True if attempt started okay otherwise false.
     */
    public function start_attempt(question_usage_by_activity $quba, int $questionsattempted) {
        // Check if the level requested is out of the minimum/maximum boundries for the attempt.
        if (!$this->level_in_bounds($this->level, $this->adaptivequiz)) {
            $var = new stdClass();
            $var->level = $this->level;
            $this->status = get_string('leveloutofbounds', 'adaptivequiz', $var);

            return false;
        }

        // Check if the attempt has reached the maximum number of questions attempted.
        if ($this->max_number_of_questions_is_answered()) {
            $this->status = get_string('maxquestattempted', 'adaptivequiz');

            return false;
        }

        // Find the last question viewed/answered by the user.
        $this->slot = $this->find_last_quest_used_by_attempt($quba);
        // Create an instance of the fetchquestion class.
        $fetchquestion = new fetchquestion($this->adaptivequiz, 1, $this->adaptivequiz->lowestlevel,
            $this->adaptivequiz->highestlevel);

        // Check if this is the beginning of an attempt (and pass the starting level) or the continuation of an attempt.
        if (empty($this->slot) && 0 == $questionsattempted) {
            // Set the starting difficulty level.
            $fetchquestion->set_level((int) $this->adaptivequiz->startinglevel);
            // Sets the level class property.
            $this->level = $this->adaptivequiz->startinglevel;
            // Set the rebuild flag for fetchquestion class.
            $fetchquestion->rebuild = true;

            $this->print_debug("start_attempt() - Brand new attempt.  Set starting level: {$this->adaptivequiz->startinglevel}.");

        } else if (!empty($this->slot) && $this->was_answer_submitted_to_question($quba, $this->slot)) {
            // If the attempt already has a question attached to it, check if an answer was submitted to the question.
            // If so fetch a new question.

            // Provide the question-fetching process with limits based on our last question.
            // If the last question was correct...
            if ($quba->get_question_mark($this->slot) > 0) {
                // Only ask questions harder than the last question unless we are already at the top of the ability scale.
                if (!is_null($this->lastdifficultylevel) && $this->lastdifficultylevel < $this->adaptivequiz->highestlevel) {
                    $fetchquestion->set_minimum_level($this->lastdifficultylevel + 1);
                    // Do not ask a question of the same level unless we are already at the max.
                    if ($this->lastdifficultylevel == $this->level) {
                        $this->print_debug("start_attempt() - Last difficulty is the same as the new difficulty, ".
                                "incrementing level from {$this->level} to ".($this->level + 1).".");
                        $this->level++;
                    }
                }
            } else {
                // If the last question was wrong...
                // Only ask questions easier than the last question unless we are already at the bottom of the ability scale.
                if (!is_null($this->lastdifficultylevel) && $this->lastdifficultylevel > $this->adaptivequiz->lowestlevel) {
                    $fetchquestion->set_maximum_level($this->lastdifficultylevel - 1);
                    // Do not ask a question of the same level unless we are already at the min.
                    if ($this->lastdifficultylevel == $this->level) {
                        $this->print_debug("start_attempt() - Last difficulty is the same as the new difficulty, ".
                                "decrementing level from {$this->level} to ".($this->level - 1).".");
                        $this->level--;
                    }
                }
            }

            // Reset the slot number back to zero, since we are going to fetch a new question.
            $this->slot = 0;
            // Set the level of difficulty to fetch.
            $fetchquestion->set_level((int) $this->level);
        } else if (empty($this->slot) && 0 < $questionsattempted) {
            // If this condition is met, then something went wrong because the slot id is empty BUT the questions attempted is
            // greater than zero. Stop the attempt.
            $this->status = get_string('errorattemptstate', 'adaptivequiz');

            return false;
        }

        // If the slot property is set, then we have a question that is ready to be attempted.  No more process is required.
        if (!empty($this->slot)) {
            return true;
        }

        // If we are here, then the slot property was unset and a new question needs to prepared for display.
        $status = $this->get_question_ready($fetchquestion, $quba);

        if (empty($status)) {
            $var = new stdClass();
            $var->level = $this->level;
            $this->status = get_string('errorfetchingquest', 'adaptivequiz', $var);

            return false;
        }

        return $status;
    }

    /**
     * This function returns a random array element
     * @param array $questions an array of question ids.  Array key values are question ids
     * @return int a question id
     */
    public function return_random_question($questions) {
        if (empty($questions)) {
            return 0;
        }

        $questionid = array_rand($questions);
        $this->print_debug('return_random_question() - random question chosen questionid: '.$questionid);

        return (int) $questionid;
    }

    /**
     * This function retrieves the last question that was used in the attempt
     * @throws moodle_exception - exception is thrown function parameter is not an instance of question_usage_by_activity class
     * @param question_usage_by_activity $quba an object loaded with the unique id of the attempt
     * @return int question slot or 0 if no unmarked question could be found
     */
    public function find_last_quest_used_by_attempt($quba) {
        if (!$quba instanceof question_usage_by_activity) {
            throw new coding_exception('find_last_quest_used_by_attempt() - Argument was not a question_usage_by_activity object',
                $this->vardump($quba));
        }

        // The last slot in the array should be the last question that was attempted (meaning it was either shown to the user
        // or the user submitted an answer to it).
        $questslots = $quba->get_slots();

        if (empty($questslots) || !is_array($questslots)) {
            $this->print_debug('find_last_quest_used_by_attempt() - No question slots found for this '.
                'question_usage_by_activity object');
            return 0;
        }

        $questslot = end($questslots);
        $this->print_debug('find_last_quest_used_by_attempt() - Found a question slot: '.$questslot);

        return $questslot;
    }

    /**
     * This function determines if the user submitted an answer to the question
     * @param question_usage_by_activity $quba an object loaded with the unique id of the attempt
     * @param int $slot question slot id
     * @return bool true if an answer to the question was submitted, otherwise false
     */
    public function was_answer_submitted_to_question($quba, $slotid) {
        $state = $quba->get_question_state($slotid);

        // Check if the state of the quesiton attempted was graded right, partially right, wrong or gave up, count the question has
        // having an answer submitted.
        $marked = $state instanceof question_state_gradedright || $state instanceof question_state_gradedpartial
            || $state instanceof question_state_gradedwrong || $state instanceof question_state_gaveup;

        if ($marked) {
            return true;
        } else {
            // Save some debugging information.
            $this->print_debug('was_answer_submitted_to_question() - question state is unrecognized state: '.get_class($state).'
                    question slotid: '.$slotid.' quba id: '.$quba->get_id());
        }

        return false;
    }

    /**
     * The method initializes the question_usage_by_activity object. If an unfinished attempt has a usage id,
     * a question_usage_by_activity object will be loaded using the usage id. Otherwise, a new question_usage_by_activity object
     * is created.
     *
     * @throws moodle_exception Exception is thrown when required behaviour could not be found.
     */
    public function initialize_quba(context_module $context): ?question_usage_by_activity {
        if (!$this->behaviour_exists()) {
            throw new moodle_exception('Missing '.self::ATTEMPTBEHAVIOUR.' behaviour', 'Behaviour: '.self::ATTEMPTBEHAVIOUR.
                ' must exist in order to use this activity');
        }

        if (0 == $this->adpqattempt->uniqueid) {
            // Init question usage and set default behaviour of usage.
            $quba = question_engine::make_questions_usage_by_activity(self::MODULENAME, $context);
            $quba->set_preferred_behaviour(self::ATTEMPTBEHAVIOUR);
        } else {
            // Load a previously used question by usage object.
            $quba = question_engine::load_questions_usage_by_activity($this->adpqattempt->uniqueid);
        }

        return $quba;
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
     * This functions returns an array of all question ids that have been used in this attempt
     *
     * @return array an array of question ids
     */
    public function get_all_questions_in_attempt($uniqueid) {
        global $DB;

        $questions = $DB->get_records_menu('question_attempts', array('questionusageid' => $uniqueid), 'id ASC', 'id,questionid');

        return $questions;
    }

    public function id(): string {
        return $this->adpqattempt->id;
    }

    public function number_of_questions_attempted(): int {
        return (int) $this->adpqattempt->questionsattempted;
    }

    public function difficulty_sum(): float {
        return (float) $this->adpqattempt->difficultysum;
    }

    /**
     * @param cat_calculation_steps_result $calcstepsresult
     * @param int $time Timestamp to save the time of attempt modification.
     * @throws coding_exception
     */
    public function update_after_question_answered(cat_calculation_steps_result $calcstepsresult, int $time): void {
        if ($this->adpqattempt === null) {
            throw new coding_exception('attempt record must be set already when updating an attempt with any data');
        }

        $record = $this->adpqattempt;

        $record->difficultysum = (float) $record->difficultysum + $calcstepsresult->logit()->as_float();
        $record->questionsattempted = (int) $record->questionsattempted + 1;
        $record->standarderror = $calcstepsresult->standard_error();
        $record->measure = $calcstepsresult->measure();

        $this->adpqattempt = $record;

        $this->save($time);
    }

    public function complete(context_module $context, float $standarderror, string $statusmessage, int $time): void {
        // Need to keep the record as it is before triggering the event below.
        $attemptrecordsnapshot = clone $this->adpqattempt;

        $this->adpqattempt->attemptstate = attempt_state::COMPLETED;
        $this->adpqattempt->attemptstopcriteria = $statusmessage;
        $this->adpqattempt->standarderror = $standarderror;

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
     * @throws dml_exception
     */
    public static function user_has_completed_on_quiz(int $adaptivequizid, int $userid): bool {
        global $DB;

        return $DB->record_exists(self::TABLE,
            ['userid' => $userid, 'instance' => $adaptivequizid, 'attemptstate' => attempt_state::COMPLETED]);
    }

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
        $record->difficultysum = 0;
        $record->standarderror = 999;
        $record->measure = 0;
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
     * Answer a string view of a variable for debugging purposes
     * @param mixed $variable
     */
    protected function vardump($variable) {
        ob_start();
        var_dump($variable);
        return ob_get_clean();
    }

    /**
     * This function gets the question ready for display to the user.
     *
     * @param fetchquestion $fetchquestion A {@link fetchquestion} object initialized to the activity instance of the attempt.
     * @param question_usage_by_activity $quba
     * @return bool True if everything went okay, otherwise false.
     */
    protected function get_question_ready($fetchquestion, question_usage_by_activity $quba) {
        // Fetch questions already attempted.
        $exclude = $this->get_all_questions_in_attempt($this->adpqattempt->uniqueid);
        // Fetch questions for display.
        $questionids = $fetchquestion->fetch_questions($exclude);

        if (empty($questionids)) {
            $this->print_debug('get_question_ready() - Unable to fetch a question $questionsids:'.$this->vardump($questionids));

            return false;
        }
        // Select one random question.
        $questiontodisplay = $this->return_random_question($questionids);

        if (empty($questiontodisplay)) {
            $this->print_debug('get_question_ready() - Unable to randomly select a question $questionstodisplay:'.
                $questiontodisplay);

            return false;
        }

        // Load basic question data.
        $questionobj = question_preload_questions(array($questiontodisplay));
        get_question_options($questionobj);
        $this->print_debug('get_question_ready() - setup question options');

        // Make a copy of the array and pop off the first (and only) element (current() didn't work for some reason).
        $quest = $questionobj;
        $quest = array_pop($quest);

        // Create the question_definition object.
        $question = question_bank::load_question($quest->id);
        // Add the question to the usage question_usable_by_activity object.
        $this->slot = $quba->add_question($question);
        // Start the question attempt.
        $quba->start_question($this->slot);
        // Save the question usage and question attempt state to the DB.
        question_engine::save_questions_usage_by_activity($quba);
        // Update the attempt unique id.
        $this->set_attempt_uniqueid($quba->get_id());

        // Set class level property to the difficulty level of the question returned from fetchquestion class.
        $this->level = $fetchquestion->get_level();
        $this->print_debug('get_question_ready() - Question: '.$this->vardump($question).' loaded and attempt started. '.
                'Question_usage_by_activity saved.');

        return true;
    }

    /**
     * This function updates the current attempt with the question_usage_by_activity id.
     */
    protected function set_attempt_uniqueid(int $uniqueid): void {
        global $DB;

        $this->adpqattempt->uniqueid = $uniqueid;
        $this->timemodified = time();

        $DB->update_record(self::TABLE, $this->adpqattempt);
    }

    /**
     * This function retrives archetypal behaviours and sets the attempt behavour to to manual grade
     * @return bool true if the behaviour exists, else false
     */
    protected function behaviour_exists() {
        $exists = false;
        $behaviours = question_engine::get_archetypal_behaviours();

        if (!empty($behaviours)) {
            foreach ($behaviours as $key => $behaviour) {
                if (0 == strcmp(self::ATTEMPTBEHAVIOUR, $key)) {
                    // Behaviour found, exit the loop.
                    $exists = true;
                    break;
                }
            }
        }

        return $exists;
    }

    private function save(int $time): void {
        global $DB;

        $this->adpqattempt->timemodified = $time;

        $DB->update_record(self::TABLE, $this->adpqattempt);
    }

    private function max_number_of_questions_is_answered(): bool {
        return $this->adpqattempt->questionsattempted >= $this->adaptivequiz->maximumquestions;
    }
}
