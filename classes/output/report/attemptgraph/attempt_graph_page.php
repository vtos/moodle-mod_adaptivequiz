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

use moodle_url;
use renderable;
use stdClass;

/**
 * Output object to render the attempt graph page.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempt_graph_page implements renderable {

    /**
     * @var moodle_url $graphurl URL of the script which builds the graph image.
     */
    private $graphurl;

    /**
     * @var attempt_graph_dataset $graphdataset
     */
    private $graphdataset;

    /**
     * Empty and closed, the factory method must be used instead.
     */
    private function __construct() {
    }

    /**
     * A factory method to properly instantiate an object of the class.
     *
     * @param stdClass $attemptrecord A record from the {adaptivequiz_attempt} table.
     * @return self
     */
    public static function create_for_attempt(stdClass $attemptrecord): self {
        $page = new self();
        $page->graphurl = new moodle_url('/mod/adaptivequiz/attemptgraph.php', ['attempt' => $attemptrecord->id]);
        $page->graphdataset = attempt_graph_dataset::create_for_attempt($attemptrecord);

        return $page;
    }

    /**
     * Property getter.
     */
    public function graph_url(): moodle_url {
        return $this->graphurl;
    }

    /**
     * Property getter.
     */
    public function graph_dataset(): attempt_graph_dataset {
        return $this->graphdataset;
    }
}
