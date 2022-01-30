<?php
// This file is not a part of Moodle - http://moodle.org/.
// This is a non-core contributed module. The module had been created
// as a collaborative effort between Middlebury College and Remote Learner.
// Later on it was adopted by a developer Vitaly Potenko to keep it compatible
// with new Moodle versions and let it acquire new features.
//
// This is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// The GNU General Public License can be seen at <http://www.gnu.org/licenses/>.

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

final class questions_repository
{
    /**
     * Counts all questions in the pool tagged as 'adaptive'.
     *
     * @param int[] $qcategoryidlist A list of id of questions categories.
     */
    public static function count_adaptive_questions_in_pool(array $qcategoryidlist): int {
        if (!$raw = question_finder::get_instance()->get_questions_from_categories($qcategoryidlist,'')) {
            return 0;
        }

        $questionstags = core_tag_tag::get_items_tags('core_question', 'question', array_keys($raw));

        // Filter non-'adaptive' tags out
        $questionstags = array_map(function(array $tags) {
            return array_filter($tags, function(core_tag_tag $tag) {
                return substr($tag->name, 0, strlen(ADAPTIVEQUIZ_QUESTION_TAG)) == ADAPTIVEQUIZ_QUESTION_TAG;
            });
        }, $questionstags);

        // Filter empty tags arrays out
        $questionstags = array_filter($questionstags, function(array $tags) {
            return !empty($tags);
        });

        return count($questionstags);
    }
}
