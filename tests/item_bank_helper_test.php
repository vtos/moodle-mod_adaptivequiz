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

use advanced_testcase;
use context_module;
use mod_adaptivequiz_generator;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * A test class.
 *
 * @package    mod_adaptivequiz
 * @copyright  2026 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\mod_adaptivequiz\item_bank_helper::class)]
class item_bank_helper_test extends advanced_testcase {

    public function test_it_counts_questions_with_the_given_difficulty_level_for_an_adaptivequiz_instance(): void {
        $this->resetAfterTest();

        $coregenerator = $this->getDataGenerator();
        /** @var \mod_adaptivequiz_generator $adaptivequizgenerator */
        $adaptivequizgenerator = $coregenerator->get_plugin_generator('mod_adaptivequiz');
        /** @var  \core_question_generator $questionsgenerator */
        $questionsgenerator = $coregenerator->get_plugin_generator('core_question');
        /** @var \mod_qbank_generator $qbankgenerator */
        $qbankgenerator = $coregenerator->get_plugin_generator('mod_qbank');

        $course1 = $coregenerator->create_course();
        $course2 = $coregenerator->create_course();

        $qbank1 = $qbankgenerator->create_instance(['course' => $course1->id]);
        $qbank2 = $qbankgenerator->create_instance(['course' => $course2->id]);
        $qbank3 = $qbankgenerator->create_instance(['course' => $course2->id]);

        $adaptivequiz = $adaptivequizgenerator->create_instance(['course' => $course1->id]);

        foreach ([$qbank1->id, $qbank2->id] as $qbankid) {
            $adaptivequizgenerator->create_link_with_question_bank([
                'adaptivequizid' => $adaptivequiz->id,
                'qbankid' => $qbankid,
            ]);
        }

        $qbank3cm = get_coursemodule_from_instance('qbank', $qbank3->id, $course2->id);
        $qbank3context = context_module::instance($qbank3cm->id);

        $qbank3cat = $questionsgenerator->create_question_category([
            'contextid' => $qbank3context->id,
            'name' => 'My category 1',
        ]);

        $adaptivequizgenerator->create_link_with_question_category([
            'adaptivequizid' => $adaptivequiz->id,
            'qcategoryid' => $qbank3cat->id,
        ]);

        $qbank1cm = get_coursemodule_from_instance('qbank', $qbank1->id, 0, false, MUST_EXIST);
        $qbank1context = context_module::instance($qbank1cm->id);
        $qbank1cat = question_get_default_category($qbank1context->id);

        $question1 = $questionsgenerator->create_question('truefalse', null, ['category' => $qbank1cat->id]);
        $questionsgenerator->create_question_tag(['questionid' => $question1->id, 'tag' => 'adpq_1']);

        $question2 = $questionsgenerator->create_question('truefalse', null, ['category' => $qbank1cat->id]);
        $questionsgenerator->create_question_tag(['questionid' => $question2->id, 'tag' => 'adpq_2']);

        $question3 = $questionsgenerator->create_question('truefalse', null, ['category' => $qbank1cat->id]);
        $questionsgenerator->create_question_tag(['questionid' => $question3->id, 'tag' => 'adpq_001']);

        $qbank2cm = get_coursemodule_from_instance('qbank', $qbank2->id, 0, false, MUST_EXIST);
        $qbank2context = context_module::instance($qbank2cm->id);
        $qbank2cat = question_get_default_category($qbank2context->id);

        $question4 = $questionsgenerator->create_question('truefalse', null, ['category' => $qbank2cat->id]);
        $questionsgenerator->create_question_tag(['questionid' => $question4->id, 'tag' => 'truefalse_1']);

        $question5 = $questionsgenerator->create_question('truefalse', null, ['category' => $qbank2cat->id]);
        $questionsgenerator->create_question_tag(['questionid' => $question5->id, 'tag' => 'adpq_3']);

        $question6 = $questionsgenerator->create_question('truefalse', null, ['category' => $qbank3cat->id]);
        $questionsgenerator->create_question_tag(['questionid' => $question6->id, 'tag' => 'adpq_1']);

        $question7 = $questionsgenerator->create_question('truefalse', null, ['category' => $qbank3cat->id]);
        $questionsgenerator->create_question_tag(['questionid' => $question7->id, 'tag' => 'adpq_3']);

        // End of setup.

        $result = item_bank_helper::count_adaptivequiz_questions_with_difficulty_level($adaptivequiz->id, 1);
        self::assertEquals(2, $result);

        $result = item_bank_helper::count_adaptivequiz_questions_with_difficulty_level($adaptivequiz->id, 2);
        self::assertEquals(1, $result);

        $result = item_bank_helper::count_adaptivequiz_questions_with_difficulty_level($adaptivequiz->id, 3);
        self::assertEquals(2, $result);
    }

    public function test_it_gets_question_categories_from_item_bank(): void {
        $this->resetAfterTest();

        $coregenerator = $this->getDataGenerator();
        /** @var \mod_adaptivequiz_generator $adaptivequizgenerator */
        $adaptivequizgenerator = $coregenerator->get_plugin_generator('mod_adaptivequiz');
        /** @var  \core_question_generator $questiongenerator */
        $questiongenerator = $coregenerator->get_plugin_generator('core_question');
        /** @var \mod_qbank_generator $qbankgenerator */
        $qbankgenerator = $coregenerator->get_plugin_generator('mod_qbank');

        $course = $coregenerator->create_course();

        $qbank1 = $qbankgenerator->create_instance(['course' => $course->id]);
        $qbank2 = $qbankgenerator->create_instance(['course' => $course->id]);

        $qbank1cm = get_coursemodule_from_instance('qbank', $qbank1->id, $course->id, false, MUST_EXIST);
        $qbank1context = context_module::instance($qbank1cm->id);
        $qbankcat1 = question_get_default_category($qbank1context->id);
        $qbankcat2 = question_get_top_category($qbank1context->id);

        $qbank2cm = get_coursemodule_from_instance('qbank', $qbank2->id, $course->id, false, MUST_EXIST);
        $qbank2context = context_module::instance($qbank2cm->id);
        $qbankcat3 = question_get_default_category($qbank2context->id);
        $qbankcat4 = question_get_top_category($qbank2context->id);

        // Contains 'single' question categories linked to the activity.
        $qbank3 = $qbankgenerator->create_instance(['course' => $course->id]);
        $qbank3cm = get_coursemodule_from_instance('qbank', $qbank3->id, $course->id, false, MUST_EXIST);
        $qbank3context = context_module::instance($qbank3cm->id);

        $singlecat1 = $questiongenerator->create_question_category([
            'contextid' => $qbank3context->id,
            'name' => 'Single category 1',
        ]);

        $singlecat2 = $questiongenerator->create_question_category([
            'contextid' => $qbank3context->id,
            'name' => 'Single category 2',
        ]);

        $adaptivequiz = $adaptivequizgenerator->create_instance(['course' => $course->id]);

        $adaptivequizgenerator->create_link_with_question_bank([
            'adaptivequizid' => $adaptivequiz->id,
            'qbankid' => $qbank1->id,
        ]);

        $adaptivequizgenerator->create_link_with_question_bank([
            'adaptivequizid' => $adaptivequiz->id,
            'qbankid' => $qbank2->id,
        ]);

        $adaptivequizgenerator->create_link_with_question_category([
            'adaptivequizid' => $adaptivequiz->id,
            'qcategoryid' => $singlecat1->id,
        ]);

        $adaptivequizgenerator->create_link_with_question_category([
            'adaptivequizid' => $adaptivequiz->id,
            'qcategoryid' => $singlecat2->id,
        ]);

        // End of setup.

        $result = item_bank_helper::get_question_categories($adaptivequiz->id);

        $expect = [$qbankcat1->id, $qbankcat2->id, $qbankcat3->id, $qbankcat4->id, $singlecat1->id, $singlecat2->id];
        self::assertEqualsCanonicalizing($expect, $result);
    }
}
