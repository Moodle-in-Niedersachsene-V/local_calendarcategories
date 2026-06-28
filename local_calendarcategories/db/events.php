<?php
// This file is part of Moodle - https://moodle.org/
//
// @package    local_calendarcategories
// @copyright  2026 Moodle in Niedersachsen e. V.
// @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later

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
