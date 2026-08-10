<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Upgrade steps for local_calendarcategories.
 *
 * @package    local_calendarcategories
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Plugin upgrade routine.
 *
 * @param int $oldversion The version currently installed.
 * @return bool Always true on success.
 */
function xmldb_local_calendarcategories_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    // Build 2026062837: table names were extended to carry the full plugin
    // prefix (moodle-plugin-ci "validate" requires local_calendarcategories_
    // as prefix). Installations from before that build still have the
    // shorter table names and need a rename, not a fresh install.
    if ($oldversion < 2026062837) {
        $renames = [
            'local_calcategories'       => 'local_calendarcategories_cats',
            'local_calcategory_members' => 'local_calendarcategories_members',
            'local_calcategory_events'  => 'local_calendarcategories_events',
        ];

        foreach ($renames as $oldname => $newname) {
            $oldtable = new xmldb_table($oldname);
            if ($dbman->table_exists($oldtable) && !$dbman->table_exists(new xmldb_table($newname))) {
                $dbman->rename_table($oldtable, $newname);
            }
        }

        upgrade_plugin_savepoint(true, 2026062837, 'local', 'calendarcategories');
    }

    // Reparaturschritt fuer Installationen, bei denen ein frueherer Erstinstall
    // nur den Versionseintrag angelegt, aber keine Tabellen erzeugt hat (zum
    // Beispiel durch einen zwischenzeitlichen Fehler in install.xml). Der
    // Schritt laeuft bei jedem Upgrade und legt fehlende Tabellen ohne
    // Datenverlust nach, sofern sie nicht bereits vorhanden sind.
    local_calendarcategories_ensure_tables($dbman);

    // Build 2026062848: Feld fuer optionalen Selbstbeitritt ergaenzt.
    if ($oldversion < 2026062848) {
        $table = new xmldb_table('local_calendarcategories_cats');
        $field = new xmldb_field('selfenrol', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'sortorder');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026062848, 'local', 'calendarcategories');
    }

    return true;
}

/**
 * Create the plugin's tables if they are missing, matching db/install.xml.
 *
 * Idempotent: does nothing for tables that already exist.
 *
 * @param database_manager $dbman The Moodle database manager.
 */
function local_calendarcategories_ensure_tables(database_manager $dbman): void {

    // Table local_calendarcategories_cats.
    $table = new xmldb_table('local_calendarcategories_cats');
    if (!$dbman->table_exists($table)) {
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null);
        $table->add_field('color', XMLDB_TYPE_CHAR, '7', null, XMLDB_NOTNULL, null, '#3a87ad');
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('selfenrol', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_context', XMLDB_KEY_FOREIGN, ['contextid'], 'context', ['id']);
        $table->add_key('fk_usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_index('idx_contextid_visible', XMLDB_INDEX_NOTUNIQUE, ['contextid', 'visible']);
        $dbman->create_table($table);
    }

    // Table local_calendarcategories_members.
    $table = new xmldb_table('local_calendarcategories_members');
    if (!$dbman->table_exists($table)) {
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('categoryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_category', XMLDB_KEY_FOREIGN, ['categoryid'], 'local_calendarcategories_cats', ['id']);
        $table->add_key('fk_user', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_index('idx_cat_user', XMLDB_INDEX_UNIQUE, ['categoryid', 'userid']);
        $dbman->create_table($table);
    }

    // Table local_calendarcategories_events.
    $table = new xmldb_table('local_calendarcategories_events');
    if (!$dbman->table_exists($table)) {
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('categoryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('eventid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_category', XMLDB_KEY_FOREIGN, ['categoryid'], 'local_calendarcategories_cats', ['id']);
        $table->add_key('fk_event', XMLDB_KEY_FOREIGN, ['eventid'], 'event', ['id']);
        $table->add_index('idx_cat_event', XMLDB_INDEX_UNIQUE, ['categoryid', 'eventid']);
        $dbman->create_table($table);
    }
}
