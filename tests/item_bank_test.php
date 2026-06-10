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
#[CoversClass(\mod_adaptivequiz\item_bank::class)]
class item_bank_test extends advanced_testcase {

    public function test_it_assigns_question_banks_to_an_adaptive_quiz_instance(): void {
        global $DB;

        $this->resetAfterTest();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $qbank1 = $this->getDataGenerator()
            ->get_plugin_generator('mod_qbank')
            ->create_instance(['course' => $course1->id]);

        $qbank1cm = get_coursemodule_from_instance('qbank', $qbank1->id, 0, false, MUST_EXIST);
        $qbank1context = context_module::instance($qbank1cm->id);

        $qbank2 = $this->getDataGenerator()
            ->get_plugin_generator('mod_qbank')
            ->create_instance(['course' => $course2->id]);

        $qbank2cm = get_coursemodule_from_instance('qbank', $qbank2->id, 0, false, MUST_EXIST);
        $qbank2context = context_module::instance($qbank2cm->id);

        $adaptivequiz = $this->getDataGenerator()
            ->get_plugin_generator('mod_adaptivequiz')
            ->create_instance(['course' => $course1->id]);

        item_bank::assign_qbanks_to_adaptivequiz($adaptivequiz->id, [$qbank1->id, $qbank2->id]);
        $result = $DB->get_records(
            'adaptivequiz_qbank',
            ['adaptivequizid' => $adaptivequiz->id],
            '',
            'qbankid, qbankcontextid'
        );

        self::assertCount(2, $result);

        self::assertEqualsCanonicalizing(
            [
                (object) ['qbankid' => $qbank1->id, 'qbankcontextid' => $qbank1context->id],
                (object) ['qbankid' => $qbank2->id, 'qbankcontextid' => $qbank2context->id],
            ],
            $result
        );
    }

    public function test_it_unassigns_a_question_bank_from_an_adaptive_quiz_instance(): void {
        $this->resetAfterTest();

        $coregenerator = $this->getDataGenerator();
        /** @var mod_adaptivequiz_generator $adaptivequizgenerator */
        $adaptivequizgenerator = $coregenerator->get_plugin_generator('mod_adaptivequiz');
        $qbankgenerator = $coregenerator->get_plugin_generator('mod_qbank');

        $course1 = $coregenerator->create_course();
        $course2 = $coregenerator->create_course();

        $qbank1 = $qbankgenerator->create_instance(['course' => $course1->id]);
        $qbank2 = $qbankgenerator->create_instance(['course' => $course1->id]);
        $qbank3 = $qbankgenerator->create_instance(['course' => $course2->id]);
        $qbank4 = $qbankgenerator->create_instance(['course' => $course2->id]);

        $adaptivequiz1 = $adaptivequizgenerator->create_instance(['course' => $course1->id]);

        foreach ([$qbank1->id, $qbank2->id] as $qbankid) {
            $adaptivequizgenerator->create_link_with_question_bank([
                'adaptivequizid' => $adaptivequiz1->id,
                'qbankid' => $qbankid,
            ]);
        }

        $adaptivequiz2 = $adaptivequizgenerator->create_instance(['course' => $course2->id]);

        foreach ([$qbank3->id, $qbank4->id] as $qbankid) {
            $adaptivequizgenerator->create_link_with_question_bank([
                'adaptivequizid' => $adaptivequiz2->id,
                'qbankid' => $qbankid,
            ]);
        }

        $manager = $coregenerator->create_user();
        $coregenerator->enrol_user($manager->id, $course1->id, 'manager');

        self::setUser($manager);
        // End of setup.

        item_bank::unassign_qbank_from_adaptivequiz($adaptivequiz1->id, $qbank1->id);

        $result = item_bank::get_question_banks_assigned_to_adaptivequiz($adaptivequiz1->id);
        self::assertCount(1, $result);

        $remainingqbank = $result[array_key_first($result)];
        self::assertEquals($qbank2->id, $remainingqbank->id);

        // Assert other instances are not affected.
        $result = item_bank::get_question_banks_assigned_to_adaptivequiz($adaptivequiz2->id, 'id, name, course');
        self::assertCount(2, $result);

        self::assertEqualsCanonicalizing(
            [
                (object) ['id' => $qbank3->id, 'name' => $qbank3->name, 'course' => $course2->id],
                (object) ['id' => $qbank4->id, 'name' => $qbank4->name, 'course' => $course2->id],
            ],
            $result
        );
    }

    public function test_it_unassigns_a_single_question_category_from_an_adaptive_quiz_instance(): void {
        $this->resetAfterTest();

        $coregenerator = $this->getDataGenerator();
        /** @var mod_adaptivequiz_generator $adaptivequizgenerator */
        $adaptivequizgenerator = $coregenerator->get_plugin_generator('mod_adaptivequiz');
        /** @var \mod_qbank_generator $qbankgenerator */
        $qbankgenerator = $coregenerator->get_plugin_generator('mod_qbank');
        /** @var  \core_question_generator $questiongenerator */
        $questiongenerator = $coregenerator->get_plugin_generator('core_question');

        $course = $coregenerator->create_course();

        $qbank = $qbankgenerator->create_instance(['course' => $course->id]);
        $qbankcm = get_coursemodule_from_instance('qbank', $qbank->id, $course->id);
        $qbankcontext = context_module::instance($qbankcm->id);

        $qcat1 = $questiongenerator->create_question_category([
            'contextid' => $qbankcontext->id,
            'name' => 'My category 1',
        ]);

        $qcat2 = $questiongenerator->create_question_category([
            'contextid' => $qbankcontext->id,
            'name' => 'My category 2',
        ]);

        $qcat3 = $questiongenerator->create_question_category([
            'contextid' => $qbankcontext->id,
            'name' => 'My category 3',
        ]);

        $qcat4 = $questiongenerator->create_question_category([
            'contextid' => $qbankcontext->id,
            'name' => 'My category 4',
        ]);

        $adaptivequiz1 = $adaptivequizgenerator->create_instance(['course' => $course->id]);

        $adaptivequizgenerator->create_link_with_question_category([
            'adaptivequizid' => $adaptivequiz1->id,
            'qcategoryid' => $qcat1->id,
        ]);

        $adaptivequizgenerator->create_link_with_question_category([
            'adaptivequizid' => $adaptivequiz1->id,
            'qcategoryid' => $qcat2->id,
        ]);

        $adaptivequizgenerator->create_link_with_question_category([
            'adaptivequizid' => $adaptivequiz1->id,
            'qcategoryid' => $qcat3->id,
        ]);

        $adaptivequiz2 = $adaptivequizgenerator->create_instance(['course' => $course->id]);

        $adaptivequizgenerator->create_link_with_question_category([
            'adaptivequizid' => $adaptivequiz2->id,
            'qcategoryid' => $qcat4->id,
        ]);

        $manager = $coregenerator->create_user();
        $coregenerator->enrol_user($manager->id, $course->id, 'manager');

        self::setUser($manager);

        // End of setup.

        item_bank::unassign_question_category_from_adaptivequiz($adaptivequiz1->id, $qcat3->id);

        $result = item_bank::get_question_categories_assigned_to_adaptivequiz($adaptivequiz1->id, 'id, name');

        self::assertEqualsCanonicalizing(
            [
                (object) [
                    'id' => $qcat1->id,
                    'name' => $qcat1->name,
                    'cmid' => $qbankcm->id,
                    'qbankname' => $qbank->name,
                ],
                (object) [
                    'id' => $qcat2->id,
                    'name' => $qcat2->name,
                    'cmid' => $qbankcm->id,
                    'qbankname' => $qbank->name,
                ],
            ],
            $result
        );

        // Assert other instances are not affected.

        $result = item_bank::get_question_categories_assigned_to_adaptivequiz($adaptivequiz2->id, 'id, name');

        self::assertEqualsCanonicalizing(
            [
                (object) [
                    'id' => $qcat4->id,
                    'name' => $qcat4->name,
                    'cmid' => $qbankcm->id,
                    'qbankname' => $qbank->name,
                ],
            ],
            $result
        );
    }

    public function test_it_gets_question_banks_assigned_to_an_adaptive_quiz_instance(): void {
        $this->resetAfterTest();

        $coregenerator = $this->getDataGenerator();
        /** @var mod_adaptivequiz_generator $adaptivequizgenerator */
        $adaptivequizgenerator = $coregenerator->get_plugin_generator('mod_adaptivequiz');
        $qbankgenerator = $coregenerator->get_plugin_generator('mod_qbank');

        $course1 = $coregenerator->create_course();
        $course2 = $coregenerator->create_course();
        $course3 = $coregenerator->create_course();
        $course4 = $coregenerator->create_course();

        $qbank1 = $qbankgenerator->create_instance(['course' => $course1->id]);
        $qbank2 = $qbankgenerator->create_instance(['course' => $course2->id]);
        $qbank3 = $qbankgenerator->create_instance(['course' => $course3->id]);
        $qbankgenerator->create_instance(['course' => $course4->id]);

        $adaptivequiz = $adaptivequizgenerator->create_instance(['course' => $course1->id]);

        foreach ([$qbank1->id, $qbank2->id, $qbank3->id] as $qbankid) {
            $adaptivequizgenerator->create_link_with_question_bank([
                'adaptivequizid' => $adaptivequiz->id,
                'qbankid' => $qbankid,
            ]);
        }

        $result = item_bank::get_question_banks_assigned_to_adaptivequiz($adaptivequiz->id, 'id, name, course');

        self::assertCount(3, $result);

        self::assertEqualsCanonicalizing(
            [
                (object) ['id' => $qbank1->id, 'name' => $qbank1->name, 'course' => $course1->id],
                (object) ['id' => $qbank2->id, 'name' => $qbank2->name, 'course' => $course2->id],
                (object) ['id' => $qbank3->id, 'name' => $qbank3->name, 'course' => $course3->id],
            ],
            $result
        );

        // Test filtering by courses.

        $result = item_bank::get_question_banks_assigned_to_adaptivequiz(
            $adaptivequiz->id,
            'id, name, course',
            incourseid: $course1->id
        );

        self::assertCount(1, $result);

        self::assertEqualsCanonicalizing(
            [(object) ['id' => $qbank1->id, 'name' => $qbank1->name, 'course' => $course1->id]],
            $result
        );

        $result = item_bank::get_question_banks_assigned_to_adaptivequiz(
            $adaptivequiz->id,
            'id, name, course',
            notincourseid: $course1->id
        );

        self::assertCount(2, $result);

        self::assertEqualsCanonicalizing(
            [
                (object) ['id' => $qbank2->id, 'name' => $qbank2->name, 'course' => $course2->id],
                (object) ['id' => $qbank3->id, 'name' => $qbank3->name, 'course' => $course3->id],
            ],
            $result
        );
    }

    public function test_it_gets_question_categories_assigned_to_an_adaptive_quiz_instance(): void {
        $this->resetAfterTest();

        $coregenerator = $this->getDataGenerator();
        /** @var mod_adaptivequiz_generator $adaptivequizgenerator */
        $adaptivequizgenerator = $coregenerator->get_plugin_generator('mod_adaptivequiz');
        /** @var \mod_qbank_generator $qbankgenerator */
        $qbankgenerator = $coregenerator->get_plugin_generator('mod_qbank');
        /** @var  \core_question_generator $questiongenerator */
        $questiongenerator = $coregenerator->get_plugin_generator('core_question');

        $course1 = $coregenerator->create_course();
        $course2 = $coregenerator->create_course();

        $qbank1 = $qbankgenerator->create_instance(['course' => $course1->id]);
        $qbank1cm = get_coursemodule_from_instance('qbank', $qbank1->id, $course1->id);
        $qbank1context = context_module::instance($qbank1cm->id);

        $qbank2 = $qbankgenerator->create_instance(['course' => $course2->id]);
        $qbank2cm = get_coursemodule_from_instance('qbank', $qbank2->id, $course2->id);
        $qbank2context = context_module::instance($qbank2cm->id);

        $qcat1 = $questiongenerator->create_question_category([
            'contextid' => $qbank1context->id,
            'name' => 'My category 1',
        ]);

        $qcat2 = $questiongenerator->create_question_category([
            'contextid' => $qbank1context->id,
            'name' => 'My category 2',
        ]);

        $qcat3 = $questiongenerator->create_question_category([
            'contextid' => $qbank2context->id,
            'name' => 'My category 3',
        ]);

        $qcat4 = $questiongenerator->create_question_category([
            'contextid' => $qbank2context->id,
            'name' => 'My category 3',
        ]);

        $adaptivequiz1 = $adaptivequizgenerator->create_instance(['course' => $course1->id]);
        $adaptivequiz2 = $adaptivequizgenerator->create_instance(['course' => $course2->id]);

        $adaptivequizgenerator->create_link_with_question_category([
            'adaptivequizid' => $adaptivequiz1->id,
            'qcategoryid' => $qcat1->id,
        ]);

        $adaptivequizgenerator->create_link_with_question_category([
            'adaptivequizid' => $adaptivequiz1->id,
            'qcategoryid' => $qcat2->id,
        ]);

        $adaptivequizgenerator->create_link_with_question_category([
            'adaptivequizid' => $adaptivequiz2->id,
            'qcategoryid' => $qcat3->id,
        ]);

        $adaptivequizgenerator->create_link_with_question_category([
            'adaptivequizid' => $adaptivequiz2->id,
            'qcategoryid' => $qcat4->id,
        ]);

        // End of setup.

        $result = item_bank::get_question_categories_assigned_to_adaptivequiz($adaptivequiz1->id, 'id, name');

        self::assertEqualsCanonicalizing(
            [
                (object) [
                    'id' => $qcat1->id,
                    'name' => $qcat1->name,
                    'cmid' => $qbank1cm->id,
                    'qbankname' => $qbank1->name,
                ],
                (object) [
                    'id' => $qcat2->id,
                    'name' => $qcat2->name,
                    'cmid' => $qbank1cm->id,
                    'qbankname' => $qbank1->name,
                ],
            ],
            $result
        );
    }

    public function test_it_tells_whether_an_adaptive_quiz_instance_has_any_question_banks_assigned(): void {
        $this->resetAfterTest();

        $coregenerator = $this->getDataGenerator();
        /** @var mod_adaptivequiz_generator $adaptivequizgenerator */
        $adaptivequizgenerator = $coregenerator->get_plugin_generator('mod_adaptivequiz');
        /** @var \mod_qbank_generator $qbankgenerator */
        $qbankgenerator = $coregenerator->get_plugin_generator('mod_qbank');
        /** @var  \core_question_generator $questiongenerator */
        $questiongenerator = $coregenerator->get_plugin_generator('core_question');

        $course1 = $coregenerator->create_course();
        $course2 = $coregenerator->create_course();

        $qbank1 = $qbankgenerator->create_instance(['course' => $course1->id]);
        $qbank2 = $qbankgenerator->create_instance(['course' => $course2->id]);
        $qbank3 = $qbankgenerator->create_instance(['course' => $course2->id]);

        $adaptivequiz1 = $adaptivequizgenerator->create_instance(['course' => $course1->id]);

        $adaptivequiz2 = $adaptivequizgenerator->create_instance(['course' => $course1->id]);

        $adaptivequizgenerator->create_link_with_question_bank([
            'adaptivequizid' => $adaptivequiz2->id,
            'qbankid' => $qbank1->id,
        ]);

        $adaptivequizgenerator->create_link_with_question_bank([
            'adaptivequizid' => $adaptivequiz2->id,
            'qbankid' => $qbank2->id,
        ]);

        $adaptivequiz3 = $adaptivequizgenerator->create_instance(['course' => $course2->id]);

        $qbank3cm = get_coursemodule_from_instance('qbank', $qbank3->id, $course2->id);
        $qbank3context = context_module::instance($qbank3cm->id);

        $qcat = $questiongenerator->create_question_category([
            'contextid' => $qbank3context->id,
            'name' => 'My category 1',
        ]);

        $adaptivequizgenerator->create_link_with_question_category([
            'adaptivequizid' => $adaptivequiz3->id,
            'qcategoryid' => $qcat->id,
        ]);

        $adaptivequiz4 = $adaptivequizgenerator->create_instance(['course' => $course2->id]);

        $adaptivequizgenerator->create_link_with_question_bank([
            'adaptivequizid' => $adaptivequiz4->id,
            'qbankid' => $qbank2->id,
        ]);

        $adaptivequizgenerator->create_link_with_question_category([
            'adaptivequizid' => $adaptivequiz4->id,
            'qcategoryid' => $qcat->id,
        ]);

        // End of setup.

        $result = item_bank::adaptive_quiz_instance_has_question_banks_or_categories_linked($adaptivequiz1->id);
        self::assertFalse($result);

        $result = item_bank::adaptive_quiz_instance_has_question_banks_or_categories_linked($adaptivequiz2->id);
        self::assertTrue($result);

        $result = item_bank::adaptive_quiz_instance_has_question_banks_or_categories_linked($adaptivequiz3->id);
        self::assertTrue($result);

        $result = item_bank::adaptive_quiz_instance_has_question_banks_or_categories_linked($adaptivequiz4->id);
        self::assertTrue($result);
    }
}
