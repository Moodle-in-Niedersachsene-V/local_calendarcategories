<?php
// This file is part of Moodle - https://moodle.org/
//
// @package    local_calendarcategories
// @copyright  2026 Moodle in Niedersachsen e. V.
// @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Eintrag unter Website-Administration → (Weitere Plugins) → Kalender-Kategorien.
    $ADMIN->add('localplugins',
        new admin_externalpage(
            'local_calendarcategories_manage',
            get_string('managecategories', 'local_calendarcategories'),
            new moodle_url('/local/calendarcategories/manage.php'),
            'local/calendarcategories:manage'
        )
    );
}
