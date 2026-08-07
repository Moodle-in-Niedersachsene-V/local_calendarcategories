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
 * Capability definitions for local_calendarcategories.
 *
 * @package    local_calendarcategories
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @author     Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    // Manage (create/edit/delete) calendar groups.
    'local/calendarcategories:manage' => [
        'riskbitmask'  => RISK_SPAM | RISK_XSS,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'manager' => CAP_ALLOW,
        ],
    ],

    // Manage calendar groups at course-category level.
    'local/calendarcategories:managecategory' => [
        'riskbitmask'  => RISK_SPAM | RISK_XSS,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes'   => [
            'manager' => CAP_ALLOW,
        ],
    ],

    // Add events to a custom calendar group.
    'local/calendarcategories:addevent' => [
        'riskbitmask'  => RISK_SPAM,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW,
        ],
    ],

    // View events in calendar groups the user is a member of.
    'local/calendarcategories:view' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW,
            'student'        => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'user'           => CAP_ALLOW,
        ],
    ],
];
