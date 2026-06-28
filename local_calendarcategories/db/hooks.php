<?php
// This file is part of Moodle - https://moodle.org/
//
// @package    local_calendarcategories
// @copyright  2026 Moodle in Niedersachsen e. V.
// @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook'     => \core\hook\after_config::class,
        'callback' => \local_calendarcategories\hook\after_config::class . '::callback',
        'priority' => 500,
    ],
];
