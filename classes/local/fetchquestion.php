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

use coding_exception;
use dml_exception;
use dml_read_exception;
use invalid_parameter_exception;
use mod_adaptivequiz\local\question\difficulty_questions_mapping;
use mod_adaptivequiz\local\repository\questions_number_per_difficulty;
use mod_adaptivequiz\local\repository\questions_repository;
use mod_adaptivequiz\local\repository\tags_repository;
use moodle_exception;
use stdClass;

/**
 * This class does the work of fetching a questions associated with a level of difficulty and within a question category.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fetchquestion {
    /**
     * The maximum number of attempts at finding a tag containing questions
     */
    const MAXTAGRETRY = 5;

    /**
     * The maximum number of tries at finding avaiable questions
     */
    const MAXNUMTRY = 100000;

    /** @var stdClass $adaptivequiz object, properties come from the adaptivequiz table */
    protected $adaptivequiz;

    /**
     * @var bool $debugenabled flag to denote developer debugging is enabled and this class should write message to the debug array
     */
    protected $debugenabled = false;

    /** @var array $debug array containing debugging information */
    protected $debug = array();

    /** @var int $level the level of difficutly that will be used to fetch questions */
    protected $level = 1;

    /** @var string $questcatids a string of comma separated question category ids */
    protected $questcatids = '';

    /** @var int $minimumlevel the minimum level achievable in the attempt */
    protected $minimumlevel;

    /** @var int $maximumlevel the maximum level achievable in the attempt */
    protected $maximumlevel;

    /** @var bool $rebuild a flag used to force the rebuilding of the $tagquestsum property */
    public $rebuild = false;

    /**
     * @var difficulty_questions_mapping $tagquestsum
     */
    private $tagquestsum;

    /**
     * @var array $tags An array of tags that used to identify eligible questions for the attempt.
     */
    private $tags;

    /**
     * Constructor initializes data required to retrieve questions associated with tag and within question categories.
     *
     * @param stdClass $adaptivequiz A record object from the {adaptivequiz} table.
     * @param int $level Level of difficulty to look for when fetching a question.
     * @param int $minimumlevel The minimum level the student can achieve.
     * @param int $maximumlevel The maximum level the student can achieve.
     * @throws coding_exception
     */
    public function __construct($adaptivequiz, $level, $minimumlevel, $maximumlevel) {
        $this->adaptivequiz = $adaptivequiz;
        $this->minimumlevel = $minimumlevel;
        $this->maximumlevel = $maximumlevel;

        if (!is_int($level) || 0 >= $level) {
            throw new coding_exception('Argument 2 is not an positive integer', 'Second parameter must be a positive integer');
        }

        if ($minimumlevel >= $maximumlevel) {
            throw new coding_exception('Minimum level is greater than maximum level',
                'Invalid minimum and maximum parameters passed');
        }

        $this->level = $level;

        $this->tagquestsum = difficulty_questions_mapping::from_global_session_or_empty();

        $this->tags = [ADAPTIVEQUIZ_QUESTION_TAG];

        if (debugging('', DEBUG_DEVELOPER)) {
            $this->debugenabled = true;
        }
    }

    /**
     * This function sets the level of difficulty property
     * @param int $level level of difficulty
     * @return void
     */
    public function set_level($level = 1) {
        if (!is_int($level) || 0 >= $level) {
            throw new coding_exception('Argument 1 is not an positive integer', 'First parameter must be a positive integer');
        }

        $this->level = $level;
    }

    /**
     * This function returns the level of difficulty property
     * @return int - level of difficulty
     */
    public function get_level() {
        return $this->level;
    }

    /**
     * Reset the maximum question level to search for to a new value
     *
     * @param int $maximumlevel
     * @return void
     * @throws coding_exception if the maximum level is less than minimum level
     */
    public function set_maximum_level($maximumlevel) {
        if ($maximumlevel < $this->minimumlevel) {
            throw new coding_exception('Maximum level is less than minimum level', 'Invalid maximum level set.');
        }
        $this->maximumlevel = $maximumlevel;
    }

    /**
     * Reset the maximum question level to search for to a new value
     *
     * @param int $maximumlevel
     * @return void
     * @throws coding_exception if the minimum level is less than maximum level
     */
    public function set_minimum_level($minimumlevel) {
        if ($minimumlevel > $this->maximumlevel) {
            throw new coding_exception('Minimum level is less than maximum level', 'Invalid minimum level set.');
        }
        $this->minimumlevel = $minimumlevel;
    }

    /**
     * This functions adds a message to the debugging array
     * @param string $message: details of the debugging message
     * @return void
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
     * This function returns the debug array
     * @return array - array of debugging messages
     */
    public function get_debug() {
        return $this->debug;
    }

    /**
     * Decrements the sum of questions for the given difficulty level by 1.
     *
     * @param int $level
     */
    public function decrement_question_sum_for_difficulty_level(int $level): void {
        $this->tagquestsum = $this->tagquestsum->decrement_questions_number_for_difficulty($level);
        $this->update_global_session();
    }

    /**
     * Constructs a mapping of difficulty levels and the number of questions in each difficulty level.
     *
     * @param array $tags An array of tags used by the activity.
     * @param int $min The minimum difficulty allowed for the attempt.
     * @param int $max The maximum difficulty allowed for the attempt.
     * @return difficulty_questions_mapping
     */
    public function initalize_tags_with_quest_count(array $tags, int $min, int $max): difficulty_questions_mapping {
        $tagquestsum = difficulty_questions_mapping::create_empty();
        $questcat = $this->retrieve_question_categories();

        // Traverse through the array of configured tags used by the activity.
        foreach ($tags as $tag) {
            $tagids = $this->retrieve_all_tag_ids($min, $max, $tag);
            $difficultyquestionsmappings = $this->retrieve_tags_with_question_count($tagids, $questcat);

            // Traverse the $difficultyquestionsmappings array and add the values with the values current in the
            // $tagquestsum argument.
            foreach ($difficultyquestionsmappings as $difficultyquestionsmapping) {
                $tagquestsum = $tagquestsum->add_to_questions_number_for_difficulty(
                    $difficultyquestionsmapping->difficulty(),
                    $difficultyquestionsmapping->questions_number()
                );
            }
        }

        return $tagquestsum;
    }

    /**
     * This function retrieves a question associated with a Moodle tag level of difficulty.
     *
     * If the search for the tag turns up empty the function tries to find another tag whose difficulty level is either higher
     * or lower.
     *
     * @param array $excquestids An array of question ids to exclude from the search.
     * @return array An array of question ids.
     */
    public function fetch_questions(array $excquestids = array()): array {
        $questids = array();

        if ($this->tagquestsum->is_empty() || $this->rebuild) {
            // Initialize the difficulty tag question sum property for searching.
            $this->tagquestsum = $this->initalize_tags_with_quest_count($this->tags, $this->minimumlevel, $this->maximumlevel);
            $this->update_global_session();
        }

        // If tagquestsum property ie empty then return with nothing.
        if ($this->tagquestsum->is_empty()) {
            $this->print_debug('fetch_questions() - tagquestsum is empty');
            return array();
        }
        // Check if the requested level has available questions.
        if ($this->tagquestsum->questions_exist_for_difficulty($this->level)) {
            $tagids = $this->retrieve_tag($this->level);
            $questids = $this->find_questions_with_tags($tagids, $excquestids);

            return $questids;
        }

        // Look for a level that has avaialbe qustions.
        $level = $this->level;
        for ($i = 1; $i <= self::MAXNUMTRY; $i++) {
            // Check if the offset level is now out of bounds and stop the loop.
            if ($this->minimumlevel > $level - $i && $this->maximumlevel < $level + $i) {
                $i += self::MAXNUMTRY + 1;
                $this->print_debug('fetch_questions() - searching levels has gone out of bounds of the min and max levels. '.
                    'No questions returned');
                continue;
            }

            // First check a level higher than the originally requested level.
            $newlevel = $level + $i;

            /*
             * If the level is within the boundries set for the attempt and the level exists and the count of question is greater
             * than zero, retrieve the tag id and the questions available
             */
            $condition = $newlevel <= $this->maximumlevel && $this->tagquestsum->questions_exist_for_difficulty($newlevel);
            if ($condition) {
                $tagids = $this->retrieve_tag($newlevel);
                $questids = $this->find_questions_with_tags($tagids, $excquestids);
                $this->level = $newlevel;
                $i += self::MAXNUMTRY + 1;
                $this->print_debug('fetch_questions() - original level could not be found.  Returned a question from level '.
                    $newlevel.' instead');
                continue;
            }

            // Check a level lower than the originally requested level.
            $newlevel = $level - $i;

            /*
             * If the level is within the boundries set for the attempt and the level exists and the count of question is greater
             *  than zero, retrieve the tag id and thequestions available
             */
            $condition = $newlevel >= $this->minimumlevel && $this->tagquestsum->questions_exist_for_difficulty($newlevel);
            if ($condition) {
                $tagids = $this->retrieve_tag($newlevel);
                $questids = $this->find_questions_with_tags($tagids, $excquestids);
                $this->level = $newlevel;
                $i += self::MAXNUMTRY + 1;
                $this->print_debug('fetch_questions() - original level could not be found.  Returned a question from level '
                    .$newlevel.' instead');
                continue;
            }
        }

        return $questids;
    }

    /**
     * This function retrieves all the tag ids that can be used in this attempt.
     *
     * @param int $minimumlevel The minimum level the student can achieve.
     * @param int $maximumlevel The maximum level the student can achieve.
     * @param string $tagprefix The tag prefix used.
     * @return array An array whose keys represent the difficulty level and values are tag ids.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function retrieve_all_tag_ids(int $minimumlevel, int $maximumlevel, string $tagprefix): array {
        if (empty(trim($tagprefix))) {
            throw new invalid_parameter_exception('Tag prefix cannot be empty.');
        }

        $tags = array_map(function(int $level): string {
            return ADAPTIVEQUIZ_QUESTION_TAG . $level;
        }, range($minimumlevel, $maximumlevel));

        if (!$leveltagidmap = tags_repository::get_question_level_to_tag_id_mapping_by_tag_names($tags)) {
            return [];
        }

        return $leveltagidmap;
    }

    /**
     * This function determines how many questions are associated with a tag, for questions contained in the category
     * used by the activity.
     *
     * @param array $tagids an array whose key is the difficulty level and value is the tag id representing the difficulty level
     * @param array $categories an array whose key and value is the question category id
     * @return questions_number_per_difficulty[]
     * @throws coding_exception
     * @throws dml_read_exception
     * @throws dml_exception
     */
    public function retrieve_tags_with_question_count($tagids, $categories): array {
        return questions_repository::count_questions_number_per_difficulty($tagids, $categories);
    }

    /**
     * This function retrieves all tag ids, used by this activity and associated with a particular level of difficulty.
     *
     * @param int $level The level of difficulty (optional). If 0 is passed then the function will use the level class
     * property, otherwise the argument value will be used.
     * @return array An array whose keys represent the difficulty level and values are tag ids.
     * @throws dml_exception
     * @throws coding_exception
     */
    public function retrieve_tag(int $level = 0): array {
        $tags = array_map(function(string $tag) use($level): string {
            return $tag . $level;
        }, $this->tags);

        if (!$tagidlist = tags_repository::get_tag_id_list_by_tag_names($tags)) {
            return [];
        }

        return $tagidlist;
    }

    /**
     * This function retrieves questions within the assigned question categories and
     * questions associated with tagids
     * @param array $tagids an array of tag is
     * @param array $exclude an array of question ids to exclude from the search
     * @return array an array whose keys are qustion ids and values are the question names
     */
    public function find_questions_with_tags($tagids = [], $exclude = []) {
        $questcat = $this->retrieve_question_categories();

        return questions_repository::find_questions_with_tags($tagids, $questcat, $exclude);
    }

    /**
     * This function retrieves all of the question categories used the activity.
     * @return array an array of quesiton category ids
     */
    protected function retrieve_question_categories() {
        global $DB;

        // Check cached result.
        if (!empty($this->questcatids)) {
            $this->print_debug('retrieve_question_categories() - question category ids (from cache): '.
                $this->vardump($this->questcatids));
            return $this->questcatids;
        }

        $param = array('instance' => $this->adaptivequiz->id);
        $records = $DB->get_records_menu('adaptivequiz_question', $param, 'questioncategory ASC', 'id,questioncategory');

        // Cache the results.
        $this->questcatids = $records;

        $this->print_debug('retrieve_question_categories() - question category ids: '.$this->vardump($records));

        return $records;
    }

    /**
     * Stores the serialized value of the 'tagquestsum' property in the session.
     */
    private function update_global_session(): void {
        global $SESSION;

        $SESSION->adpqtagquestsum = $this->tagquestsum->as_array();
    }
}
