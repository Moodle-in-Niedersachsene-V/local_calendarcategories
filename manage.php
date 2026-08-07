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
 * Management page for calendar groups.
 *
 * Access: local/calendarcategories:manage in CONTEXT_SYSTEM.
 *
 * @package    local_calendarcategories
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
$context = context_system::instance();
require_capability('local/calendarcategories:manage', $context);

$action     = optional_param('action', 'list', PARAM_ALPHAEXT);
$categoryid = optional_param('id', 0, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/calendarcategories/manage.php'));
$PAGE->set_title(get_string('pluginname', 'local_calendarcategories'));
$PAGE->set_heading(get_string('pluginname', 'local_calendarcategories'));
$PAGE->set_pagelayout('admin');

// CSRF token for all write actions.
// Sesskey wird nur bei Schreibaktionen geprüft (siehe unten).

// ---------------------------------------------------------------------------
// Handle write actions (require sesskey).
// ---------------------------------------------------------------------------
if (in_array($action, ['delete', 'togglevisible'])) {
    require_sesskey();

    if ($action === 'delete' && $categoryid) {
        \local_calendarcategories\category_manager::delete($categoryid);
        redirect(
            new moodle_url('/local/calendarcategories/manage.php'),
            get_string('categorydeleted', 'local_calendarcategories'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    if ($action === 'togglevisible' && $categoryid) {
        global $DB;
        $cat = $DB->get_record('local_calcategories', ['id' => $categoryid], 'id, visible', MUST_EXIST);
        \local_calendarcategories\category_manager::update($categoryid, ['visible' => (int)!$cat->visible]);
        redirect(new moodle_url('/local/calendarcategories/manage.php'));
    }
}

// ---------------------------------------------------------------------------
// Output.
// ---------------------------------------------------------------------------
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managecategories', 'local_calendarcategories'));

// "Add" button.
$addUrl = new moodle_url('/local/calendarcategories/edit.php', ['action' => 'add']);
echo $OUTPUT->single_button($addUrl, get_string('addcategory', 'local_calendarcategories'), 'get');

// List existing categories.
global $DB;
$categories = $DB->get_records('local_calcategories', null, 'sortorder ASC');

if ($categories) {
    $table = new html_table();
    $table->head = [
        get_string('categoryname', 'local_calendarcategories'),
        get_string('color', 'local_calendarcategories'),
        get_string('visible'),
        get_string('actions'),
    ];

    foreach ($categories as $cat) {
        $editUrl   = new moodle_url('/local/calendarcategories/edit.php', ['id' => $cat->id]);
        $deleteUrl = new moodle_url(
            '/local/calendarcategories/manage.php',
            ['action' => 'delete', 'id' => $cat->id, 'sesskey' => sesskey()]
        );
        $toggleUrl = new moodle_url(
            '/local/calendarcategories/manage.php',
            ['action' => 'togglevisible', 'id' => $cat->id, 'sesskey' => sesskey()]
        );

        $colorBox = html_writer::tag(
            'span',
            '&nbsp;&nbsp;&nbsp;&nbsp;',
            ['style' => "background:{$cat->color};border:1px solid #999;display:inline-block;"]
        );

        $table->data[] = [
            format_string($cat->name),
            $colorBox . ' ' . s($cat->color),
            $cat->visible
                ? $OUTPUT->pix_icon('i/show', get_string('visible'))
                : $OUTPUT->pix_icon('i/hide', get_string('notvisible')),
            html_writer::link($editUrl, get_string('edit')) . ' | ' .
            html_writer::link($toggleUrl, $cat->visible ? get_string('hide') : get_string('show')) . ' | ' .
            html_writer::link(
                $deleteUrl,
                get_string('delete'),
                ['onclick' => 'return confirm(' . json_encode(get_string('confirmdelete', 'local_calendarcategories')) . ')']
            ),
        ];
    }

    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('nocategories', 'local_calendarcategories'), 'info');
}

echo $OUTPUT->footer();
