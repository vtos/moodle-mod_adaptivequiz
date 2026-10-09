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
use cm_info;
use context_module;
use core\exception\invalid_parameter_exception;
use mod_adaptivequiz_generator;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;

/**
 * A test class.
 *
 * @package    mod_adaptivequiz
 * @copyright  2026 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\mod_adaptivequiz\item_bank::class)]
final class item_bank_test extends advanced_testcase {
    /**
     * Tests a Moodle question bank can be assigned to an adaptive quiz instance to build up the item bank.
     */
    public function test_it_assigns_question_banks_to_an_adaptive_quiz_instance(): void {
        global $DB;

        $this->resetAfterTest();

        $coregenerator = $this->getDataGenerator();
        /** @var mod_adaptivequiz_generator $adaptivequizgenerator */
        $adaptivequizgenerator = $coregenerator->get_plugin_generator('mod_adaptivequiz');
        $qbankgenerator = $coregenerator->get_plugin_generator('mod_qbank');

        $course1 = $coregenerator->create_course();
        $course2 = $coregenerator->create_course();

        $qbank1 = $qbankgenerator->create_instance(['course' => $course1->id]);

        $qbank1cm = get_coursemodule_from_instance(modulename: 'qbank', instance: $qbank1->id, strictness: MUST_EXIST);
        $qbank1context = context_module::instance($qbank1cm->id);

        $qbank2 = $qbankgenerator->create_instance(['course' => $course2->id]);

        $qbank2cm = get_coursemodule_from_instance(modulename: 'qbank', instance: $qbank2->id, strictness: MUST_EXIST);
        $qbank2context = context_module::instance($qbank2cm->id);

        $adaptivequiz = $adaptivequizgenerator->create_instance(['course' => $course1->id]);

        $manager = $coregenerator->create_user();
        $coregenerator->enrol_user($manager->id, $course1->id, 'manager');
        $coregenerator->enrol_user($manager->id, $course2->id, 'manager');

        // End of setup.

        self::setUser($manager);
        item_bank::assign_qbanks_to_adaptivequiz($adaptivequiz->id, [$qbank1context->id, $qbank2context->id]);

        $result = $DB->get_records(
            'adaptivequiz_qbank',
            ['adaptivequizid' => $adaptivequiz->id],
            '',
            'qbankcontextid'
        );

        self::assertCount(2, $result);

        self::assertEqualsCanonicalizing(
            [
                (object) ['qbankcontextid' => $qbank1context->id],
                (object) ['qbankcontextid' => $qbank2context->id],
            ],
            $result
        );
    }

    /**
     * Tests a duplicate assignment will not be created.
     */
    public function test_it_will_not_reassign_a_question_bank_to_an_adaptive_quiz_instance(): void {
        $this->resetAfterTest();

        $coregenerator = $this->getDataGenerator();
        /** @var mod_adaptivequiz_generator $adaptivequizgenerator */
        $adaptivequizgenerator = $coregenerator->get_plugin_generator('mod_adaptivequiz');
        $qbankgenerator = $coregenerator->get_plugin_generator('mod_qbank');

        $course1 = $coregenerator->create_course();

        $qbank1 = $qbankgenerator->create_instance(['course' => $course1->id]);
        $qbank2 = $qbankgenerator->create_instance(['course' => $course1->id]);

        $qbank1cm = get_coursemodule_from_instance(modulename: 'qbank', instance: $qbank1->id, strictness: MUST_EXIST);
        $qbank1context = context_module::instance($qbank1cm->id);

        $qbank2cm = get_coursemodule_from_instance(modulename: 'qbank', instance: $qbank2->id, strictness: MUST_EXIST);
        $qbank2context = context_module::instance($qbank2cm->id);

        // Data randomization.

        $randomadaptivequiz = $adaptivequizgenerator->create_instance(['course' => $course1->id]);

        foreach ([$qbank1context->id, $qbank2context->id] as $qbankcontextid) {
            $adaptivequizgenerator->create_link_with_question_bank([
                'adaptivequizid' => $randomadaptivequiz->id,
                'qbankcontextid' => $qbankcontextid,
            ]);
        }

        // End of.

        $adaptivequiz = $adaptivequizgenerator->create_instance(['course' => $course1->id]);

        $adaptivequizgenerator->create_link_with_question_bank([
            'adaptivequizid' => $adaptivequiz->id,
            'qbankcontextid' => $qbank1context->id,
        ]);

        // End of setup.

        self::expectException(invalid_parameter_exception::class);
        self::expectExceptionMessage('Invalid parameter value detected (Attempt to add existing question bank assignments)');
        item_bank::assign_qbanks_to_adaptivequiz($adaptivequiz->id, [$qbank1context->id]);
    }

    public function test_it_will_not_assign_a_question_bank_to_an_adaptive_quiz_instance_with_no_permissions(): void {
        $this->resetAfterTest();

        $coregenerator = $this->getDataGenerator();
        /** @var mod_adaptivequiz_generator $adaptivequizgenerator */
        $adaptivequizgenerator = $coregenerator->get_plugin_generator('mod_adaptivequiz');
        $qbankgenerator = $coregenerator->get_plugin_generator('mod_qbank');

        $course1 = $coregenerator->create_course();
        $course2 = $coregenerator->create_course();
        $course3 = $coregenerator->create_course();

        $qbank1 = $qbankgenerator->create_instance(['course' => $course1->id]);
        $qbank2 = $qbankgenerator->create_instance(['course' => $course1->id]);
        $qbank3 = $qbankgenerator->create_instance(['course' => $course2->id]);
        $qbank4 = $qbankgenerator->create_instance(['course' => $course3->id]);

        $qbank1cm = get_coursemodule_from_instance(modulename: 'qbank', instance: $qbank1->id, strictness: MUST_EXIST);
        $qbank1context = context_module::instance($qbank1cm->id);

        $qbank2cm = get_coursemodule_from_instance(modulename: 'qbank', instance: $qbank2->id, strictness: MUST_EXIST);
        $qbank2context = context_module::instance($qbank2cm->id);

        // Data randomization.

        $randomadaptivequiz = $adaptivequizgenerator->create_instance(['course' => $course1->id]);

        foreach ([$qbank1context->id, $qbank2context->id] as $qbankcontextid) {
            $adaptivequizgenerator->create_link_with_question_bank([
                'adaptivequizid' => $randomadaptivequiz->id,
                'qbankcontextid' => $qbankcontextid,
            ]);
        }

        // End of.

        $adaptivequiz = $adaptivequizgenerator->create_instance(['course' => $course1->id]);

        $manager = $coregenerator->create_user();
        $coregenerator->enrol_user($manager->id, $course1->id, 'manager');
        $coregenerator->enrol_user($manager->id, $course2->id, 'manager');

        // End of setup.

        self::setUser($manager);

        self::expectException(invalid_parameter_exception::class);
        self::expectExceptionMessage('Invalid parameter value detected (Attempt to assign question bank with no permissions)');

        $qbank3cm = get_coursemodule_from_instance(modulename: 'qbank', instance: $qbank3->id, strictness: MUST_EXIST);
        $qbank3context = context_module::instance($qbank3cm->id);

        $qbank4cm = get_coursemodule_from_instance(modulename: 'qbank', instance: $qbank4->id, strictness: MUST_EXIST);
        $qbank4context = context_module::instance($qbank4cm->id);

        item_bank::assign_qbanks_to_adaptivequiz(
            $adaptivequiz->id,
            [$qbank1context->id, $qbank2context->id, $qbank3context->id, $qbank4context->id]
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

        $qbank1cm = get_coursemodule_from_instance(modulename: 'qbank', instance: $qbank1->id, strictness: MUST_EXIST);
        $qbank1context = context_module::instance($qbank1cm->id);

        $qbank2cm = get_coursemodule_from_instance(modulename: 'qbank', instance: $qbank2->id, strictness: MUST_EXIST);
        $qbank2context = context_module::instance($qbank2cm->id);

        $adaptivequiz1 = $adaptivequizgenerator->create_instance(['course' => $course1->id]);

        foreach ([$qbank1context->id, $qbank2context->id] as $qbankcontextid) {
            $adaptivequizgenerator->create_link_with_question_bank([
                'adaptivequizid' => $adaptivequiz1->id,
                'qbankcontextid' => $qbankcontextid,
            ]);
        }

        $qbank3cm = get_coursemodule_from_instance(modulename: 'qbank', instance: $qbank3->id, strictness: MUST_EXIST);
        $qbank3context = context_module::instance($qbank3cm->id);

        $qbank4cm = get_coursemodule_from_instance(modulename: 'qbank', instance: $qbank4->id, strictness: MUST_EXIST);
        $qbank4context = context_module::instance($qbank4cm->id);

        $adaptivequiz2 = $adaptivequizgenerator->create_instance(['course' => $course2->id]);

        foreach ([$qbank3context->id, $qbank4context->id] as $qbankcontextid) {
            $adaptivequizgenerator->create_link_with_question_bank([
                'adaptivequizid' => $adaptivequiz2->id,
                'qbankcontextid' => $qbankcontextid,
            ]);
        }

        $manager = $coregenerator->create_user();
        $coregenerator->enrol_user($manager->id, $course1->id, 'manager');

        // End of setup.

        $qbank1cm = get_coursemodule_from_instance(modulename: 'qbank', instance: $qbank1->id, strictness: MUST_EXIST);

        self::setUser($manager);
        item_bank::unassign_qbank_from_adaptivequiz($adaptivequiz1->id, $qbank1cm->id);

        $result = item_bank::get_question_banks_assigned_to_adaptivequiz($adaptivequiz1->id);
        self::assertCount(1, $result);

        $remainingqbank = $result[array_key_first($result)];

        // Pick the essential info for making assertions.
        $assertable = [
            'id' => $remainingqbank->get_course_module_record()->id,
            'course' => $remainingqbank->get_course()->id,
        ];

        $qbank2cm = get_coursemodule_from_instance(modulename: 'qbank', instance: $qbank2->id, strictness: MUST_EXIST);
        self::assertEquals(['id' => $qbank2cm->id, 'course' => $course1->id], $assertable);

        // Assert other instances are not affected.
        $result = item_bank::get_question_banks_assigned_to_adaptivequiz($adaptivequiz2->id);
        self::assertCount(2, $result);
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

        $result = item_bank::get_question_categories_assigned_to_adaptivequiz($adaptivequiz1->id);

        $assertable = array_map(function (stdClass $qcat): array {
            /** @var \cm_info $qbank */
            $qbank = $qcat->qbank;

            return [
                'qcatid' => $qcat->id,
                'qbankcmid' => $qbank->get_course_module_record()->id,
                'qbankcmcourse' => $qbank->get_course()->id,
            ];
        }, $result);

        self::assertEqualsCanonicalizing(
            [
                ['qcatid' => $qcat1->id, 'qbankcmid' => $qbankcm->id, 'qbankcmcourse' => $course->id],
                ['qcatid' => $qcat2->id, 'qbankcmid' => $qbankcm->id, 'qbankcmcourse' => $course->id],
            ],
            $assertable
        );

        // Assert other instances are not affected.

        $result = item_bank::get_question_categories_assigned_to_adaptivequiz($adaptivequiz2->id);
        self::assertCount(1, $result);
    }

    public function test_it_gets_question_banks_assigned_to_an_adaptive_quiz_instance(): void {
        $this->resetAfterTest();

        $coregenerator = $this->getDataGenerator();
        /** @var mod_adaptivequiz_generator $adaptivequizgenerator */
        $adaptivequizgenerator = $coregenerator->get_plugin_generator('mod_adaptivequiz');
        /** @var \mod_qbank_generator $qbankgenerator */
        $qbankgenerator = $coregenerator->get_plugin_generator('mod_qbank');

        $course1 = $coregenerator->create_course();
        $course2 = $coregenerator->create_course();
        $course3 = $coregenerator->create_course();
        $course4 = $coregenerator->create_course();

        $qbank1 = $qbankgenerator->create_instance(['course' => $course1->id]);
        $qbank2 = $qbankgenerator->create_instance(['course' => $course2->id]);
        $qbank3 = $qbankgenerator->create_instance(['course' => $course3->id]);
        $qbankgenerator->create_instance(['course' => $course4->id]);

        $qbank1cm = get_coursemodule_from_instance(modulename: 'qbank', instance: $qbank1->id, strictness: MUST_EXIST);
        $qbank1context = context_module::instance($qbank1cm->id);

        $qbank2cm = get_coursemodule_from_instance(modulename: 'qbank', instance: $qbank2->id, strictness: MUST_EXIST);
        $qbank2context = context_module::instance($qbank2cm->id);

        $qbank3cm = get_coursemodule_from_instance(modulename: 'qbank', instance: $qbank3->id, strictness: MUST_EXIST);
        $qbank3context = context_module::instance($qbank3cm->id);

        $adaptivequiz = $adaptivequizgenerator->create_instance(['course' => $course1->id]);

        foreach ([$qbank1context->id, $qbank2context->id, $qbank3context->id] as $qbankcontextid) {
            $adaptivequizgenerator->create_link_with_question_bank([
                'adaptivequizid' => $adaptivequiz->id,
                'qbankcontextid' => $qbankcontextid,
            ]);
        }

        $result = item_bank::get_question_banks_assigned_to_adaptivequiz($adaptivequiz->id);
        self::assertCount(3, $result);

        $assertable = array_map(fn (cm_info $qbankcm): array => [
            'id' => $qbankcm->get_course_module_record()->id,
            'course' => $qbankcm->get_course()->id,
        ], $result);

        self::assertEqualsCanonicalizing(
            [
                ['id' => $qbank1cm->id, 'course' => $course1->id],
                ['id' => $qbank2cm->id, 'course' => $course2->id],
                ['id' => $qbank3cm->id, 'course' => $course3->id],
            ],
            $assertable
        );

        // Test filtering by courses.

        $result = item_bank::get_question_banks_assigned_to_adaptivequiz($adaptivequiz->id, incourseid: $course1->id);
        self::assertCount(1, $result);

        $assertable = array_map(fn (cm_info $qbankcm): array => [
            'id' => $qbankcm->get_instance_record()->id,
            'course' => $qbankcm->get_course()->id,
        ], $result);

        self::assertEqualsCanonicalizing(
            [
                ['id' => $qbank1->id, 'course' => $course1->id],
            ],
            $assertable
        );

        $result = item_bank::get_question_banks_assigned_to_adaptivequiz(
            $adaptivequiz->id,
            notincourseid: $course1->id
        );

        self::assertCount(2, $result);

        $assertable = array_map(fn (cm_info $qbankcm): array => [
            'id' => $qbankcm->get_instance_record()->id,
            'course' => $qbankcm->get_course()->id,
        ], $result);

        self::assertEqualsCanonicalizing(
            [
                ['id' => $qbank2->id, 'course' => $course2->id],
                ['id' => $qbank3->id, 'course' => $course3->id],
            ],
            $assertable
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

        $result = item_bank::get_question_categories_assigned_to_adaptivequiz($adaptivequiz1->id);
        self::assertCount(2, $result);

        $assertable = array_map(function (stdClass $qcat): array {
            /** @var \cm_info $qbank */
            $qbank = $qcat->qbank;

            return [
                'qcatid' => $qcat->id,
                'qbankcmid' => $qbank->get_course_module_record()->id,
                'qbankcmcourse' => $qbank->get_course()->id,
            ];
        }, $result);

        self::assertEqualsCanonicalizing(
            [
                ['qcatid' => $qcat1->id, 'qbankcmid' => $qbank1cm->id, 'qbankcmcourse' => $course1->id],
                ['qcatid' => $qcat2->id, 'qbankcmid' => $qbank1cm->id, 'qbankcmcourse' => $course1->id],
            ],
            $assertable
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

        $qbank1cm = get_coursemodule_from_instance(modulename: 'qbank', instance: $qbank1->id, strictness: MUST_EXIST);
        $qbank1context = context_module::instance($qbank1cm->id);

        $qbank2cm = get_coursemodule_from_instance(modulename: 'qbank', instance: $qbank2->id, strictness: MUST_EXIST);
        $qbank2context = context_module::instance($qbank2cm->id);

        $adaptivequiz1 = $adaptivequizgenerator->create_instance(['course' => $course1->id]);
        $adaptivequiz2 = $adaptivequizgenerator->create_instance(['course' => $course1->id]);

        $adaptivequizgenerator->create_link_with_question_bank([
            'adaptivequizid' => $adaptivequiz2->id,
            'qbankcontextid' => $qbank1context->id,
        ]);

        $adaptivequizgenerator->create_link_with_question_bank([
            'adaptivequizid' => $adaptivequiz2->id,
            'qbankcontextid' => $qbank2context->id,
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
            'qbankcontextid' => $qbank2context->id,
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
