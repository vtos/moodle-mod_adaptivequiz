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

namespace mod_adaptivequiz\external;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../../webservice/tests/helpers.php');

use context_module;
use externallib_advanced_testcase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * A test class.
 *
 * @package    mod_adaptivequiz
 * @copyright  2026 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\mod_adaptivequiz\external\search_question_banks::class)]
class search_question_banks_test extends externallib_advanced_testcase {

    public function test_it_returns_qbanks_for_assignment_in_adaptive_quiz_instance(): void {
        global $SITE;

        $this->resetAfterTest();

        $coregenerator = $this->getDataGenerator();
        /** @var \mod_adaptivequiz_generator $adaptivequizgenerator */
        $adaptivequizgenerator = $coregenerator->get_plugin_generator('mod_adaptivequiz');
        $qbankgenerator = $coregenerator->get_plugin_generator('mod_qbank');

        $course1 = $coregenerator->create_course(['shortname' => 'c1']);
        $course2 = $coregenerator->create_course(['shortname' => 'c2']);
        $course3 = $coregenerator->create_course(['shortname' => 'c3']);

        $qbank1 = $qbankgenerator->create_instance(
            ['course' => $course1->id, 'name' => 'This Course Question Bank']
        );
        $qbank2 = $qbankgenerator->create_instance(
            ['course' => $course2->id, 'name' => 'Another Course Question Bank 1']
        );
        $qbank3 = $qbankgenerator->create_instance(
            ['course' => $SITE->id, 'name' => 'System Question Bank']
        );
        $qbank4 = $qbankgenerator->create_instance(
            ['course' => $course3->id, 'name' => 'Another Course Question Bank 2']
        );

        $adaptivequiz = $adaptivequizgenerator->create_instance(['course' => $course1->id]);

        $cm = get_coursemodule_from_instance(
            modulename: 'adaptivequiz',
            instance: $adaptivequiz->id,
            strictness: MUST_EXIST
        );

        $context = context_module::instance($cm->id);

        $manager = $coregenerator->create_user();
        $coregenerator->enrol_user($manager->id, $course1->id);
        self::setUser($manager);

        // End of setup.

        // Test it returns everything from everywhere.
        $result = search_question_banks::execute($context->id);
        self::assertArrayHasKey('questionbanks', $result);
        self::assertCount(4, $result['questionbanks']);
        foreach ($result['questionbanks'] as $resultitem) {
            self::assertArrayHasKey('value', $resultitem);
            self::assertArrayHasKey('label', $resultitem);
        }

        // Test it returns what's in the given course only.
        $result = search_question_banks::execute(contextid: $context->id, incourseid: $course1->id);
        self::assertArrayHasKey('questionbanks', $result);
        self::assertCount(1, $result['questionbanks']);
        self::assertArrayHasKey('label', $result['questionbanks'][0]);
        self::assertStringContainsString('This Course Question Bank', $result['questionbanks'][0]['label']);

        // Test it returns what's in other courses only.
        $result = search_question_banks::execute(contextid: $context->id, notincourseid: $course1->id);
        self::assertArrayHasKey('questionbanks', $result);

        self::assertEqualsCanonicalizing(
            [
                ['value' => $qbank2->id, 'label' => "$course2->shortname - $qbank2->name"],
                ['value' => $qbank3->id, 'label' => "$SITE->shortname - $qbank3->name"],
                ['value' => $qbank4->id, 'label' => "$course3->shortname - $qbank4->name"],
            ],
            $result['questionbanks']);


        // Test it filters by a search term.
        $result = search_question_banks::execute(contextid: $context->id, notincourseid: $course1->id, search: 'sys');
        self::assertCount(1, $result['questionbanks']);
        self::assertArrayHasKey('label', $result['questionbanks'][0]);
        self::assertStringContainsString('System Question Bank', $result['questionbanks'][0]['label']);
    }

    public function test_it_will_not_return_qbanks_already_assigned_to_the_adaptive_quiz_instance(): void {
        global $SITE;

        $this->resetAfterTest();

        $coregenerator = $this->getDataGenerator();
        /** @var \mod_adaptivequiz_generator $adaptivequizgenerator */
        $adaptivequizgenerator = $coregenerator->get_plugin_generator('mod_adaptivequiz');
        $qbankgenerator = $coregenerator->get_plugin_generator('mod_qbank');

        $course1 = $coregenerator->create_course(['shortname' => 'c1']);
        $course2 = $coregenerator->create_course(['shortname' => 'c2']);
        $course3 = $coregenerator->create_course(['shortname' => 'c3']);

        $qbank1 = $qbankgenerator->create_instance(
            ['course' => $course1->id, 'name' => 'This Course Question Bank 1']
        );
        $qbank2 = $qbankgenerator->create_instance(
            ['course' => $course1->id, 'name' => 'This Course Question Bank 2']
        );
        $qbank3 = $qbankgenerator->create_instance(
            ['course' => $course2->id, 'name' => 'Another Course Question Bank 1']
        );
        $qbank4 = $qbankgenerator->create_instance(
            ['course' => $SITE->id, 'name' => 'System Question Bank']
        );
        $qbank5 = $qbankgenerator->create_instance(
            ['course' => $course3->id, 'name' => 'Another Course Question Bank 2']
        );

        $adaptivequiz = $adaptivequizgenerator->create_instance(['course' => $course1->id]);

        foreach ([$qbank1->id, $qbank4->id, $qbank5->id] as $qbankid) {
            $adaptivequizgenerator->create_link_with_question_bank([
                'adaptivequizid' => $adaptivequiz->id,
                'qbankid' => $qbankid,
            ]);
        }

        $cm = get_coursemodule_from_instance(
            modulename: 'adaptivequiz',
            instance: $adaptivequiz->id,
            strictness: MUST_EXIST
        );

        $context = context_module::instance($cm->id);

        $manager = $coregenerator->create_user();
        $coregenerator->enrol_user($manager->id, $course1->id);
        self::setUser($manager);

        // End of setup.

        $result = search_question_banks::execute($context->id, incourseid: $course1->id, search: 'question');
        self::assertArrayHasKey('questionbanks', $result);
        self::assertCount(1, $result['questionbanks']);
        self::assertArrayHasKey('label', $result['questionbanks'][0]);
        self::assertStringContainsString('This Course Question Bank 2', $result['questionbanks'][0]['label']);

        $result = search_question_banks::execute($context->id, notincourseid: $course1->id, search: 'question');
        self::assertArrayHasKey('questionbanks', $result);
        self::assertCount(1, $result['questionbanks']);
        self::assertArrayHasKey('label', $result['questionbanks'][0]);
        self::assertStringContainsString('Another Course Question Bank 1', $result['questionbanks'][0]['label']);
    }
}
