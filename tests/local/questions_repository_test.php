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
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_adaptivequiz\local;

use advanced_testcase;
use context_course;
use core_question_generator;
use mod_adaptivequiz\local\repository\questions_repository;

final class questions_repository_test extends advanced_testcase {

    /**
     * @test
     */
    public function it_can_count_adaptive_questions_in_pool(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        /** @var  core_question_generator $questionsgenerator */
        $questionsgenerator = $generator->get_plugin_generator('core_question');

        $course = $generator->create_course();

        $questionscat1 = $questionsgenerator->create_question_category(
            ['contextid' => context_course::instance($course->id)->id,]
        );

        $question1 = $questionsgenerator->create_question('truefalse', null, ['category' => $questionscat1->id]);
        $questionsgenerator->create_question_tag(
            ['questionid' => $question1->id, 'tag' => 'adpq_1',]
        );
        $question2 = $questionsgenerator->create_question('truefalse', null, ['category' => $questionscat1->id]);
        $questionsgenerator->create_question_tag(
            ['questionid' => $question2->id, 'tag' => 'adpq_2',]
        );
        $question3 = $questionsgenerator->create_question('truefalse', null, ['category' => $questionscat1->id]);
        $questionsgenerator->create_question_tag(
            ['questionid' => $question3->id, 'tag' => 'adpq_001',]
        );

        $questionscat2 = $questionsgenerator->create_question_category(
            ['contextid' => context_course::instance($course->id)->id,]
        );

        $this->assertEquals(1, questions_repository::count_adaptive_questions_in_pool_with_level(
            [$questionscat1->id, $questionscat2->id,], 1
        ));

        $questionsgenerator->create_question('truefalse', null, ['category' => $questionscat2->id]);

        $questionsgenerator->create_question_tag(
            ['questionid' => $question2->id, 'tag' => 'truefalse_1',]
        );

        $this->assertEquals(1, questions_repository::count_adaptive_questions_in_pool_with_level(
            [$questionscat1->id, $questionscat2->id,], 1
        ));

        $questionscat3 = $questionsgenerator->create_question_category(
            ['contextid' => context_course::instance($course->id)->id,]
        );

        $this->assertEquals(0, questions_repository::count_adaptive_questions_in_pool_with_level(
            [$questionscat3->id,], 1
        ));
    }
}
