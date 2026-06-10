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
 * Page to manage item bank for an adaptive quiz instance.
 *
 * @package    mod_adaptivequiz
 * @copyright  2026 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use core\output\notification;
use core_question\local\bank\question_bank_helper;
use mod_adaptivequiz\item_bank;

$id = required_param('id', PARAM_INT);
$unassignqbankid = optional_param('unassignqbank', 0, PARAM_INT);
$unassignqcatid = optional_param('unassignqcat', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'adaptivequiz');

if ($unassignqbankid && $confirm) {
    item_bank::unassign_qbank_from_adaptivequiz($cm->instance, $unassignqbankid);

    redirect(
        new moodle_url('/mod/adaptivequiz/itembank.php', ['id' => $id]),
        get_string('itembankqbankunassignsuccess', 'adaptivequiz')
    );
}

if ($unassignqcatid && $confirm) {
    item_bank::unassign_question_category_from_adaptivequiz($cm->instance, $unassignqcatid);

    redirect(
        new moodle_url('/mod/adaptivequiz/itembank.php', ['id' => $id]),
        get_string('itembankunlinksuccess', 'adaptivequiz')
    );
}

$adaptivequiz = $DB->get_record('adaptivequiz', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

$PAGE->set_context($context);
$PAGE->set_url('/mod/adaptivequiz/itembank.php', ['id' => $id]);

$title = get_string('itembankpagetitle', 'adaptivequiz', format_string($adaptivequiz->name));
$PAGE->set_title($title);

require_login($course, true, $cm);

/** @var mod_adaptivequiz_renderer $renderer */
$renderer = $PAGE->get_renderer('mod_adaptivequiz');

$qbankmigrated = question_bank_helper::has_bank_migration_task_completed_successfully();
if (!$qbankmigrated) {
    $defaultqbankmod = question_bank_helper::get_default_question_bank_activity_name();

    echo $renderer->header();

    echo $renderer->notification(
        message: get_string('transfernotfinished', 'mod_' . $defaultqbankmod),
        type: notification::NOTIFY_WARNING,
        closebutton: false
    );

    echo $renderer->footer();
    exit;
}

if ($unassignqbankid || $unassignqcatid) {
    $confirmurl = clone($PAGE->url);
    $confirmurl->param('confirm', 1);

    if ($unassignqbankid) {
        $qbankcm = get_coursemodule_from_instance('qbank', $unassignqbankid, 0, false, MUST_EXIST);
        $qbankcminfo = cm_info::create($qbankcm);

        $confirmmessage = get_string('itembankqbankunassignconfirm', 'adaptivequiz', $qbankcminfo->get_formatted_name());
        $confirmurl->param('unassignqbank', $unassignqbankid);
    }

    if ($unassignqcatid) {
        $qcategory = $DB->get_record('question_categories', ['id' => $unassignqcatid], '*', MUST_EXIST);

        $confirmmessage = get_string('itembankqcatunlinkconfirm', 'adaptivequiz', $qcategory->name);
        $confirmurl->param('unassignqcat', $unassignqcatid);
    }

    echo $renderer->header();
    echo $renderer->confirm($confirmmessage, $confirmurl, $PAGE->url);
    echo $renderer->footer();
    die;
}

$PAGE->set_heading($title);
$PAGE->add_body_class('limitedwidth');

echo $renderer->header();
echo $renderer->item_bank_page($adaptivequiz, $cm);
echo $renderer->footer();
