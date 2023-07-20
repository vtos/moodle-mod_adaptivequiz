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
 * Generates a graph of the question difficulties asked and the measured ability of the test-taker as the test progressed.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_adaptivequiz\local\attempt\attempt;
use mod_adaptivequiz\local\report\questions_difficulty_range;
use mod_adaptivequiz\output\report\attemptgraph\attempt_graph_dataset;

require_once('../../config.php');
require_once($CFG->dirroot.'/tag/lib.php');
require_once($CFG->dirroot.'/mod/adaptivequiz/locallib.php');
require_once($CFG->dirroot.'/lib/graphlib.php');

$attemptid = required_param('attempt', PARAM_INT);

$attempt = attempt::get_by_id($attemptid);

$adaptivequiz = $DB->get_record('adaptivequiz', ['id' => $attempt->read_attempt_data()->instance], '*', MUST_EXIST);
list($course, $cm) = get_course_and_cm_from_instance($adaptivequiz, 'adaptivequiz');

require_login($course, true, $cm);

require_capability('mod/adaptivequiz:viewreport', context_module::instance($cm->id));

$graphdataset = attempt_graph_dataset::create_for_attempt($attempt->read_attempt_data());
$diffucultyrange = questions_difficulty_range::from_activity_instance($adaptivequiz);

$g = new graph(750, 300);
$g->parameter['title'] = '';
$g->parameter['y_label_left'] = get_string('attemptquestion_ability', 'adaptivequiz');
$g->parameter['legend']        = 'outside-top';
$g->parameter['legend_border'] = 'black';
$g->parameter['legend_offset'] = 4;
$g->parameter['grid_colour'] = 'grayCC';

$qnumbers = [];
$qdifficulties = [];
$abilitymeasures = [];
$errormaximums = [];
$errorminimums = [];
$targetlevels = [];

$ordernum = 0;
foreach ($graphdataset->points() as $datasetpoint) {
    $qnumbers[] = ++$ordernum;
    $qdifficulties[] = $datasetpoint->questiondifficulty;
    $targetlevels[] = $datasetpoint->targetquestiondifficulty;
    $abilitymeasures[] = $datasetpoint->abilitymeasure;
    $errormaximums[] = $datasetpoint->standarderrorrangemax;
    $errorminimums[] = $datasetpoint->standarderrorrangemin;
}

$g->x_data = $qnumbers;
$g->y_data['qdiff'] = $qdifficulties;
$g->y_data['ability'] = $abilitymeasures;
$g->y_data['target_level'] = $targetlevels;
$g->y_data['error_max'] = $errormaximums;
$g->y_data['error_min'] = $errorminimums;

$g->y_format['qdiff'] = array('colour' => 'blue', 'line' => 'brush', 'brush_size' => 2, 'shadow' => 'none',
    'legend' => get_string('attemptquestion_level', 'adaptivequiz'));
$g->y_format['target_level'] = array('colour' => 'green', 'line' => 'brush', 'brush_size' => 1, 'shadow' => 'none',
    'legend' => get_string('graphlegend_target', 'adaptivequiz'));
$g->y_format['ability'] = array('colour' => 'red', 'line' => 'brush', 'brush_size' => 2, 'shadow' => 'none',
    'legend' => get_string('attemptquestion_ability', 'adaptivequiz'));
$g->colour['pink'] = imagecolorallocate($g->image, 0xFF, 0xE5, 0xE5);
$g->y_format['error_max'] = array('colour' => 'pink', 'area' => 'fill', 'shadow' => 'none',
    'legend' => get_string('graphlegend_error', 'adaptivequiz'));
$g->y_format['error_min'] = array('colour' => 'white', 'area' => 'fill', 'shadow' => 'none');

$g->parameter['y_min_left'] = $adaptivequiz->lowestlevel;
$g->parameter['y_max_left'] = $adaptivequiz->highestlevel;
$g->parameter['x_grid'] = 'none';

if ($adaptivequiz->highestlevel - $adaptivequiz->lowestlevel <= 20) {
    $g->parameter['y_axis_gridlines'] = $adaptivequiz->highestlevel - $adaptivequiz->lowestlevel + 1;
    $g->parameter['y_decimal_left'] = 0;
} else {
    $g->parameter['y_axis_gridlines'] = 21;
    $g->parameter['y_decimal_left'] = 1;
}

$numattempted = count($graphdataset->points());

// Ensure that the x-axis text isn't to cramped.
$g->parameter['x_axis_text'] = ceil($numattempted / 40);


// Draw in custom order to get grid lines on top instead of using $g->draw().
$g->y_order = array('error_max', 'error_min', 'target_level', 'qdiff', 'ability');
$g->init();
// After initializing with all data sets, reset the order to just the standard-error sets and draw them.
$g->y_order = array('error_max', 'error_min');
$g->draw_data();

// Now draw the axis and text on top of the error ranges.
$g->y_order = array('ability', 'error_max', 'target_level', 'qdiff', 'error_min');
$g->draw_y_axis();
$g->draw_text();

// Now reset the order and draw our lines.
$g->y_order = array('qdiff', 'target_level', 'ability');
$g->draw_data();

$g->output();
