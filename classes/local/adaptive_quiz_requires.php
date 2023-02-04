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

use mod_adaptivequiz\local\attempt\attempt;
use moodle_exception;
use question_bank;
use question_engine;

/**
 * The class contains checks for availability of an adaptive quiz activity which are based on the global site configs.
 *
 * @package    mod_adaptivequiz
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class adaptive_quiz_requires {

    /**
     * Checks whether the required behaviour is properly set in the site config.
     *
     * @throws moodle_exception
     */
    public function deferred_feedback_question_behaviour_is_enabled(): self {
        $behaviours = question_engine::get_archetypal_behaviours();
        if (!array_key_exists(attempt::ATTEMPTBEHAVIOUR, $behaviours)) {
            throw new moodle_exception(
                'activityavailabilitymissingquestionbehaviour',
                'adaptivequiz',
                '',
                attempt::ATTEMPTBEHAVIOUR
            );
        }

        $questionbankconfig = question_bank::get_config();
        if (in_array(attempt::ATTEMPTBEHAVIOUR, explode(',', $questionbankconfig->disabledbehaviours))) {
            throw new moodle_exception(
                'activityavailabilityquestionbehaviourdisabled',
                'adaptivequiz',
                '',
                attempt::ATTEMPTBEHAVIOUR
            );
        }

        return $this;
    }
}
