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
 * Contains function with the definition of upgrade steps for the plugin.
 *
 * @package   adaptivequizcatmodel_helloworld
 * @copyright 2023 Vitaly Potenko <potenkov@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Defines upgrade steps for the plugin.
 *
 * @param mixed $oldversion
 * @return bool True on success.
 */
function xmldb_adaptivequizcatmodel_helloworld_upgrade($oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2023022200) {
        $table = new xmldb_table('catmodel_helloworld');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('adaptivequizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('lowestlevel', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('highestlevel', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, 0);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
    }

    if ($oldversion < 2023031000) {
        $table = new xmldb_table('catmodel_helloworld');

        $field = new xmldb_field('lowestlevel', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, 0);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'param1');
        }

        $field = new xmldb_field('highestlevel', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, 0);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'param2');
        }
    }

    return true;
}
