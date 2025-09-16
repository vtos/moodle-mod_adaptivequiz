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

use coding_exception;
use core\persistent;
use dml_missing_record_exception;
use mod_adaptivequiz\local\attempt\attempt_state;

/**
 * An implementation of Moodle's persistent class for attempts.
 *
 * IMPORTANT: currently, the class is used as a read model only. Its capabilities to store the modified attempt's properties
 * are blocked as far as allowed by Moodle's persistent implementation.
 *
 * @package    mod_adaptivequiz
 * @copyright  2025 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt extends persistent {

    /** @var string */
    const TABLE = 'adaptivequiz_attempt';

    /**
     * Implements the parent's method.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'instance' => [
                'type' => PARAM_INT,
            ],
            'userid' => [
                'type' => PARAM_INT,
            ],
            'uniqueid' => [
                'type' => PARAM_INT,
            ],
            'attemptstate' => [
                'type' => PARAM_ALPHANUMEXT,
                'choices' => [
                    attempt_state::IN_PROGRESS,
                    attempt_state::COMPLETED,
                ],
            ],
            'attemptstopcriteria' => [
                'type' => PARAM_TEXT,
                'null' => NULL_ALLOWED,
                'default' => '',
            ],
            'questionsattempted' => [
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
     * A hook implementation.
     *
     * As soon as the class is used as a read model, this is used to prevent any changes in the attempt object stored.
     */
    protected function before_create() {
        throw new coding_exception('The attempt persistent class must not be used to store the attempt\'s state.');
    }

    /**
     * A hook implementation.
     *
     * As soon as the class is used as a read model, this is used to prevent any changes in the attempt object stored.
     */
    protected function before_update() {
        throw new coding_exception('The attempt persistent class must not be used to store the attempt\'s state.');
    }

    /**
     * A hook implementation.
     *
     * As soon as the class is used as a read model, this is used to prevent deletion of attempts.
     */
    protected function before_delete() {
        throw new coding_exception('The attempt persistent class must not be used to delete attempts.');
    }

    /**
     * Returns an attempt with the highest score (ability measure value) for the user.
     *
     * The caller assumes the user has made at least one attempt, a DML exception will be thrown if no attempts found at all.
     *
     * @param int $userid
     * @param int $adaptivequizid
     */
    public static function get_with_highest_score_for_user(int $userid, int $adaptivequizid): self {
        $attempts = self::get_records([
            'userid' => $userid,
            'instance' => $adaptivequizid,
            'attemptstate' => attempt_state::COMPLETED,
        ], 'measure', 'DESC', 0, 1);

        if (!$attempts) {
            throw new dml_missing_record_exception(self::TABLE);
        }

        $keyfirst = array_key_first($attempts);

        return $attempts[$keyfirst];
    }

    /**
     * Returns the first attempt the user has made.
     *
     * The caller assumes the user has made at least one attempt, a DML exception will be thrown if no attempts found at all.
     *
     * @param int $userid
     * @param int $adaptivequizid
     */
    public static function get_first_for_user(int $userid, int $adaptivequizid): self {
        $attempts = self::get_records([
            'userid' => $userid,
            'instance' => $adaptivequizid,
            'attemptstate' => attempt_state::COMPLETED,
        ], 'timecreated', 'ASC', 0, 1);

        if (!$attempts) {
            throw new dml_missing_record_exception(self::TABLE);
        }

        $keyfirst = array_key_first($attempts);

        return $attempts[$keyfirst];
    }

    /**
     * Returns the last attempt the user has made.
     *
     * The caller assumes the user has made at least one attempt, a DML exception will be thrown if no attempts found at all.
     *
     * @param int $userid
     * @param int $adaptivequizid
     */
    public static function get_last_for_user(int $userid, int $adaptivequizid): self {
        $attempts = self::get_records([
            'userid' => $userid,
            'instance' => $adaptivequizid,
            'attemptstate' => attempt_state::COMPLETED,
        ], 'timecreated', 'DESC', 0, 1);

        if (!$attempts) {
            throw new dml_missing_record_exception(self::TABLE);
        }

        $keyfirst = array_key_first($attempts);

        return $attempts[$keyfirst];
    }
}
