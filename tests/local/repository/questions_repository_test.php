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

namespace mod_adaptivequiz\local\repository;

use advanced_testcase;
use coding_exception;
use context;
use context_course;
use context_module;
use core_question\local\bank\question_version_status;
use core_tag_tag;
use PHPUnit\Framework\Attributes\CoversClass;
use question_bank;
use stdClass;

/**
 * A test class.
 *
 * @package    mod_adaptivequiz
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\mod_adaptivequiz\local\repository\questions_repository::class)]
final class questions_repository_test extends advanced_testcase {

    public function test_it_can_count_adaptive_questions_in_pool(): void {
        $this->resetAfterTest();

        $coregenerator = $this->getDataGenerator();
        /** @var \core_question_generator $questionsgenerator */
        $questionsgenerator = $coregenerator->get_plugin_generator('core_question');
        /** @var \mod_qbank_generator $qbankgenerator */
        $qbankgenerator = $coregenerator->get_plugin_generator('mod_qbank');

        $course = $coregenerator->create_course();
        $qbank = $qbankgenerator->create_instance(['course' => $course->id]);

        $qbankcm = get_coursemodule_from_instance('qbank', $qbank->id, $course->id, false, MUST_EXIST);
        $qbankcontext = context_module::instance($qbankcm->id);

        $questionscat1 = $questionsgenerator->create_question_category(['contextid' => $qbankcontext->id]);

        $question1 = $questionsgenerator->create_question('truefalse', null, ['category' => $questionscat1->id]);
        $questionsgenerator->create_question_tag(['questionid' => $question1->id, 'tag' => 'adpq_1']);

        $question2 = $questionsgenerator->create_question('truefalse', null, ['category' => $questionscat1->id]);
        $questionsgenerator->create_question_tag(['questionid' => $question2->id, 'tag' => 'adpq_2']);

        $question3 = $questionsgenerator->create_question('truefalse', null, ['category' => $questionscat1->id]);
        $questionsgenerator->create_question_tag(['questionid' => $question3->id, 'tag' => 'adpq_001']);

        $questionscat2 = $questionsgenerator->create_question_category(['contextid' => $qbankcontext->id]);

        $result = questions_repository::count_adaptive_questions_in_pool_with_level(
            [$questionscat1->id, $questionscat2->id],
            1
        );
        self::assertEquals(1, $result);

        $questionsgenerator->create_question('truefalse', null, ['category' => $questionscat2->id]);
        $questionsgenerator->create_question_tag(
            ['questionid' => $question2->id, 'tag' => 'truefalse_1']
        );

        $result = questions_repository::count_adaptive_questions_in_pool_with_level(
            [$questionscat1->id, $questionscat2->id],
            1
        );
        self::assertEquals(1, $result);

        $questionscat3 = $questionsgenerator->create_question_category(['contextid' => $qbankcontext->id]);

        $result = questions_repository::count_adaptive_questions_in_pool_with_level([$questionscat3->id], 1);
        $this->assertEquals(0, $result);
    }

    public function test_it_can_count_questions_number_per_difficulty(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        /** @var \core_question_generator $questionsgenerator */
        $questionsgenerator = $generator->get_plugin_generator('core_question');

        $course = $generator->create_course();

        $category1 = $questionsgenerator->create_question_category(
            ['contextid' => context_course::instance($course->id)->id]
        );
        $category2 = $questionsgenerator->create_question_category(
            ['contextid' => context_course::instance($course->id)->id]
        );

        $question1 = $questionsgenerator->create_question('truefalse', null, ['category' => $category1->id]);
        $question1tag = $this->create_question_tag($question1->id, 'adpq_1');

        $question2 = $questionsgenerator->create_question('truefalse', null, ['category' => $category2->id]);
        $question2tag = $this->create_question_tag($question2->id, 'adpq_4');

        $question3 = $questionsgenerator->create_question('truefalse', null, ['category' => $category1->id]);

        // Update one of the questions to populate several versions in the database.
        $questionsgenerator->update_question($question3, null, ['name' => 'New question version']);

        $question3tag = $this->create_question_tag($question3->id, 'adpq_4');

        $draftquestion = $questionsgenerator->create_question('truefalse', null,
            ['category' => $category1->id, 'status' => question_version_status::QUESTION_STATUS_DRAFT]);
        $draftquestiontag = $this->create_question_tag($draftquestion->id, 'adpq_5');

        $hiddenquestion = $questionsgenerator->create_question('truefalse', null,
            ['category' => $category1->id, 'status' => question_version_status::QUESTION_STATUS_HIDDEN]);
        $hiddenquestiontag = $this->create_question_tag($hiddenquestion->id, 'adpq_6');

        self::assertEquals([
                new questions_number_per_difficulty(1, 1),
                new questions_number_per_difficulty(4, 2),
            ],
            questions_repository::count_questions_number_per_difficulty(
                [$question1tag->id, $question2tag->id, $question3tag->id, $draftquestiontag->id, $hiddenquestiontag->id],
                [$category1->id, $category2->id]
            )
        );
    }

    public function test_it_finds_questions_with_tags(): void {
        $this->resetAfterTest();

        // It returns empty array when no tags or no question categories specified.
        self::assertEquals([], questions_repository::find_questions_with_tags([], [1, 2], []));
        self::assertEquals([], questions_repository::find_questions_with_tags([1, 2], [], []));

        $generator = $this->getDataGenerator();
        /** @var \core_question_generator $questionsgenerator */
        $questionsgenerator = $generator->get_plugin_generator('core_question');

        $course = $generator->create_course();

        $category1 = $questionsgenerator->create_question_category(
            ['contextid' => context_course::instance($course->id)->id]
        );
        $category2 = $questionsgenerator->create_question_category(
            ['contextid' => context_course::instance($course->id)->id]
        );

        $question1 = $questionsgenerator->create_question('truefalse', null,
            ['name' => 'Question 1', 'category' => $category1->id]);
        $question1tag = $this->create_question_tag($question1->id, 'adpq_1');

        $question2 = $questionsgenerator->create_question('truefalse', null,
            ['name' => 'Question 2', 'category' => $category2->id]);

        // Update one of the questions to populate several versions in the database.
        $question2 = $questionsgenerator->update_question($question2, null, ['name' => 'New question version']);

        $question2tag = $this->create_question_tag($question2->id, 'adpq_4');

        $draftquestion = $questionsgenerator->create_question('truefalse', null,
            ['category' => $category1->id, 'status' => question_version_status::QUESTION_STATUS_DRAFT]);
        $draftquestiontag = $this->create_question_tag($draftquestion->id, 'adpq_5');

        $hiddenquestion = $questionsgenerator->create_question('truefalse', null,
            ['category' => $category1->id, 'status' => question_version_status::QUESTION_STATUS_HIDDEN]);
        $hiddenquestiontag = $this->create_question_tag($hiddenquestion->id, 'adpq_6');

        $tagidlist = [$question1tag->id, $question2tag->id, $draftquestiontag->id, $hiddenquestiontag->id];
        $categoryidlist = [$category1->id, $category2->id];
        $result = questions_repository::find_questions_with_tags($tagidlist, $categoryidlist, []);

        $expectedresult = [];

        $expectedrecord = new stdClass;
        $expectedrecord->id = $question1->id;
        $expectedrecord->name = $question1->name;
        $expectedresult[$question1->id] = $expectedrecord;

        $expectedrecord = new stdClass;
        $expectedrecord->id = $question2->id;
        $expectedrecord->name = $question2->name;
        $expectedresult[$question2->id] = $expectedrecord;

        self::assertEquals($expectedresult, $result);

        // It can handle excluded questions.
        $result = questions_repository::find_questions_with_tags($tagidlist, $categoryidlist, [$question1->id]);

        $expectedresult = [];

        $expectedrecord = new stdClass;
        $expectedrecord->id = $question2->id;
        $expectedrecord->name = $question2->name;
        $expectedresult[$question2->id] = $expectedrecord;

        self::assertEquals($expectedresult, $result);
    }

    /**
     * Wraps creation of a question tag.
     *
     * @param int $questionid
     * @param string $tagname
     */
    private function create_question_tag(int $questionid, string $tagname): core_tag_tag {
        $question = question_bank::load_question($questionid);

        core_tag_tag::add_item_tag('core_question', 'question', $question->id,
            context::instance_by_id($question->contextid), $tagname);

        $tagbyname = core_tag_tag::get_by_name(0, $tagname);
        if (!$tagbyname) {
            throw new coding_exception('Could not find the tag by name, please, check data initialization steps.');
        }

        return $tagbyname;
    }
}
