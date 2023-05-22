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

/**
 * Unit tests for the CAT model parameters class.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_adaptivequiz\local\attempt\cat_model_params
 */
class cat_model_params_test extends \advanced_testcase {

    public function test_it_can_be_manipulated_during_a_quiz_attempt(): void {
        self::resetAfterTest();

        // Prepare the background.
        $course = $this->getDataGenerator()->create_course();
        $adaptivequiz = $this->getDataGenerator()
            ->get_plugin_generator('mod_adaptivequiz')
            ->create_instance([
                'highestlevel' => 10,
                'lowestlevel' => 1,
                'standarderror' => 5,
                'course' => $course->id,
            ]);

        $user = $this->getDataGenerator()->create_user();

        $attempt = attempt::create($adaptivequiz, $user->id);

        // Given an instance of CAT model parameters was created.
        $params = cat_model_params::create_new_for_attempt($attempt->read_attempt_data()->id);

        // When updating it with results of calculations from the algorithm.
        $params->update_with_calculation_steps_result(cat_calculation_steps_result::from_floats(-2.5365500, 0, 0));

        // Then the parameters are available as values of the entity's properties.
        self::assertEquals(-2.5365500, $params->get('difficultysum'));
        self::assertEquals(0, $params->get('standarderror'));
        self::assertEquals(0, $params->get('measure'));

        // And the parameters can be re-fetched from the database.
        $params = cat_model_params::for_attempt($attempt->read_attempt_data()->id);
        self::assertEquals(-2.5365500, $params->get('difficultysum'));
        self::assertEquals(0, $params->get('standarderror'));
        self::assertEquals(0, $params->get('measure'));

        // Given the attempt continues.
        // This assumes we make use of the same $param variable above.

        // When updating it with results of calculations from the algorithm again.
        $params->update_with_calculation_steps_result(cat_calculation_steps_result::from_floats(-1.0986123, 0, 4.30391));

        // Then the parameters contain the expected values.
        self::assertEquals(-3.6351623, $params->get('difficultysum'));
        self::assertEquals(0, $params->get('standarderror'));
        self::assertEquals(4.30391, $params->get('measure'));

        // Given the attempt continues.
        // This assumes we make use of the same $param variable above.

        // When updating it when the attempt is completed.
        $params->update_when_attempt_completed(1.45095);

        // Then the parameters contain the expected values.
        self::assertEquals(-3.6351623, $params->get('difficultysum'));
        self::assertEquals(1.45095, $params->get('standarderror'));
        self::assertEquals(4.30391, $params->get('measure'));

        // And the parameters can be re-fetched from the database.
        $params = cat_model_params::for_attempt($attempt->read_attempt_data()->id);
        self::assertEquals(-3.6351623, $params->get('difficultysum'));
        self::assertEquals(1.45095, $params->get('standarderror'));
        self::assertEquals(4.30391, $params->get('measure'));
    }
}
