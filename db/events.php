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
 * Event observer definitions for local_calendarcategories.
 *
 * @package    local_calendarcategories
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    // Clean up category-event links when a calendar event is deleted.
    [
        'eventname'   => '\core\event\calendar_event_deleted',
        'callback'    => '\local_calendarcategories\observer::calendar_event_deleted',
        'includefile' => null,
        'internal'    => false,
        'priority'    => 0,
    ],
    // Clean up memberships when a user is deleted.
    [
        'eventname'   => '\core\event\user_deleted',
        'callback'    => '\local_calendarcategories\observer::user_deleted',
        'includefile' => null,
        'internal'    => false,
        'priority'    => 0,
    ],
];
