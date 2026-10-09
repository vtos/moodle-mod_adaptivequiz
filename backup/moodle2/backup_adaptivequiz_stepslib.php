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

use mod_adaptivequiz\item_bank_helper;

/**
 * Define all the backup steps that will be used by the backup_adaptivequiz_activity_task.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @copyright  2022 onwards Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_adaptivequiz_activity_structure_step extends backup_questions_activity_structure_step {

    /**
     * Define the backup structure.
     *
     * @return backup_nested_element The root element (adaptivequiz), wrapped into standard activity structure.
     */
    protected function define_structure() {
        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $nodes = ['name', 'intro', 'introformat', 'attempts', 'password', 'browsersecurity', 'attemptfeedbackenable',
            'attemptfeedback', 'attemptfeedbackformat', 'showabilitymeasure', 'showabilitymeasurefeedback',
            'showabilitymeasuresummary', 'showattemptprogress', 'highestlevel', 'lowestlevel', 'minimumquestions',
            'maximumquestions', 'standarderror', 'startinglevel', 'timecreated', 'timemodified',
            'completionattemptcompleted', 'debuginfoenable'];
        $adaptivequiz = new backup_nested_element('adaptivequiz', ['id'], $nodes);

        // Attempts.
        $adaptiveattempts = new backup_nested_element('adaptiveattempts');
        $nodes = ['userid', 'uniqueid', 'attemptstate', 'attemptstopcriteria', 'questionsattempted', 'difficultysum',
            'standarderror', 'measure', 'timecreated', 'timemodified'];
        $adaptiveattempt = new backup_nested_element('adaptiveattempt', ['id'], $nodes);

        // This module is using questions, so produce the related question states and sessions.
        // attaching them to the $attempt element based in 'uniqueid' matching.
        $this->add_question_usages($adaptiveattempt, 'uniqueid');

        // Activity to question categories reference.
        $adaptivequestioncats = new backup_nested_element('adatpivequestioncats');
        $adaptivequestioncat = new backup_nested_element('adatpivequestioncat', ['id'], ['questioncategory']);

        // Question banks.
        $qbanks = new backup_nested_element('itembankqbanks');
        $qbank = new backup_nested_element('itembankqbank', ['id'], ['qbankid', 'qbankcontextid']);

        // Build the tree.

        $adaptivequiz->add_child($adaptiveattempts);
        $adaptiveattempts->add_child($adaptiveattempt);

        $adaptivequiz->add_child($adaptivequestioncats);
        $adaptivequestioncats->add_child($adaptivequestioncat);

        $adaptivequiz->add_child($qbanks);
        $qbanks->add_child($qbank);

        // Define sources.
        $adaptivequiz->set_source_table('adaptivequiz', ['id' => backup::VAR_ACTIVITYID]);
        $adaptivequestioncat->set_source_table('adaptivequiz_question', ['instance' => backup::VAR_PARENTID]);
        $qbank->set_source_table('adaptivequiz_qbank', ['adaptivequizid' => backup::VAR_PARENTID]);

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $sql = 'SELECT *
                      FROM {adaptivequiz_attempt}
                     WHERE instance = :instance';
            $param = ['instance' => backup::VAR_PARENTID];
            $adaptiveattempt->set_source_sql($sql, $param);
        }

        // Define id annotations.
        $adaptivequestioncat->annotate_ids('question_categories', 'questioncategory');
        $adaptiveattempt->annotate_ids('user', 'userid');

        $this->annotate_item_bank();

        $adaptivequiz->annotate_files('mod_adaptivequiz', 'intro', null); // This file area hasn't itemid.
        $adaptivequiz->annotate_files('mod_adaptivequiz', 'attemptfeedback', null);

        return $this->prepare_activity_structure($adaptivequiz);
    }

    /**
     * Collects all questions to be included into the backup.
     *
     * This is similar to what {@see \backup_question_set_reference_trait::annotate_set_reference_bank_entries()} does.
     * To have all the questions from the activity's item bank to be included into the backup, we have to collect their
     * 'questionbankentryid' values and explicitely add them to the backup.
     */
    private function annotate_item_bank(): void {
        global $DB;

        $qcatidlist = item_bank_helper::get_question_categories($this->task->get_activityid());
        if (!$qcatidlist) {
            return;
        }

        [$qcatsql, $params] = $DB->get_in_or_equal($qcatidlist);

        $sql = "SELECT qbe.id
                  FROM {question_versions} qv
                  JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                 WHERE qbe.questioncategoryid {$qcatsql}";

        $qbeidlist = $DB->get_fieldset_sql($sql, $params);

        $backupid = $this->task->get_backupid();
        foreach ($qbeidlist as $qbeid) {
            backup_structure_dbops::insert_backup_ids_record($backupid, 'question_bank_entry', $qbeid);
        }
    }
}
