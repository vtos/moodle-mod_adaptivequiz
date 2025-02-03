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

namespace mod_adaptivequiz\output;

use mod_adaptivequiz\editor_placeholder_option;
use mod_adaptivequiz\editor_placeholders as editor_placeholders_definition;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Output object to display a list of placeholders to be used in the editor form field.
 *
 * @package    mod_adaptivequiz
 * @copyright  2025 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editor_placeholders implements renderable, templatable {

    /**
     * @var editor_placeholders_definition $definition Definition of the placeholders.
     */
    private editor_placeholders_definition $definition;

    /**
     * The constructor.
     */
    public function __construct(editor_placeholders_definition $definition) {
        $this->definition = $definition;
    }

    /**
     * Implements the interface.
     *
     * @param renderer_base $output
     * @return stdClass|array
     */
    public function export_for_template(renderer_base $output) {
        return [
            'placeholders' => array_map(fn (editor_placeholder_option $option) => [
                'placeholderid' => $option->id(),
                'placeholderkey' => $option->key(),
                'placeholderdesc' => $option->description(),
            ], $this->definition->options()),
        ];
    }
}
