<?php
// This file is part of Moodle - https://moodle.org/
//
// @package    local_calendarcategories
// @copyright  2026 Moodle in Niedersachsen e. V.
// @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later

namespace local_calendarcategories;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/calendar/lib.php');

/**
 * Manager for events created natively inside the plugin.
 *
 * Events are stored as mdl_event records (type = CALENDAR_EVENT_TYPE_STANDARD,
 * eventtype = 'site') and immediately linked to the chosen category via
 * local_calcategory_events.  This keeps them compatible with the Moodle
 * calendar API while letting the plugin control visibility through category
 * membership.
 *
 * @package    local_calendarcategories
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
class event_manager {

    /**
     * Create a new event and link it to a category.
     *
     * @param  int    $categoryid   local_calcategories.id
     * @param  string $name         Event title.
     * @param  int    $timestart    Unix timestamp (start).
     * @param  int    $timeduration Duration in seconds (0 = no duration).
     * @param  string $description  HTML description.
     * @param  string $location     Optional location string.
     * @return int    New mdl_event.id
     * @throws \required_capability_exception
     * @throws \moodle_exception
     */
    public static function create_event(
        int    $categoryid,
        string $name,
        int    $timestart,
        int    $timeduration = 0,
        string $description  = '',
        string $location     = ''
    ): int {
        global $DB, $USER;

        // Capability check delegated to category_manager (also checks membership context).
        $cat     = $DB->get_record('local_calcategories', ['id' => $categoryid], '*', MUST_EXIST);
        $context = \context::instance_by_id($cat->contextid, MUST_EXIST);
        require_capability('local/calendarcategories:addevent', $context);

        // Validate inputs.
        if (empty(trim($name))) {
            throw new \moodle_exception('erroremptytitle', 'local_calendarcategories');
        }
        if ($timestart <= 0) {
            throw new \moodle_exception('errorinvaliddate', 'local_calendarcategories');
        }

        // Build the Moodle calendar event record.
        $eventdata = (object)[
            'name'           => clean_param($name, PARAM_TEXT),
            'description'    => clean_param($description, PARAM_CLEANHTML),
            'format'         => FORMAT_HTML,
            'location'       => clean_param($location, PARAM_TEXT),
            'courseid'       => 0,           // site-level event
            'groupid'        => 0,
            'userid'         => (int)$USER->id,
            'modulename'     => '',
            'eventtype'      => 'site',
            'timestart'      => $timestart,
            'timeduration'   => max(0, (int)$timeduration),
            'visible'        => 1,
            'sequence'       => 1,
            'timemodified'   => time(),
        ];

        // Use Moodle's calendar API so hooks/observers fire correctly.
        $event   = \calendar_event::create($eventdata, false);
        $eventid = (int)$event->id;

        // Link to category.
        $DB->insert_record('local_calcategory_events', [
            'categoryid'  => $categoryid,
            'eventid'     => $eventid,
            'timecreated' => time(),
        ]);

        return $eventid;
    }

    /**
     * Update an existing plugin event.
     *
     * @param  int   $eventid
     * @param  array $data  Keys: name, timestart, timeduration, description, location.
     * @return bool
     * @throws \required_capability_exception
     */
    public static function update_event(int $eventid, array $data): bool {
        global $DB;

        // Load event + resolve category context for capability check.
        $link = $DB->get_record('local_calcategory_events', ['eventid' => $eventid], '*', MUST_EXIST);
        $cat  = $DB->get_record('local_calcategories', ['id' => $link->categoryid], '*', MUST_EXIST);
        $context = \context::instance_by_id($cat->contextid, MUST_EXIST);
        require_capability('local/calendarcategories:addevent', $context);

        $calevent = \calendar_event::load($eventid);

        $update = new \stdClass();
        if (isset($data['name'])) {
            $update->name = clean_param($data['name'], PARAM_TEXT);
        }
        if (isset($data['timestart'])) {
            $update->timestart = (int)$data['timestart'];
        }
        if (isset($data['timeduration'])) {
            $update->timeduration = max(0, (int)$data['timeduration']);
        }
        if (isset($data['description'])) {
            $update->description = clean_param($data['description'], PARAM_CLEANHTML);
        }
        if (isset($data['location'])) {
            $update->location = clean_param($data['location'], PARAM_TEXT);
        }

        $calevent->update($update, false);
        return true;
    }

    /**
     * Delete a plugin event.
     *
     * @param  int  $eventid
     * @return bool
     * @throws \required_capability_exception
     */
    public static function delete_event(int $eventid): bool {
        global $DB;

        $link = $DB->get_record('local_calcategory_events', ['eventid' => $eventid]);
        if (!$link) {
            return false;   // Not a plugin event – don't touch it.
        }
        $cat     = $DB->get_record('local_calcategories', ['id' => $link->categoryid], '*', MUST_EXIST);
        $context = \context::instance_by_id($cat->contextid, MUST_EXIST);
        require_capability('local/calendarcategories:addevent', $context);

        $calevent = \calendar_event::load($eventid);
        $calevent->delete();   // Observer cleans up local_calcategory_events automatically.

        return true;
    }

    /**
     * Get upcoming events (next N days) across all active categories for the current user.
     *
     * @param  int $days     Look-ahead window in days (default 90).
     * @param  int $limit    Maximum number of events to return (default 200).
     * @return array  Rows from mdl_event with extra fields: categoryid, categoryname, categorycolor.
     */
    public static function get_upcoming_for_user(int $days = 90, int $limit = 200): array {
        global $DB, $USER;

        $now  = time();
        $end  = $now + ($days * DAYSECS);

        if (is_siteadmin()) {
            // Admins see all.
            $sql = 'SELECT e.*, c.id AS categoryid, c.name AS categoryname, c.color AS categorycolor
                      FROM {event} e
                      JOIN {local_calcategory_events} ce ON ce.eventid = e.id
                      JOIN {local_calcategories} c ON c.id = ce.categoryid
                     WHERE c.visible = 1
                       AND e.timestart >= :now
                       AND e.timestart < :end
                  ORDER BY e.timestart ASC';
            return array_values($DB->get_records_sql($sql, ['now' => $now, 'end' => $end], 0, $limit));
        }

        require_capability('local/calendarcategories:view', \context_system::instance());

        $sql = 'SELECT e.*, c.id AS categoryid, c.name AS categoryname, c.color AS categorycolor
                  FROM {event} e
                  JOIN {local_calcategory_events} ce ON ce.eventid = e.id
                  JOIN {local_calcategories} c ON c.id = ce.categoryid
                  JOIN {local_calcategory_members} m ON m.categoryid = c.id
                 WHERE c.visible = 1
                   AND m.userid  = :uid
                   AND e.timestart >= :now
                   AND e.timestart < :end
              ORDER BY e.timestart ASC';

        return array_values($DB->get_records_sql($sql, [
            'uid'  => (int)$USER->id,
            'now'  => $now,
            'end'  => $end,
        ], 0, $limit));
    }

    /**
     * Get all events in a specific month for the current user.
     *
     * @param  int $year
     * @param  int $month  1–12
     * @return array
     */
    public static function get_month_events(int $year, int $month): array {
        global $DB, $USER;

        $start = mktime(0, 0, 0, $month, 1, $year);
        $end   = mktime(0, 0, 0, ($month === 12 ? 1 : $month + 1), 1, ($month === 12 ? $year + 1 : $year));

        if (is_siteadmin()) {
            $sql = 'SELECT e.*, c.id AS categoryid, c.name AS categoryname, c.color AS categorycolor
                      FROM {event} e
                      JOIN {local_calcategory_events} ce ON ce.eventid = e.id
                      JOIN {local_calcategories} c ON c.id = ce.categoryid
                     WHERE c.visible = 1
                       AND e.timestart >= :s AND e.timestart < :en
                  ORDER BY e.timestart ASC';
            return array_values($DB->get_records_sql($sql, ['s' => $start, 'en' => $end]));
        }

        require_capability('local/calendarcategories:view', \context_system::instance());

        $sql = 'SELECT e.*, c.id AS categoryid, c.name AS categoryname, c.color AS categorycolor
                  FROM {event} e
                  JOIN {local_calcategory_events} ce ON ce.eventid = e.id
                  JOIN {local_calcategories} c ON c.id = ce.categoryid
                  JOIN {local_calcategory_members} m ON m.categoryid = c.id
                 WHERE c.visible = 1
                   AND m.userid  = :uid
                   AND e.timestart >= :s AND e.timestart < :en
              ORDER BY e.timestart ASC';

        return array_values($DB->get_records_sql($sql, [
            'uid' => (int)$USER->id,
            's'   => $start,
            'en'  => $end,
        ]));
    }
}
