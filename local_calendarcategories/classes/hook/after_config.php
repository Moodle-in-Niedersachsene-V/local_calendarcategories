<?php
// This file is part of Moodle - https://moodle.org/
//
// @package    local_calendarcategories
// @copyright  2026 Moodle in Niedersachsen e. V.
// @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later

namespace local_calendarcategories\hook;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook callback for core\hook\after_config.
 * Injects the calendar link into the custom menu for teachers/managers/admins.
 *
 * @package    local_calendarcategories
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
class after_config {

    /**
     * Callback executed after Moodle config is fully loaded.
     *
     * @param \core\hook\after_config $hook
     */
    public static function callback(\core\hook\after_config $hook): void {
        global $CFG;

        if (!isloggedin() || isguestuser()) {
            return;
        }

        try {
            $syscontext = \context_system::instance();
        } catch (\Exception $e) {
            return;
        }

        if (!is_siteadmin() && !has_capability('local/calendarcategories:addevent', $syscontext)) {
            return;
        }

        $label = get_string('pluginname', 'local_calendarcategories');
        $url   = '/local/calendarcategories/view.php';
        $entry = $label . '|' . $url . "\n";

        if (empty($CFG->custommenuitems)) {
            $CFG->custommenuitems = $entry;
        } else if (strpos($CFG->custommenuitems, $url) === false) {
            $CFG->custommenuitems = $entry . $CFG->custommenuitems;
        }
    }
}
