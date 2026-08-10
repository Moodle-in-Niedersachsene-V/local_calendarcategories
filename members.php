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
 * Add or remove members of a calendar group.
 *
 * Classic two-listbox interface, matching Moodle's own cohort and role
 * assignment pages. No AJAX or AMD module involved.
 *
 * @package    local_calendarcategories
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/user/selector/lib.php');

use local_calendarcategories\category_manager;
use local_calendarcategories\existing_member_selector;
use local_calendarcategories\potential_member_selector;

$categoryid = required_param('categoryid', PARAM_INT);

global $DB;
$category = $DB->get_record('local_calendarcategories_cats', ['id' => $categoryid], '*', MUST_EXIST);
$context  = context::instance_by_id($category->contextid, MUST_EXIST);

require_login();
require_capability('local/calendarcategories:manage', $context);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/calendarcategories/members.php', ['categoryid' => $categoryid]));
$PAGE->set_title(get_string('managemembers', 'local_calendarcategories'));
$PAGE->set_heading(get_string('managemembers', 'local_calendarcategories'));
$PAGE->set_pagelayout('admin');
$PAGE->requires->css('/local/calendarcategories/styles/calendarcategories.css');

$potentialselector = new potential_member_selector('addselect', ['categoryid' => $categoryid]);
$existingselector   = new existing_member_selector('removeselect', ['categoryid' => $categoryid]);

// Handle "add" submission.
if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
    $userstoadd = $potentialselector->get_selected_users();
    if (!empty($userstoadd)) {
        foreach ($userstoadd as $usertoadd) {
            category_manager::add_member($categoryid, (int)$usertoadd->id);
        }
        $potentialselector->invalidate_selected_users();
        $existingselector->invalidate_selected_users();
    }
}

// Handle "remove" submission.
if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
    $userstoremove = $existingselector->get_selected_users();
    if (!empty($userstoremove)) {
        foreach ($userstoremove as $usertoremove) {
            category_manager::remove_member($categoryid, (int)$usertoremove->id);
        }
        $potentialselector->invalidate_selected_users();
        $existingselector->invalidate_selected_users();
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(
    get_string('managemembers', 'local_calendarcategories') . ': ' . format_string($category->name)
);

$backurl = new moodle_url('/local/calendarcategories/manage.php');
echo html_writer::div(
    html_writer::link($backurl, '&laquo; ' . get_string('managecategories', 'local_calendarcategories')),
    'mb-3'
);

echo html_writer::start_tag('form', [
    'id'     => 'lcc-members-form',
    'action' => $PAGE->url->out(),
    'method' => 'post',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'categoryid', 'value' => $categoryid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

echo html_writer::start_div('lcc-members-columns');

echo html_writer::start_div('lcc-members-column');
echo html_writer::tag('p', get_string('currentmembers', 'local_calendarcategories'));
echo $existingselector->display(true);
echo html_writer::end_div();

echo html_writer::start_div('lcc-members-actions');
echo html_writer::tag(
    'button',
    '&laquo; ' . get_string('add'),
    ['type' => 'submit', 'name' => 'add', 'value' => '1', 'class' => 'btn btn-secondary mb-2']
);
echo html_writer::tag(
    'button',
    get_string('remove') . ' &raquo;',
    ['type' => 'submit', 'name' => 'remove', 'value' => '1', 'class' => 'btn btn-secondary']
);
echo html_writer::end_div();

echo html_writer::start_div('lcc-members-column');
echo html_writer::tag('p', get_string('potentialmembers', 'local_calendarcategories'));
echo $potentialselector->display(true);
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
