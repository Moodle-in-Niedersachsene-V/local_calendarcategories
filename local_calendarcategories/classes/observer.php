<?php
// This file is part of Moodle - https://moodle.org/
//
// @package    local_calendarcategories
// @copyright  2026 Moodle in Niedersachsen e. V.
// @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later

namespace local_calendarcategories;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observers for local_calendarcategories.
 *
 * @package    local_calendarcategories
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
class observer {

    /**
     * Remove event-category links when a calendar event is deleted.
     *
     * @param \core\event\calendar_event_deleted $event
     */
    public static function calendar_event_deleted(\core\event\calendar_event_deleted $event): void {
        global $DB;
        $DB->delete_records('local_calcategory_events', ['eventid' => (int)$event->objectid]);
    }

    /**
     * Remove user memberships when a user is deleted.
     *
     * @param \core\event\user_deleted $event
     */
    public static function user_deleted(\core\event\user_deleted $event): void {
        global $DB;
        $DB->delete_records('local_calcategory_members', ['userid' => (int)$event->objectid]);
    }
}
