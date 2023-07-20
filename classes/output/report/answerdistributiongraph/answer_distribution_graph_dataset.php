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

namespace mod_adaptivequiz\output\report\answerdistributiongraph;

use core_tag_tag;
use mod_adaptivequiz\local\question\question_answer_evaluation;
use mod_adaptivequiz\local\report\answers_summary_per_difficulty;
use mod_adaptivequiz\local\report\questions_difficulty_range;
use moodle_exception;
use question_engine;
use renderable;
use stdClass;

/**
 * Output object to render the answer distribution graph data.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class answer_distribution_graph_dataset implements renderable {

    /**
     * @var answer_distribution_graph_dataset_point[] $points
     */
    private $points;

    /**
     * Empty and closed, the factory method must be used instead.
     */
    private function __construct() {
    }

    /**
     * A factory method to properly instantiate an object of the class.
     *
     * @param stdClass $attemptrecord A record from the {adaptivequiz_attempt} table.
     * @return self
     */
    public static function create_for_attempt(stdClass $attemptrecord): self {
        global $DB;

        // TODO: this is ineffective to fetch the instance record here, consider storing this required data in mod's storage.
        $adaptivequiz = $DB->get_record('adaptivequiz', ['id' => $attemptrecord->instance], '*', MUST_EXIST);
        $difficultyrange = questions_difficulty_range::from_activity_instance($adaptivequiz);

        $quba = question_engine::load_questions_usage_by_activity($attemptrecord->uniqueid);

        // First iteration is to collect all questions to get tags for.
        $questionidarr = [];
        foreach ($quba->get_slots() as $slot) {
            $questionidarr[] = $quba->get_question($slot)->id;
        }

        // This returns an array with the following structure: [questionid] => Array of core_tag_tag Object's.
        $questiontags = core_tag_tag::get_items_tags('core_question', 'question', $questionidarr);

        $questionanswerevaluation = new question_answer_evaluation($quba);
        $answersperdifficulty = answers_summary_per_difficulty::from_difficulty_range($difficultyrange);

        foreach ($quba->get_slots() as $slot) {
            $questiondifficulty = self::discover_question_difficulty($quba->get_question($slot)->id, $questiontags);
            $answerevaluationresult = $questionanswerevaluation->perform($slot);

            $answerevaluationresult->answer_is_correct()
                ? $answersperdifficulty->increment_number_of_correct_answers_for_difficulty($questiondifficulty)
                : $answersperdifficulty->increment_number_of_incorrect_answers_for_difficulty($questiondifficulty);
        }

        $dataset = new self();
        $dataset->points = [];

        for ($i = $difficultyrange->lowest_level(); $i <= $difficultyrange->highest_level(); $i++) {
            $answersfordifficulty = $answersperdifficulty->answers_summary_for_difficulty($i);

            $datasetpoint = new answer_distribution_graph_dataset_point();
            $datasetpoint->questiondifficulty = $i;
            $datasetpoint->numberofcorrectanswers = $answersfordifficulty->number_of_correct_answers();
            $datasetpoint->numberofincorrectanswers = $answersfordifficulty->number_of_incorrect_answers();

            $dataset->points[] = $datasetpoint;
        }

        return $dataset;
    }

    /**
     * Returns dataset.
     *
     * @return answer_distribution_graph_dataset_point[]
     */
    public function points(): array {
        return $this->points;
    }

    /**
     * Fetches question difficulty from the tags pool passed.
     *
     * @param int $questionid
     * @param array $questiontags
     * @return int
     * @throws moodle_exception
     */
    private static function discover_question_difficulty(int $questionid, array $questiontags): int {
        if (!array_key_exists($questionid, $questiontags)) {
            // TODO: consider storing this required data in mod's storage instead to avoid checking such things here.
            throw new moodle_exception('', 'adaptivequiz');
        }

        $tagstrarr = [];
        foreach ($questiontags[$questionid] as $questiontag) {
            $tagstrarr[] = $questiontag->name;
        }

        $difficulty = adaptivequiz_get_difficulty_from_tags($tagstrarr);
        if (is_null($difficulty)) {
            throw new moodle_exception('', 'adaptivequiz');
        }

        return $difficulty;
    }
}
