<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * A class to wrap all database queries which are specific to questions and their related data. Normally should contain
 * only static methods to call.
 *
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_adaptivequiz\local\repository;

use core_tag_tag;
use question_finder;

final class questions_repository {
    /**
     * Counts all questions in the pool tagged as 'adaptive' with a certain difficulty level.
     *
     * @param int[] $qcategoryidlist A list of id of questions categories.
     * @param int $level Question difficulty which is contained in the question's tag.
     */
    public static function count_adaptive_questions_in_pool_with_level(array $qcategoryidlist, int $level): int {
        if (!$raw = question_finder::get_instance()->get_questions_from_categories($qcategoryidlist, '')) {
            return 0;
        }

        $questionstags = core_tag_tag::get_items_tags('core_question', 'question', array_keys($raw));

        // Filter 'non-adaptive' and level mismatching tags out.
        $questionstags = array_map(function(array $tags) use ($level) {
            return array_filter($tags, function(core_tag_tag $tag) use ($level) {
                return substr($tag->name, strlen(ADAPTIVEQUIZ_QUESTION_TAG)) === (string)$level;
            });
        }, $questionstags);

        // Filter empty tags arrays out.
        $questionstags = array_filter($questionstags, function(array $tags) {
            return !empty($tags);
        });

        return count($questionstags);
    }
}
