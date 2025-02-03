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
 * A container for placeholder options to be used in a text editor.
 *
 * The class can be used for any text editor.
 *
 * @package    mod_adaptivequiz
 * @copyright  2025 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editor_placeholders {

    /**
     * @var editor_placeholder_option[] $options.
     */
    private array $options;

    /**
     * The constructor, intentionally empty.
     */
    public function __construct() {
    }

    /**
     * Adds an option to the container.
     *
     * @param editor_placeholder_option $option
     * @return self To enable chaining.
     */
    public function add_option(editor_placeholder_option $option): self {
        $this->options[] = $option;

        return $this;
    }

    /**
     * Returns the contained options.
     *
     * @return editor_placeholder_option[]
     */
    public function options(): array {
        return $this->options;
    }
}
