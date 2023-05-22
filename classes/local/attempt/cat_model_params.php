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

use core\persistent;
use dml_missing_record_exception;
use stdClass;

/**
 * Defines CAT algorithm parameters that are stored during performing an attempt.
 *
 * Utilizes the core's persistent functionality.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cat_model_params extends persistent {

    public const TABLE = 'adaptivequiz_cat_params';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'attempt' => [
                'type' => PARAM_INT,
            ],
            'difficultysum' => [
                'type' => PARAM_FLOAT,
            ],
            'standarderror' => [
                'type' => PARAM_FLOAT,
            ],
            'measure' => [
                'type' => PARAM_FLOAT,
            ],
        ];
    }

    /**
     * Updates the entity with a set of parameters.
     *
     * The parameters are the results of calculation received from the algorithm.
     *
     * @param cat_calculation_steps_result $calcstepsresult
     * @return void
     */
    public function update_with_calculation_steps_result(cat_calculation_steps_result $calcstepsresult): void {
        $this->set_many([
            'difficultysum' => (float) $this->get('difficultysum') + $calcstepsresult->logit()->as_float(),
            'standarderror' => $calcstepsresult->standard_error(),
            'measure' => $calcstepsresult->measure(),
        ]);
        $this->update();
    }

    /**
     * Updates entity with the resulted standard error value on attempt completion.
     *
     * @param float $standarderror
     */
    public function update_when_attempt_completed(float $standarderror): void {
        $this->set('standarderror', $standarderror);
        $this->update();
    }

    /**
     * Instantiates an object for a fresh attempt.
     *
     * @param int $attemptid
     * @return self
     */
    public static function create_new_for_attempt(int $attemptid): self {
        $data = new stdClass();
        $data->attempt = $attemptid;
        $data->difficultysum = 0;
        $data->standarderror = 999;
        $data->measure = 0;

        $params = new self(0, $data);
        $params->create();

        return $params;
    }

    /**
     * instantiates an object fot the given attempt.
     *
     * Reaches out to the database to fetch the corresponding record.
     *
     * @param int $attemptid
     * @return self
     * @throws dml_missing_record_exception
     */
    public static function for_attempt(int $attemptid): self {
        $params = self::get_record(['attempt' => $attemptid]);
        if (!$params) {
            throw new dml_missing_record_exception(self::TABLE);
        }

        return $params;
    }
}
