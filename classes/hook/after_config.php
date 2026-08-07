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

namespace local_calendarcategories\hook;

/**
 * Hook callback for core\hook\after_config.
 * Injects the calendar link into the custom menu for teachers/managers/admins.
 *
 * @package    local_calendarcategories
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class after_config {
    /**
     * Callback executed after Moodle config is fully loaded.
     *
     * @param \core\hook\after_config $hook
     */
    public static function callback(\core\hook\after_config $hook): void {
        global $CFG;

        if (!isloggedin() || isguestuser()) {
            return;
        }

        try {
            $syscontext = \context_system::instance();
        } catch (\Exception $e) {
            return;
        }

        if (!is_siteadmin() && !has_capability('local/calendarcategories:addevent', $syscontext)) {
            return;
        }

        $label = get_string('pluginname', 'local_calendarcategories');
        $url   = '/local/calendarcategories/view.php';
        $entry = $label . '|' . $url . "\n";

        if (empty($CFG->custommenuitems)) {
            $CFG->custommenuitems = $entry;
        } else if (strpos($CFG->custommenuitems, $url) === false) {
            $CFG->custommenuitems = $entry . $CFG->custommenuitems;
        }
    }
}
