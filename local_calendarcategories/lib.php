<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// @package    local_calendarcategories
// @copyright  2026 Moodle in Niedersachsen e. V.
// @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later

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
        $syscontext = context_system::instance();
    } catch (\Exception $e) {
        return;
    }

    if (!is_siteadmin() && !has_capability('local/calendarcategories:addevent', $syscontext)) {
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

