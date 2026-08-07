<?php
// This file is part of Moodle - https://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify.
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

defined('MOODLE_INTERNAL') || die();

/**
 * Global navigation: adds node to flat nav sidebar.
 * Visible for admins, managers, editingteachers.
 *
 * @param global_navigation $nav
 */
function local_calendarcategories_extend_navigation(global_navigation $nav): void {

    if (!isloggedin() || isguestuser()) {
        return;
    }

    try {
        $sys_context = context_system::instance();
    } catch (\Exception $e) {
        return;
    }

    if (!is_siteadmin() && !has_capability('local/calendarcategories:addevent', $sys_context)) {
        return;
    }

    $url  = new moodle_url('/local/calendarcategories/view.php');
    $node = $nav->add(
        get_string('pluginname', 'local_calendarcategories'),
        $url,
        navigation_node::TYPE_CUSTOM,
        get_string('pluginname', 'local_calendarcategories'),
        'local_calendarcategories',
        new pix_icon('i/calendar', '')
    );
    $node->showinflatnavigation = true;
    $node->isexpandable         = false;
}
