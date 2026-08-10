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
 * Self-service join/leave for calendar groups that allow self-enrolment.
 *
 * Access: local/calendarcategories:view in CONTEXT_SYSTEM. The actual
 * capability and selfenrol check happens inside category_manager.
 *
 * @package    local_calendarcategories
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/calendarcategories:view', $context);

$action     = required_param('action', PARAM_ALPHA);
$categoryid = required_param('categoryid', PARAM_INT);

require_sesskey();

$returnurl = new moodle_url('/local/calendarcategories/view.php');

if ($action === 'join') {
    \local_calendarcategories\category_manager::self_join($categoryid);
    redirect($returnurl, get_string('joinedcategory', 'local_calendarcategories'), null, \core\output\notification::NOTIFY_SUCCESS);
} else if ($action === 'leave') {
    \local_calendarcategories\category_manager::self_leave($categoryid);
    redirect($returnurl, get_string('leftcategory', 'local_calendarcategories'), null, \core\output\notification::NOTIFY_SUCCESS);
}

redirect($returnurl);
