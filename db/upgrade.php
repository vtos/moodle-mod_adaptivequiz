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
 * @package   mod_adaptivequiz
 * @copyright 2013 Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright 2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Defines upgrade steps for the plugin.
 *
 * @param mixed $oldversion
 */
function xmldb_adaptivequiz_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2014020400) {
        // Define field grademethod.
        $table = new xmldb_table('adaptivequiz');
        $field = new xmldb_field('grademethod', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, 1, 'startinglevel');

        // Conditionally add field grademethod.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Quiz savepoint reached.
        upgrade_mod_savepoint(true, 2014020400, 'adaptivequiz');
    }

    if ($oldversion < 2022012600) {
        $table = new xmldb_table('adaptivequiz');
        $field = new xmldb_field('showabilitymeasure', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, false, '0',
            'attemptfeedbackformat');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2022012600, 'adaptivequiz');
    }

    if ($oldversion < 2022092600) {
        $table = new xmldb_table('adaptivequiz');
        $field = new xmldb_field('completionattemptcompleted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, false, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2022092600, 'adaptivequiz');
    }

    if ($oldversion < 2022110200) {
        $table = new xmldb_table('adaptivequiz');
        $field = new xmldb_field('showattemptprogress', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0,
            'showabilitymeasure');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2022110200, 'adaptivequiz');
    }

    if ($oldversion < 2025092700) {
        $table = new xmldb_table('adaptivequiz');
        // The default value is set to '-1' to indicate the transition state of the setting for the existing instances.
        $field = new xmldb_field('attemptfeedbackenable', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL,
            null, '-1', 'attemptfeedbackformat');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2025092700, 'adaptivequiz');
    }

    if ($oldversion < 2025092701) {
        $table = new xmldb_table('adaptivequiz');

        $field = new xmldb_field('showabilitymeasurefeedback', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL,
            null, '0', 'showabilitymeasure');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('showabilitymeasuresummary', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL,
            null, '0', 'showabilitymeasurefeedback');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2025092701, 'adaptivequiz');
    }

    if ($oldversion < 2025092702) {
        // Both new fields will acquire their values from the original 'showabilitymeasure'.
        $sql = "UPDATE {adaptivequiz}
                   SET showabilitymeasurefeedback = showabilitymeasure,
                       showabilitymeasuresummary = showabilitymeasure";
        $DB->execute($sql);

        upgrade_mod_savepoint(true, 2025092702, 'adaptivequiz');
    }

    if ($oldversion < 2026030100) {
        $table = new xmldb_table('adaptivequiz_qbank');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('adaptivequizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('qbankid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('qbankcontextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('adaptivequizid', XMLDB_KEY_FOREIGN, ['adaptivequizid'], 'adaptivequiz', ['id']);
        $table->add_key('qbankid', XMLDB_KEY_FOREIGN, ['qbankid'], 'qbank', ['id']);
        $table->add_key('qbankcontextid', XMLDB_KEY_FOREIGN, ['qbankcontextid'], 'context', ['id']);

        $table->add_index('adaptivequiz-qbank', XMLDB_INDEX_UNIQUE, ['adaptivequizid', 'qbankid', 'qbankcontextid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2026030100, 'adaptivequiz');
    }

    if ($oldversion < 2026030101) {
        $table = new xmldb_table('adaptivequiz');
        $field = new xmldb_field('debuginfoenable', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL,
            null, '0', 'completionattemptcompleted');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026030101, 'adaptivequiz');
    }

    return true;
}
