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

namespace mod_adaptivequiz\output\report\attemptgraph;

/**
 * Output object to render a point from the attempt graph data.
 *
 * A graph point is a set of parameters describing a single attempted question.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class attempt_graph_dataset_point {

    /**
     * @var string $questiondifficulty
     */
    public $questiondifficulty;

    /**
     * @var string $targetquestiondifficulty
     */
    public $targetquestiondifficulty;

    /**
     * @var bool $answeriscorrect
     */
    public $answeriscorrect;

    /**
     * @var string $abilitymeasure
     */
    public $abilitymeasure;

    /**
     * @var string $standarderror
     */
    public $standarderror;

    /**
     * @var string $standarderrorrangemin
     */
    public $standarderrorrangemin;

    /**
     * @var string $standarderrorrangemax
     */
    public $standarderrorrangemax;
}
