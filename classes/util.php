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
 * Capability check that also looks at the user's own courses.
 *
 * Calendar groups are a site-wide concept (subject groups, leadership
 * teams) and therefore live in the system context. Teachers, however,
 * almost always receive their editingteacher role inside a specific
 * course, not at system level. A plain has_capability() check against the
 * system context only reflects roles assigned at system level or above,
 * so on its own it misses course-assigned teachers entirely, even though
 * they clearly should count as teachers for a site-wide feature like this.
 *
 * This helper additionally checks the user's own enrolled courses before
 * concluding that a capability is absent.
 *
 * @package    local_calendarcategories
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class util {
    /**
     * Check whether the current user holds a capability anywhere on the
     * site: at system level, or within any course they are enrolled in.
     *
     * @param string $capability Fully qualified capability name.
     * @return bool
     */
    public static function has_capability_anywhere(string $capability): bool {
        global $USER;

        if (has_capability($capability, \context_system::instance())) {
            return true;
        }

        $courses = enrol_get_users_courses($USER->id, true);
        foreach ($courses as $course) {
            if (has_capability($capability, \context_course::instance($course->id))) {
                return true;
            }
        }

        return false;
    }
}
