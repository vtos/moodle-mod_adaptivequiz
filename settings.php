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
 * Plugin's settings.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'adaptivequizdefaultsettingsheading',
        get_string('settingsdefaultsettingsheading', 'adaptivequiz'),
        get_string('settingsdefaultsettingsheadinginfo', 'adaptivequiz')
    ));

    $settings->add(new admin_setting_configtext(
        'adaptivequiz/startinglevel',
        get_string('startinglevel', 'adaptivequiz'),
        get_string('startinglevel_help', 'adaptivequiz'),
        0,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'adaptivequiz/lowestlevel',
        get_string('lowestlevel', 'adaptivequiz'),
        get_string('lowestlevel_help', 'adaptivequiz'),
        0,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'adaptivequiz/highestlevel',
        get_string('highestlevel', 'adaptivequiz'),
        get_string('highestlevel_help', 'adaptivequiz'),
        0,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'adaptivequiz/minimumquestions',
        get_string('minimumquestions', 'adaptivequiz'),
        get_string('minimumquestions_help', 'adaptivequiz'),
        0,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'adaptivequiz/maximumquestions',
        get_string('maximumquestions', 'adaptivequiz'),
        get_string('maximumquestions_help', 'adaptivequiz'),
        0,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'adaptivequiz/standarderror',
        get_string('standarderror', 'adaptivequiz'),
        get_string('standarderror_help', 'adaptivequiz'),
        5,
        PARAM_FLOAT
    ));
}
