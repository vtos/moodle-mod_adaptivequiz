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
 * Adaptive testing version information.
 *
 * @copyright  2013 Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2022012600;
$plugin->release = '2.0.0';
$plugin->requires = 2021051704.13;
$plugin->cron = 0;
$plugin->component = 'mod_adaptivequiz';
$plugin->maturity = MATURITY_BETA;
