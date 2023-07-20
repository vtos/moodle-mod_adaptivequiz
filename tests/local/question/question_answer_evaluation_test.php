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

namespace mod_adaptivequiz\local\question;

use advanced_testcase;
use context_course;
use context_module;
use mod_adaptivequiz\local\attempt\attempt;
use question_bank;
use question_engine;

/**
 * Test class.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_adaptivequiz\local\question\question_answer_evaluation
 */
class question_answer_evaluation_test extends advanced_testcase {

    /**
     * @var \question_usage_by_activity $quba
     */
    private $quba;

    /**
     * @var \stdClass $questioncategory
     */
    private $questioncategory;

    protected function setUp(): void {
        $course = $this->getDataGenerator()->create_course();

        $adaptivequiz = $this->getDataGenerator()
            ->get_plugin_generator('mod_adaptivequiz')
            ->create_instance([
                'highestlevel' => 10,
                'lowestlevel' => 1,
                'standarderror' => 14,
                'course' => $course->id,
            ]);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id);
        $context = context_module::instance($cm->id);

        $quba = question_engine::make_questions_usage_by_activity('mod_adaptivequiz', $context);
        $quba->set_preferred_behaviour(attempt::ATTEMPTBEHAVIOUR);
        $this->quba = $quba;

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $this->questioncategory = $questiongenerator->create_question_category(
            ['contextid' => context_course::instance($course->id)->id]
        );
    }

    public function test_test_it_gives_proper_answer_evaluation_when_no_answer_was_given(): void {
        self::resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $question = $questiongenerator->create_question('truefalse', null, [
            'name' => 'Question',
            'category' => $this->questioncategory->id,
        ]);

        // Given the question was just displayed, but not answered.
        $slot = $this->quba->add_question(question_bank::load_question($question->id));
        $this->quba->start_question($slot);

        // When performing answer evaluation.
        $questionanswerevaluation = new question_answer_evaluation($this->quba);
        $evaluationresult = $questionanswerevaluation->perform($slot);

        // Then the evaluation result should match the expectation.
        self::assertNull($evaluationresult);
    }

    /**
     * A test method.
     *
     * @dataProvider truefalse_question_answers_provider
     * @param question_answer_evaluation_result|null $expectation
     * @param string $response
     */
    public function test_it_gives_proper_answer_evaluation_for_truefalse_questions(
        ?question_answer_evaluation_result $expectation,
        string $response
    ): void {
        self::resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $question = $questiongenerator->create_question('truefalse', null, [
            'name' => 'Question',
            'category' => $this->questioncategory->id,
        ]);

        // Given the question was answered.
        $slot = $this->quba->add_question(question_bank::load_question($question->id));
        $this->quba->start_question($slot);

        $time = time();
        $this->quba->process_all_actions($time,
            $questiongenerator->get_simulated_post_data_for_questions_in_usage($this->quba, [$slot => $response], false));
        $this->quba->finish_all_questions($time);

        // When performing answer evaluation.
        $evaluationresult = (new question_answer_evaluation($this->quba))->perform($slot);

        // Then the evaluation result should match the expectation.
        self::assertEquals($expectation, $evaluationresult);
    }

    /**
     * Data provider method.
     *
     * @return array
     */
    public function truefalse_question_answers_provider(): array {
        return [
            '2' => [
                'expectation' => question_answer_evaluation_result::when_answer_is_correct(),
                'response' => 'True',
            ],
            '3' => [
                'expectation' => question_answer_evaluation_result::when_answer_is_incorrect(),
                'response' => 'False',
            ],
        ];
    }

    /**
     * A test method.
     *
     * @dataProvider multichoice_question_answers_provider
     * @param question_answer_evaluation_result|null $expectation
     * @param string $questionfrommaker
     * @param array $response
     */
    public function test_it_gives_proper_answer_evaluation_for_multichoice_questions(
        ?question_answer_evaluation_result $expectation,
        string $questionfrommaker,
        array $response
    ): void {
        self::resetAfterTest();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $question = $questiongenerator->create_question('multichoice', $questionfrommaker, [
            'name' => 'Question',
            'category' => $this->questioncategory->id,
        ]);

        // Given the question was answered.
        $slot = $this->quba->add_question(question_bank::load_question($question->id));
        $this->quba->start_question($slot);

        $time = time();
        $this->quba->process_all_actions($time, $this->quba->prepare_simulated_post_data([$slot => $response]));
        $this->quba->finish_all_questions($time);

        // When performing answer evaluation.
        $evaluationresult = (new question_answer_evaluation($this->quba))->perform($slot);

        // Then the evaluation result should match the expectation.
        self::assertEquals($expectation, $evaluationresult);
    }

    /**
     * Data provider method.
     *
     * @return array
     */
    public function multichoice_question_answers_provider(): array {
        return [
            'single, correct response' => [
                'expectation' => question_answer_evaluation_result::when_answer_is_correct(),
                'questionfrommaker' => 'one_of_four',
                'response' => ['answer' => 'One'],
            ],
            'single, incorrect response' => [
                'expectation' => question_answer_evaluation_result::when_answer_is_incorrect(),
                'questionfrommaker' => 'one_of_four',
                'response' => ['answer' => 'Three'],
            ],
            'multi, correct response' => [
                'expectation' => question_answer_evaluation_result::when_answer_is_correct(),
                'questionfrommaker' => 'two_of_four',
                'response' => ['One' => '1', 'Two' => '0', 'Three' => '1', 'Four' => '0'],
            ],
            'multi, incorrect response' => [
                'expectation' => question_answer_evaluation_result::when_answer_is_incorrect(),
                'questionfrommaker' => 'two_of_four',
                'response' => ['One' => '0', 'Two' => '1', 'Three' => '1', 'Four' => '0'],
            ],
        ];
    }
}
