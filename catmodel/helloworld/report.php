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

/**
 * Attempts report page.
 *
 * @package    adaptivequizcatmodel_helloworld
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');

use mod_adaptivequiz\local\attempt\attempt;

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('adaptivequiz', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$adaptivequiz = $DB->get_record('adaptivequiz', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('adaptivequizcatmodel/helloworld:viewreport', $context);

$PAGE->set_url('/mod/adaptivequiz/catmodel/helloworld/report.php', ['id' => $cm->id]);
$PAGE->set_context($context);
$PAGE->add_body_class('limitedwidth');
$PAGE->activityheader->disable();
$PAGE->set_title(format_string($adaptivequiz->name));

$attemptsnumber = attempt::total_number($adaptivequiz->id);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('attemptsnumber', 'adaptivequiz', $attemptsnumber));
echo $OUTPUT->footer();
