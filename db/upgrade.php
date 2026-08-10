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

    return true;
}
