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

namespace local_calendarcategories;

/**
 * Event observers for local_calendarcategories.
 *
 * @package    local_calendarcategories
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Remove event-category links when a calendar event is deleted.
     *
     * @param \core\event\calendar_event_deleted $event
     */
    public static function calendar_event_deleted(\core\event\calendar_event_deleted $event): void {
        global $DB;
        $DB->delete_records('local_calendarcategories_events', ['eventid' => (int)$event->objectid]);
    }

    /**
     * Remove user memberships when a user is deleted.
     *
     * @param \core\event\user_deleted $event
     */
    public static function user_deleted(\core\event\user_deleted $event): void {
        global $DB;
        $DB->delete_records('local_calendarcategories_members', ['userid' => (int)$event->objectid]);
    }
}
