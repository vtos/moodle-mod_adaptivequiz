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

/**
 * Defines an interface for placeholder options.
 *
 * The purpose is to define a single interface for placeholders to be used with any text editor within the plugin.
 *
 * @package    mod_adaptivequiz
 * @copyright  2025 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface editor_placeholder_option {

    /**
     * Returns an option's string value.
     */
    public function id(): string;

    /**
     * Returns an option's string value enclosed in special symbols to identify it as a placeholder in a text.
     */
    public function key(): string;

    /**
     * Defines text for the given option to be used as an option's explanation on a page, in a form, etc.
     */
    public function description(): string;
}
