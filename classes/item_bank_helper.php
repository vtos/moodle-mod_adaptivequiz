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

use mod_adaptivequiz\local\repository\questions_repository;

/**
 * Provides methods to read information from item banks.
 *
 * The purpose of this class is to provide methods for the item administration context only.
 *
 * @package    mod_adaptivequiz
 * @copyright  2026 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_bank_helper {

    /**
     * Counts questions with the given difficulty level.
     *
     * @param int $adaptivequizid ID of the 'adaptivequiz' instance.
     * @param int $level The difficulty level to search questions by.
     * @return int The number of questions found.
     */
    public static function count_adaptivequiz_questions_with_difficulty_level(int $adaptivequizid, int $level): int {
        $qcategoryidlist = self::get_question_categories($adaptivequizid);
        if (!$qcategoryidlist) {
            return 0;
        }

        return questions_repository::count_adaptive_questions_in_pool_with_level($qcategoryidlist, $level);
    }

    /**
     * Gets the list of question categories in the instance's item bank.
     *
     * @param int $adaptivequizid ID of the 'adaptivequiz' instance.
     * @return int[] A list of question category ID.
     */
    public static function get_question_categories(int $adaptivequizid): array {
        global $DB;

        // Single categories.
        $return = $DB->get_fieldset('adaptivequiz_question', 'questioncategory', ['instance' => $adaptivequizid]);

        // Entire question banks.

        $sql = "SELECT qc.id
                  FROM {adaptivequiz_qbank} aqb
                  JOIN {question_categories} qc ON aqb.qbankcontextid = qc.contextid
                 WHERE aqb.adaptivequizid = ?";
        $params = [$adaptivequizid];

        $return = array_merge($return, $DB->get_fieldset_sql($sql, $params));
        $return = array_unique($return);

        return $return;
    }
}
