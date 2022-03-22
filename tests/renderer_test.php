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
 * PHPUnit tests for the renderer class.
 *
 * @copyright  2013 Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/mod/adaptivequiz/locallib.php');
require_once($CFG->dirroot.'/mod/adaptivequiz/renderer.php');
require_once($CFG->dirroot.'/tag/lib.php');

/**
 * @group mod_adaptivequiz
 */
class mod_adaptivequiz_renderer_testcase extends advanced_testcase {
    /**
     * This function tests the output for the start attempt form
     */
    public function test_display_start_attempt_form() {
        $dummypage = new moodle_page();
        $target = 'mod_adaptivequiz';
        $renderer = new mod_adaptivequiz_renderer($dummypage, $target);
        $output = $renderer->display_start_attempt_form(9999);

        $this->assertStringContainsString('<form', $output);
        $this->assertStringContainsString('/mod/adaptivequiz/attempt.php?cmid=9999', $output);
        $this->assertStringContainsString('<input', $output);
        $this->assertStringContainsString('type="submit"', $output);
        $this->assertStringContainsString('class="submitbtns adaptivequizbtn btn btn-secondary"', $output);
        $this->assertStringContainsString('type="hidden"', $output);
        $this->assertStringContainsString('name="sesskey"', $output);
        $this->assertStringContainsString('</form>', $output);
    }

    /**
     * This function tests the output from the get_js_module
     * @return void
     */
    public function test_adaptivequiz_get_js_module() {
        $dummypage = new moodle_page();
        $target = 'mod_adaptivequiz';
        $renderer = new mod_adaptivequiz_renderer($dummypage, $target);
        $output = $renderer->adaptivequiz_get_js_module();

        $this->assertArrayHasKey('name', $output);
        $this->assertContains('mod_adaptivequiz', $output);
        $this->assertArrayHasKey('fullpath', $output);
        $this->assertContains('/mod/adaptivequiz/module.js', $output);
        $this->assertArrayHasKey('requires', $output);
        $this->assertEquals(array('base', 'dom', 'event-delegate', 'event-key', 'core_question_engine', 'moodle-core-formchangechecker'), $output['requires']);
        $this->assertArrayHasKey('strings', $output);
    }

    /**
     * This function tests the output from the create_submit_form
     * @return void
     */
    public function test_create_submit_form() {

        $dummypage = new moodle_page();
        $target = 'mod_adaptivequiz';
        $renderer = new mod_adaptivequiz_renderer($dummypage, $target);

        $mockquba = $this->createPartialMock('question_usage_by_activity', ['render_question']);
        $mockquba->expects($this->once())
            ->method('render_question')
            ->withAnyParameters()
            ->willReturn('output');

        $output = $renderer->create_submit_form(9999, $mockquba, 8888, 7777);

        // Test form attributes
        $this->assertStringContainsString('<form', $output);
        $this->assertStringContainsString('enctype="multipart/form-data"', $output);
        $this->assertStringContainsString('accept-charset="utf-8"', $output);
        $this->assertStringContainsString('id="responseform"', $output);

        // Test submit button and class
        $this->assertStringContainsString('type="submit"', $output);
        $this->assertStringContainsString('class="submitbtns adaptivequizbtn"', $output);

        // Test output contains required elements
        $this->assertStringContainsString('name="cmid"', $output);
        $this->assertStringContainsString('name="uniqueid"', $output);
        $this->assertStringContainsString('name="sesskey"', $output);
        $this->assertStringContainsString('name="slots"', $output);
        $this->assertStringContainsString('name="dl"', $output);

        $this->assertStringContainsString('</form>', $output);
    }

    /**
     * This functions tests the output from create_report_table()
     */
    public function test_create_report_table() {
        $dummypage = new moodle_page();
        $target = 'mod_adaptivequiz';
        $renderer = new mod_adaptivequiz_renderer($dummypage, $target);

        $records = array();
        $records[1] = new stdClass();
        $records[1]->id = 1;
        $records[1]->firstname = 'test firstname';
        $records[1]->lastname = 'test lastname';
        $records[1]->email = 'test@example.edu';
        $records[1]->measure = -0.6;
        $records[1]->stderror = 0.17;
        $records[1]->timemodified = 12345678;
        $records[1]->uniqueid = 1111;
        $records[1]->highestlevel = 16;
        $records[1]->lowestlevel = 1;
        $records[1]->attempts = 5;

        $cm = new stdClass();
        $cm->id = 1;

        $sort = 'firstname';
        $sortdir = 'ASC';

        $output = $renderer->create_report_table($records, $cm, $sort, $sortdir);
        $this->assertStringContainsString('<table', $output);
        $this->assertStringContainsString('/mod/adaptivequiz/viewreport.php', $output);
        /* Check table row */
        $this->assertStringContainsString('test firstname', $output);
        $this->assertStringContainsString('test lastname', $output);
        $this->assertStringContainsString('test@example.edu', $output);
        $this->assertStringContainsString('/user/profile.php?id=1', $output);
        $this->assertStringContainsString('6.3', $output);
        $this->assertStringContainsString('&plusmn; 4%', $output);
        $this->assertStringContainsString('5', $output);
        /* Check table column headers */
        $this->assertStringContainsString('sort=firstname', $output);
        $this->assertStringContainsString('sort=lastname', $output);
        $this->assertStringContainsString('sort=email', $output);
        $this->assertStringContainsString('sort=attempts', $output);
        $this->assertStringContainsString('sort=stderror', $output);
    }

    /**
     * This function tests how init_metadata() handlss an integer
     */
    public function test_init_metadata_with_integer() {
        $dummypage = new moodle_page();
        $target = 'mod_adaptivequiz';
        $renderer = new mod_adaptivequiz_renderer($dummypage, $target);

        $mockquba = $this->createPartialMock('question_usage_by_activity', ['render_question_head_html']);
        $mockquba->expects($this->once())
            ->method('render_question_head_html')
            ->willReturn('');

        // Only testing that the mock object's method is called once
        $renderer->init_metadata($mockquba, 1);
    }

    /**
     * This function tests the output from print_form_and_button()
     */
    public function test_print_form_and_button() {
        $dummypage = new moodle_page();
        $target = 'mod_adaptivequiz';
        $renderer = new mod_adaptivequiz_renderer($dummypage, $target);
        $url = new moodle_url('/test/phpunittest/test.php', array('cmid' => 99));
        $text = 'phpunit test button';

        $output = $renderer->print_form_and_button($url, $text);
        $this->assertStringContainsString('<form', $output);
        $this->assertStringContainsString('<input', $output);
        $this->assertStringContainsString('type="submit"', $output);
        $this->assertStringContainsString('/test/phpunittest/test.php', $output);
        $this->assertStringContainsString('cmid=99', $output);
        $this->assertStringContainsString('phpunit test button', $output);
        $this->assertStringContainsString('<center>', $output);
        $this->assertStringContainsString('</center>', $output);
        $this->assertStringContainsString('</form>', $output);
    }

    /**
     * This functions tests the output from print_attempt_report_table()
     */
    public function test_print_attempt_report_table() {
        $dummypage = new moodle_page();
        $target = 'mod_adaptivequiz';
        $renderer = new mod_adaptivequiz_renderer($dummypage, $target);

        $records = array();
        $records[1] = new stdClass();
        $records[1]->id = 1;
        $records[1]->instance = 1;
        $records[1]->userid = 1;
        $records[1]->uniqueid = 123;
        $records[1]->attemptstate = 'completed';
        $records[1]->attemptstopcriteria = 'stopped for some reason';
        $records[1]->questionsattempted = 12;
        $records[1]->standarderror = 0.001;
        $records[1]->measure = -0.6;
        $records[1]->stderror = 0.17;
        $records[1]->highestlevel = 16;
        $records[1]->lowestlevel = 1;
        $records[1]->timemodified = 12345678;
        $records[1]->timecreated = 12345600;

        $cm = new stdClass();
        $cm->id = 1;

        $output = $renderer->print_attempt_report_table($records, $cm, new stdClass);
        $this->assertStringContainsString('<table', $output);
        $this->assertStringContainsString('/mod/adaptivequiz/reviewattempt.php', $output);
        $this->assertStringContainsString('uniqueid=123', $output);
        $this->assertStringContainsString('userid=1', $output);
        $this->assertStringContainsString('cmid=1', $output);
        /* Check table row */
        $this->assertStringContainsString('stopped for some reason', $output);
        $this->assertStringContainsString('6.3 &plusmn; 4%', $output);
        $this->assertStringContainsString('12', $output);
        $this->assertStringContainsString('</table>', $output);
    }

    /**
     * This function tests the output from format_report_table_headers()
     */
    public function test_format_report_table_headers() {
        $dummypage = new moodle_page();
        $target = 'mod_adaptivequiz';
        $renderer = new mod_adaptivequiz_renderer($dummypage, $target);
        $dummycm = new stdClass();
        $dummycm->id = 99;

        $output = $renderer->format_report_table_headers($dummycm, 'stderror', 'ASC');
        $this->assertEquals(6, count($output));
        $this->assertStringContainsString('/mod/adaptivequiz/viewreport.php', $output[0]);
        $this->assertStringContainsString('sort=firstname&amp;sortdir=ASC', $output[0]);
        $this->assertStringContainsString('sort=lastname&amp;sortdir=ASC', $output[0]);
        $this->assertStringContainsString('/mod/adaptivequiz/viewreport.php', $output[1]);
        $this->assertStringContainsString('sort=email&amp;sortdir=ASC', $output[1]);
        $this->assertStringContainsString('/mod/adaptivequiz/viewreport.php', $output[2]);
        $this->assertStringContainsString('sort=attempts&amp;sortdir=ASC', $output[2]);
        $this->assertStringContainsString('/mod/adaptivequiz/viewreport.php', $output[3]);
        $this->assertStringContainsString('sort=measure&amp;sortdir=ASC', $output[3]);
        $this->assertStringContainsString('/mod/adaptivequiz/viewreport.php', $output[4]);
        $this->assertStringContainsString('sort=stderror&amp;sortdir=DESC', $output[4]);
        $this->assertStringContainsString('/mod/adaptivequiz/viewreport.php', $output[5]);
        $this->assertStringContainsString('sort=timemodified&amp;sortdir=ASC', $output[5]);
    }
}
