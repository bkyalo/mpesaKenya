<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Capability definitions for the M-Pesa Kenya payment gateway
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    // Ability to configure M-Pesa Kenya payment gateway
    'paygw/mpesakenya:config' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW
        ],
        'clonepermissionsfrom' => 'moodle/site:config'
    ],
    
    // Ability to view M-Pesa Kenya payment transactions
    'paygw/mpesakenya:viewtransactions' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW
        ]
    ],
    
    // Ability to process refunds
    'paygw/mpesakenya:refund' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW
        ]
    ]
];

// Add role assignments for default roles
$role_assignments = [
    'manager' => [
        'paygw/mpesakenya:config',
        'paygw/mpesakenya:viewtransactions',
        'paygw/mpesakenya:refund'
    ]
];

// This code will be executed after the plugin is installed
$post_install = function() use ($role_assignments) {
    global $DB;
    
    // Assign capabilities to roles
    foreach ($role_assignments as $role_shortname => $capabilities) {
        if ($role = $DB->get_record('role', ['shortname' => $role_shortname])) {
            $context = context_system::instance();
            foreach ($capabilities as $capability) {
                assign_capability($capability, CAP_ALLOW, $role->id, $context->id, true);
            }
        }
    }
    
    // Trigger capability update
    reload_all_capabilities();
};

// Register the post-install function
$observers = [
    [
        'eventname' => '\core\event\role_capabilities_updated',
        'callback' => $post_install,
        'includefile' => ''
    ]
];
