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

namespace mod_adaptivequiz;

use advanced_testcase;
use context_course;
use context_module;
use mod_adaptivequiz\local\attempt;
use mod_adaptivequiz\local\attempt\attempt_state;
use question_usage_by_activity;

/**
 * Functional tests for 'performing calculation steps' during an adaptive quiz session.
 *
 * These test don't cover a particular class, they are aimed at the entire process of taking an adaptive quiz. Perhaps, should be
 * converted to Behat tests?
 *
 * @package    mod_adaptivequiz
 * @copyright  2024 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class calculation_steps_test extends advanced_testcase {

    /**
     * Tries to simulate how an adaptive test runs and make assertions on the results of 'calculation steps'.
     *
     * @param string $stepsfixturesfile Name of the file with fixtures to get both expectations and input data from.
     * @param int $instance Number of instance form the relevant fixtures file.
     * @param string $stoppagereason
     * @param array $continueslots Slots where the test will simulate a 'continued attempt'.
     * @dataProvider data_for_test_calculation_steps
     */
    public function test_calculation_steps(
        string $stepsfixturesfile,
        int $instance,
        string $stoppagereason,
        array $continueslots
    ): void {
        global $DB, $SESSION;

        $this->resetAfterTest();

        $dataset = $this->dataset_from_files([
            'questionpool' => __DIR__ .'/fixtures/calcsteps/questionpool.csv',
            'instances' => __DIR__ .'/fixtures/calcsteps/instances.csv',
            'calcsteps' => __DIR__ ."/fixtures/calcsteps/$stepsfixturesfile",
        ]);

        $datagenerator = $this->getDataGenerator();
        $questionsgenerator = $datagenerator->get_plugin_generator('core_question');
        $modgenerator = $datagenerator->get_plugin_generator('mod_adaptivequiz');

        $course = $datagenerator->create_course();
        $user = $datagenerator->create_user();

        $qcategory = $questionsgenerator->create_question_category([
            'contextid' => context_course::instance($course->id)->id,
        ]);

        // Setup questions pool.
        $questionfixtures = $dataset->get_rows(['questionpool'])['questionpool'];
        foreach ($questionfixtures as $fixturesdata) {
            $diffquestionsgenerated = 0;
            while ($diffquestionsgenerated < $fixturesdata['questionsnum']) {
                $question = $questionsgenerator->create_question('truefalse', null, [
                    'category' => $qcategory->id,
                ]);

                $difftag = 'adpq_'. $fixturesdata['difficultylevel'];
                $questionsgenerator->create_question_tag([
                    'questionid' => $question->id,
                    'tag' => $difftag,
                ]);

                $diffquestionsgenerated++;
            }
        };

        $modfixturesarr = array_filter($dataset->get_rows(['instances'])['instances'], function (array $item) use ($instance) {
            return $item['instancenumber'] == $instance;
        });
        $modfixtures = $modfixturesarr[array_key_first($modfixturesarr)];

        $adaptivequiz = $modgenerator->create_instance([
            'course' => $course->id,
            'questionpool' => [$qcategory->id],
            'lowestlevel' => $modfixtures['lowestlevel'],
            'highestlevel' => $modfixtures['highestlevel'],
            'startinglevel' => $modfixtures['startinglevel'],
            'minimumquestions' => $modfixtures['minimumquestions'],
            'maximumquestions' => $modfixtures['maximumquestions'],
            'standarderror' => $modfixtures['standarderror'],
        ]);

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id, $course->id, false, MUST_EXIST);
        $modcontext = context_module::instance($cm->id);

        // Load fixtures for calculation steps.
        $calcstepsfixtures = $dataset->get_rows(['calcsteps'])['calcsteps'];

        $this->setUser($user);

        // This piece simulates what's happening in the fetchquestion class' destructor.
        $SESSION->adpqtagquestsum = [];
        foreach ($questionfixtures as $fixturesdata) {
            $SESSION->adpqtagquestsum[$fixturesdata['difficultylevel']] = $fixturesdata['questionsnum'];
        }

        // The test starts from here.
        do {
            // Emulate the page params.
            if (!isset($uniqueid)) {
                $uniqueid = 0;
            }

            // The below properties help to manage simulation of 'continued attempt'. This is the case when the user gets
            // the question administered, but doesn't answer it and leaves the page. Then they re-open the adaptive quiz and
            // got to the same question administered again.
            if (!isset($incontinueattemptsimulation)) {
                $incontinueattemptsimulation = false;
            }
            // This variable helps to track how many times the item was administered to user, but not answered. We want to
            // simulate getting back to the administered question twice at least.
            if (!isset($itemadministeredcount)) {
                $itemadministeredcount = 0;
            }
            if (!isset($simulatecontinueattempt)) {
                $simulatecontinueattempt = false;
            }

            $attempt = new attempt($adaptivequiz, $user->id);

            if (!$incontinueattemptsimulation) {
                $simulatecontinueattempt = isset($slot) && in_array($slot, $continueslots);
            }

            if ($incontinueattemptsimulation) {
                // Reset the simulation if the item has been administered twice already.
                if ($itemadministeredcount == 2) {
                    $incontinueattemptsimulation = false;
                    $itemadministeredcount = 0;
                    $simulatecontinueattempt = false;
                }
            }

            if ($simulatecontinueattempt) {
                $uniqueid = 0;

                $incontinueattemptsimulation = true;
            }

            // Emulates checking of whether a question answer was submitted.
            if (!empty($uniqueid)) {
                // The order of rows in the fixtures file corresponds to slots sequence.
                $calcstepsfixturesindex = $slot - 1;
                $attemptstepfixtures = $calcstepsfixtures[$calcstepsfixturesindex];

                $simulatedresponses = [
                    $slot => ['answer' => ($attemptstepfixtures['correctwrong'] == 'C')],
                ];

                $qubahelper = function (question_usage_by_activity $quba) use ($simulatedresponses): void {
                    $simulatedpostdata = $quba->prepare_simulated_post_data($simulatedresponses);

                    $time = time();
                    $quba->process_all_actions($time, $simulatedpostdata);
                    $quba->finish_all_questions($time);
                };

                cat_session::process_administered_item_result($uniqueid, $adaptivequiz, $attempt, $qubahelper);

                // Assertion. Reach out to the database directly.
                $expectation = [
                    'difficultysum' => $attemptstepfixtures['difficultysum'],
                    'standarderror' => $attemptstepfixtures['standarderrorraw'],
                    'measure' => $attemptstepfixtures['measureraw'],
                ];

                $attemptrecord = $DB->get_record('adaptivequiz_attempt', ['uniqueid' => $uniqueid], '*', MUST_EXIST);
                $stepsresult = [
                    'difficultysum' => $attemptrecord->difficultysum,
                    'standarderror' => $attemptrecord->standarderror,
                    'measure' => $attemptrecord->measure,
                ];

                self::assertEquals($expectation, $stepsresult);

                $attemptcompleted = $DB->record_exists('adaptivequiz_attempt',
                    ['uniqueid' => $uniqueid, 'attemptstate' => attempt_state::COMPLETED]);

                if ($attemptcompleted) {
                    $message = $DB->get_field('adaptivequiz_attempt', 'attemptstopcriteria', ['uniqueid' => $uniqueid],
                        MUST_EXIST);

                    break;
                }
            }

            cat_session::run_item_administration($uniqueid, $adaptivequiz, $modcontext, $attempt);

            $message = $attempt->get_status();

            $attemptcompleted = !empty($message);
            if ($attemptcompleted) {
                break;
            }

            $slot = $attempt->get_question_slot_number();

            $quba = $attempt->get_quba();

            // Emulate what happens in the form?
            $uniqueid = $quba->get_id();

            if ($incontinueattemptsimulation) {
                $itemadministeredcount++;
            }

        } while (true); // The loop ends with explicit break's inside.

        self::assertEquals($stoppagereason, $message);

        $attemptrecord = $DB->get_record('adaptivequiz_attempt', ['uniqueid' => $uniqueid], '*', MUST_EXIST);

        $expectedquestionsattempted = count($calcstepsfixtures);
        self::assertEquals($expectedquestionsattempted, $attemptrecord->questionsattempted);

        // For a completed attempt, algorithm's parameters should be kept as the last fixture's value.
        $lastattemptstepfixtures = $calcstepsfixtures[count($calcstepsfixtures) - 1];

        $expectation = [
            'difficultysum' => $lastattemptstepfixtures['difficultysum'],
            'standarderror' => $lastattemptstepfixtures['standarderrorraw'],
            'measure' => $lastattemptstepfixtures['measureraw'],
        ];

        $laststepsresult = [
            'difficultysum' => $attemptrecord->difficultysum,
            'standarderror' => $attemptrecord->standarderror,
            'measure' => $attemptrecord->measure,
        ];

        self::assertEquals($expectation, $laststepsresult);
    }

    /**
     * A data provider method.
     *
     * @return array
     */
    public static function data_for_test_calculation_steps(): array {
        return [
            'unable to fetch a question for level 14' => [
                'stepsfixturesfile' => '1.csv',
                'instancenumber' => 1,
                'stoppagereason' => 'Unable to fetch a question for level 14',
                'continueslots' => [],
            ],
            'calculated standard error 11 is within the limits' => [
                'stepsfixturesfile' => '2.csv',
                'instancenumber' => 1,
                'stoppagereason' => 'Calculated standard error of 11 is within the limits imposed by the activity 11',
                'continueslots' => [],
            ],
            'maximum number of questions attempted 1' => [
                'stepsfixturesfile' => '3.csv',
                'instancenumber' => 2,
                'stoppagereason' => 'Maximum number of questions attempted',
                'continueslots' => [],
            ],
            'unable to fetch a question for level 1' => [
                'stepsfixturesfile' => '4.csv',
                'instancenumber' => 1,
                'stoppagereason' => 'Unable to fetch a question for level 1',
                'continueslots' => [],
            ],
            'maximum number of questions attempted 2' => [
                'stepsfixturesfile' => '5.csv',
                'instancenumber' => 3,
                'stoppagereason' => 'Maximum number of questions attempted',
                'continueslots' => [],
            ],
            'maximum number of questions attempted 3' => [
                'stepsfixturesfile' => '6.csv',
                'instancenumber' => 4,
                'stoppagereason' => 'Maximum number of questions attempted',
                'continueslots' => [],
            ],
            'calculated standard error 8 is within the limits' => [
                'stepsfixturesfile' => '7.csv',
                'instancenumber' => 5,
                'stoppagereason' => 'Calculated standard error of 8 is within the limits imposed by the activity 8',
                'continueslots' => [],
            ],
            'maximum number of questions attempted 4' => [
                'stepsfixturesfile' => '8.csv',
                'instancenumber' => 6,
                'stoppagereason' => 'Maximum number of questions attempted',
                'continueslots' => [],
            ],
            'unable to fetch a question for level 15, continued attempt' => [
                'stepsfixturesfile' => '1.csv',
                'instancenumber' => 1,
                'stoppagereason' => 'Unable to fetch a question for level 14',
                'continueslots' => [4, 8, 14, 19],
            ],
        ];
    }
}
