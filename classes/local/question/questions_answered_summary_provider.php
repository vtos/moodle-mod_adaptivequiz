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

use question_usage_by_activity;

/**
 * A service to collect results of answering questions when making attempt on adaptive quiz.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class questions_answered_summary_provider {

    /**
     * @var question_usage_by_activity $quba
     */
    private $quba;

    /**
     * The constructor.
     *
     * @param question_usage_by_activity $quba
     */
    public function __construct(question_usage_by_activity $quba) {
        $this->quba = $quba;
    }

    /**
     * Uses quba to obtain information about how the questions were answered when making attempt on adaptive quiz.
     *
     * @return questions_answered_summary
     */
    public function collect_summary(): questions_answered_summary {
        $numberofwronganswers = 0;
        $numberofcorrectanswers = 0;

        $slots = $this->quba->get_slots();
        foreach ($slots as $slot) {
            $mark = $this->quba->get_question_mark($slot);
            if (is_null($mark)) {
                $numberofwronganswers++;

                continue;
            }
            if ($mark <= 0.0) {
                $numberofwronganswers++;

                continue;
            }

            $numberofcorrectanswers++;
        }

        return questions_answered_summary::from_integers($numberofwronganswers, $numberofcorrectanswers);
    }
}
