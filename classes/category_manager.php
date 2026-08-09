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
 * Core manager for custom calendar categories.
 *
 * All public methods that modify data perform capability checks internally.
 * Callers do NOT need to duplicate capability checks.
 *
 * @package    local_calendarcategories
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class category_manager {
    /**
     * Create a new calendar category.
     *
     * @param  string $name        Human-readable name.
     * @param  string $color       Hex color, e.g. '#3a87ad'.
     * @param  int    $contextid   Moodle context id (system, coursecat, …).
     * @param  string $description Optional description.
     * @param  int    $sortorder   Sort position.
     * @return int    New category id.
     * @throws \required_capability_exception
     */
    public static function create(
        string $name,
        string $color = '#3a87ad',
        int $contextid = 0,
        string $description = '',
        int $sortorder = 0
    ): int {
        global $DB, $USER;

        // Capability check.
        $context = $contextid
            ? \context::instance_by_id($contextid, MUST_EXIST)
            : \context_system::instance();
        require_capability('local/calendarcategories:manage', $context);

        // Validate color format.
        self::validate_color($color);

        $record = (object)[
            'name'         => clean_param($name, PARAM_TEXT),
            'description'  => clean_param($description, PARAM_CLEANHTML),
            'color'        => $color,
            'contextid'    => $context->id,
            'sortorder'    => (int)$sortorder,
            'visible'      => 1,
            'timecreated'  => time(),
            'timemodified' => time(),
            'usermodified' => (int)$USER->id,
        ];

        return $DB->insert_record('local_calendarcategories_cats', $record);
    }

    /**
     * Update an existing category.
     *
     * @param  int    $id
     * @param  array  $data Associative array of fields to update.
     * @return bool
     * @throws \required_capability_exception
     * @throws \dml_missing_record_exception
     */
    public static function update(int $id, array $data): bool {
        global $DB, $USER;

        $category = $DB->get_record('local_calendarcategories_cats', ['id' => $id], '*', MUST_EXIST);
        $context  = \context::instance_by_id($category->contextid, MUST_EXIST);
        require_capability('local/calendarcategories:manage', $context);

        $allowed = ['name', 'description', 'color', 'sortorder', 'visible'];
        $record  = (object)['id' => $id, 'timemodified' => time(), 'usermodified' => (int)$USER->id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === 'color') {
                    self::validate_color($data[$field]);
                }
                $record->$field = $field === 'name'
                    ? clean_param($data[$field], PARAM_TEXT)
                    : ($field === 'description'
                        ? clean_param($data[$field], PARAM_CLEANHTML)
                        : $data[$field]);
            }
        }

        return $DB->update_record('local_calendarcategories_cats', $record);
    }

    /**
     * Delete a category and all its members/event links.
     *
     * @param  int  $id
     * @return bool
     * @throws \required_capability_exception
     */
    public static function delete(int $id): bool {
        global $DB;

        $category = $DB->get_record('local_calendarcategories_cats', ['id' => $id], '*', MUST_EXIST);
        $context  = \context::instance_by_id($category->contextid, MUST_EXIST);
        require_capability('local/calendarcategories:manage', $context);

        $DB->delete_records('local_calendarcategories_members', ['categoryid' => $id]);
        $DB->delete_records('local_calendarcategories_events', ['categoryid' => $id]);
        return $DB->delete_records('local_calendarcategories_cats', ['id' => $id]);
    }

    /**
     * Add a user as member of a category.
     *
     * @param  int  $categoryid
     * @param  int  $userid
     * @return bool  false if already a member.
     * @throws \required_capability_exception
     */
    public static function add_member(int $categoryid, int $userid): bool {
        global $DB;

        $category = $DB->get_record('local_calendarcategories_cats', ['id' => $categoryid], '*', MUST_EXIST);
        $context  = \context::instance_by_id($category->contextid, MUST_EXIST);
        require_capability('local/calendarcategories:manage', $context);

        // Make sure user exists.
        if (!$DB->record_exists('user', ['id' => $userid, 'deleted' => 0])) {
            throw new \moodle_exception('invaliduser', 'local_calendarcategories');
        }

        if ($DB->record_exists('local_calendarcategories_members', ['categoryid' => $categoryid, 'userid' => $userid])) {
            return false;
        }

        $DB->insert_record('local_calendarcategories_members', [
            'categoryid'  => $categoryid,
            'userid'      => $userid,
            'timecreated' => time(),
        ]);
        return true;
    }

    /**
     * Remove a user from a category.
     *
     * @param  int  $categoryid
     * @param  int  $userid
     * @return bool
     * @throws \required_capability_exception
     */
    public static function remove_member(int $categoryid, int $userid): bool {
        global $DB;

        $category = $DB->get_record('local_calendarcategories_cats', ['id' => $categoryid], '*', MUST_EXIST);
        $context  = \context::instance_by_id($category->contextid, MUST_EXIST);
        require_capability('local/calendarcategories:manage', $context);

        return (bool)$DB->delete_records(
            'local_calendarcategories_members',
            ['categoryid' => $categoryid, 'userid' => $userid]
        );
    }

    /**
     * Link a Moodle calendar event to a category.
     *
     * @param  int  $categoryid
     * @param  int  $eventid    mdl_event.id
     * @return bool  false if already linked.
     * @throws \required_capability_exception
     */
    public static function link_event(int $categoryid, int $eventid): bool {
        global $DB;

        $category = $DB->get_record('local_calendarcategories_cats', ['id' => $categoryid], '*', MUST_EXIST);
        $context  = \context::instance_by_id($category->contextid, MUST_EXIST);
        require_capability('local/calendarcategories:addevent', $context);

        // Validate that the event exists.
        if (!$DB->record_exists('event', ['id' => $eventid])) {
            throw new \moodle_exception('invalidevent', 'local_calendarcategories');
        }

        if ($DB->record_exists('local_calendarcategories_events', ['categoryid' => $categoryid, 'eventid' => $eventid])) {
            return false;
        }

        $DB->insert_record('local_calendarcategories_events', [
            'categoryid'  => $categoryid,
            'eventid'     => $eventid,
            'timecreated' => time(),
        ]);
        return true;
    }

    /**
     * Get all categories visible to the current user.
     *
     * @return array of stdClass records from local_calendarcategories_cats.
     */
    public static function get_visible_categories(): array {
        global $DB, $USER;

        // Admins see all.
        if (is_siteadmin()) {
            return $DB->get_records('local_calendarcategories_cats', ['visible' => 1], 'sortorder ASC');
        }

        // Check view capability.
        if (!has_capability('local/calendarcategories:view', \context_system::instance())) {
            return [];
        }

        // Users see categories where they are members.
        $sql = 'SELECT c.*
                  FROM {local_calendarcategories_cats} c
                  JOIN {local_calendarcategories_members} m ON m.categoryid = c.id
                 WHERE c.visible = 1
                   AND m.userid  = :userid
              ORDER BY c.sortorder ASC';

        return $DB->get_records_sql($sql, ['userid' => (int)$USER->id]);
    }

    /**
     * Get events for a specific category that the current user may see.
     *
     * @param  int   $categoryid
     * @return array of stdClass from mdl_event.
     * @throws \required_capability_exception
     */
    public static function get_category_events(int $categoryid): array {
        global $DB, $USER;

        $category = $DB->get_record('local_calendarcategories_cats', ['id' => $categoryid], '*', MUST_EXIST);
        $context  = \context::instance_by_id($category->contextid, MUST_EXIST);
        require_capability('local/calendarcategories:view', $context);

        // Non-admins must be a member.
        if (!is_siteadmin()) {
            $ismember = $DB->record_exists(
                'local_calendarcategories_members',
                ['categoryid' => $categoryid, 'userid' => (int)$USER->id]
            );
            if (!$ismember) {
                throw new \required_capability_exception($context, 'local/calendarcategories:view', 'nopermissions', '');
            }
        }

        $sql = 'SELECT e.*
                  FROM {event} e
                  JOIN {local_calendarcategories_events} ce ON ce.eventid = e.id
                 WHERE ce.categoryid = :categoryid
              ORDER BY e.timestart ASC';

        return $DB->get_records_sql($sql, ['categoryid' => $categoryid]);
    }
    // Internal helpers.
    /**
     * Validate hex color string.
     *
     * @param  string $color
     * @throws \moodle_exception on invalid format.
     */
    private static function validate_color(string $color): void {
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            throw new \moodle_exception('invalidcolor', 'local_calendarcategories', '', $color);
        }
    }
}
