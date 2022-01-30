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
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_adaptivequiz\local;

use advanced_testcase;
use context_course;
use core_question_generator;
use mod_adaptivequiz\local\repository\questions_repository;

final class questions_repository_test extends advanced_testcase
{
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

        $questionscat2 = $questionsgenerator->create_question_category(
            ['contextid' => context_course::instance($course->id)->id,]
        );

        $this->assertEquals(2, questions_repository::count_adaptive_questions_in_pool(
            [$questionscat1->id, $questionscat2->id,]
        ));

        $questionsgenerator->create_question('truefalse', null, ['category' => $questionscat2->id]);

        $questionsgenerator->create_question_tag(
            ['questionid' => $question2->id, 'tag' => 'truefalse_1',]
        );

        $this->assertEquals(2, questions_repository::count_adaptive_questions_in_pool(
            [$questionscat1->id, $questionscat2->id,]
        ));

        $questionscat3 = $questionsgenerator->create_question_category(
            ['contextid' => context_course::instance($course->id)->id,]
        );

        $this->assertEquals(0, questions_repository::count_adaptive_questions_in_pool(
            [$questionscat3->id,]
        ));
    }
}
