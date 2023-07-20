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

namespace mod_adaptivequiz\output\report\attemptgraph;

use core_tag_tag;
use mod_adaptivequiz\local\catalgorithm\catalgo;
use mod_adaptivequiz\local\question\question_answer_evaluation;
use mod_adaptivequiz\local\report\questions_difficulty_range;
use moodle_exception;
use question_engine;
use renderable;
use stdClass;

/**
 * Output object to render the attempt graph data.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_graph_dataset implements renderable {

    /**
     * @var attempt_graph_dataset_point[] $points
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
     * @throws moodle_exception
     */
    public static function create_for_attempt(stdClass $attemptrecord): self {
        global $DB;

        $dataset = new self();

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

        $numattempted = 0;
        $numcorrect = 0;
        $difficultysum = 0;
        foreach ($quba->get_slots() as $slot) {
            $numattempted++;

            $questiondifficulty = self::discover_question_difficulty($quba->get_question($slot)->id, $questiontags);

            $difficultylogits = catalgo::convert_linear_to_logit($questiondifficulty, $difficultyrange);
            $difficultysum = $difficultysum + $difficultylogits;

            $answerevaluationresult = $questionanswerevaluation->perform($slot);
            $answeriscorrect = is_null($answerevaluationresult) ? false : $answerevaluationresult->answer_is_correct();

            if ($answeriscorrect) {
                $numcorrect++;
            }

            $targetquestiondifficulty = $adaptivequiz->startinglevel;
            if ($slot > 1) {
                $targetquestiondifficulty = self::compute_target_question_difficulty($answeriscorrect, $questiondifficulty,
                    $difficultyrange, $numattempted);
            }

            $dataset->points[] = self::build_dataset_point($numattempted, $numcorrect, $answeriscorrect, $difficultysum,
                $questiondifficulty, $targetquestiondifficulty, $difficultyrange);
        }

        return $dataset;
    }

    /**
     * Property getter.
     *
     * @return attempt_graph_dataset_point[]
     */
    public function points(): array {
        return $this->points;
    }

    /**
     * Constructs a dataset point object given the question answer data.
     *
     * @param int $numattempted
     * @param int $numcorrect
     * @param bool $answeriscorrect
     * @param float $difficultysum
     * @param int $questiondifficulty
     * @param int $targetquestiondifficulty
     * @param questions_difficulty_range $difficultyrange
     * @return attempt_graph_dataset_point
     */
    private static function build_dataset_point(
        int $numattempted,
        int $numcorrect,
        bool $answeriscorrect,
        float $difficultysum,
        int $questiondifficulty,
        int $targetquestiondifficulty,
        questions_difficulty_range $difficultyrange
    ): attempt_graph_dataset_point {
        $abilitylogits = catalgo::estimate_measure($difficultysum, $numattempted, $numcorrect, $numattempted - $numcorrect);
        $abilityfraction = 1 / ( 1 + exp( (-1 * $abilitylogits) ) );
        $abilitymeasure = (($difficultyrange->highest_level() - $difficultyrange->lowest_level()) * $abilityfraction)
            + $difficultyrange->lowest_level();

        $standarderrorlogits = catalgo::estimate_standard_error($numattempted, $numcorrect, $numattempted - $numcorrect);
        $standarderror = catalgo::convert_logit_to_percent($standarderrorlogits);

        $standarderrorrangemin = max($difficultyrange->lowest_level(),
            $abilitymeasure - ($standarderror * ($difficultyrange->highest_level() - $difficultyrange->lowest_level())));
        $standarderrorrangemax = min($difficultyrange->highest_level(),
            $abilitymeasure + ($standarderror * ($difficultyrange->highest_level() - $difficultyrange->lowest_level())));

        $point = new attempt_graph_dataset_point();
        $point->questiondifficulty = $questiondifficulty;
        $point->targetquestiondifficulty = $targetquestiondifficulty;
        $point->answeriscorrect = $answeriscorrect;
        $point->abilitymeasure = $abilitymeasure;
        $point->standarderror = $standarderror;
        $point->standarderrorrangemin = $standarderrorrangemin;
        $point->standarderrorrangemax = $standarderrorrangemax;

        return $point;
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

    /**
     * Computes the target difficulty level for the question.
     *
     * @param bool $answeriscorrect
     * @param int $questiondifficulty
     * @param questions_difficulty_range $difficultyrange
     * @param int $numattempted
     * @return int
     */
    private static function compute_target_question_difficulty(
        bool $answeriscorrect,
        int $questiondifficulty,
        questions_difficulty_range $difficultyrange,
        int $numattempted
    ): int {
        $difficultylogits = catalgo::convert_linear_to_logit($questiondifficulty, $difficultyrange);

        if ($answeriscorrect) {
            $targetquestiondifficulty = round(catalgo::map_logit_to_scale($difficultylogits + 2 / $numattempted,
                $difficultyrange->highest_level(), $difficultyrange->lowest_level()));
            if ($targetquestiondifficulty == $questiondifficulty
                && $targetquestiondifficulty < $difficultyrange->highest_level()
            ) {
                $targetquestiondifficulty++;
            }

            return $targetquestiondifficulty;
        }

        $targetquestiondifficulty = round(catalgo::map_logit_to_scale($difficultylogits - 2 / $numattempted,
            $difficultyrange->highest_level(), $difficultyrange->lowest_level()));
        if ($targetquestiondifficulty == $questiondifficulty
            && $targetquestiondifficulty > $difficultyrange->lowest_level()
        ) {
            $targetquestiondifficulty--;
        }

        return $targetquestiondifficulty;
    }
}
