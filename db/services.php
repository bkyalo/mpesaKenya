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
 * External functions and service definitions for the M-Pesa Kenya payment gateway.
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    // Get configuration for JavaScript
    'paygw_mpesakenya_get_config_for_js' => [
        'classname' => 'paygw_mpesakenya\\external\\get_config_for_js',
        'methodname' => 'execute',
        'description' => 'Returns the configuration settings required to initialize the M-Pesa payment UI',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
        'capabilities' => 'paygw/mpesakenya:view',
    ],
    
    // Start a new M-Pesa transaction
    'paygw_mpesakenya_transaction_start' => [
        'classname' => 'paygw_mpesakenya\\external\\transaction_start',
        'methodname' => 'execute',
        'description' => 'Initiates a new M-Pesa STK push transaction',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
        'capabilities' => 'paygw/mpesakenya:view',
    ],
    
    // Complete an M-Pesa transaction
    'paygw_mpesakenya_transaction_complete' => [
        'classname' => 'paygw_mpesakenya\\external\\transaction_complete',
        'methodname' => 'execute',
        'description' => 'Completes an M-Pesa transaction by verifying its status',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
        'capabilities' => 'paygw/mpesakenya:view',
    ],
];

// Define the services
$services = [
    'M-Pesa Kenya payment gateway' => [
        'functions' => [
            'paygw_mpesakenya_get_config_for_js',
            'paygw_mpesakenya_transaction_start',
            'paygw_mpesakenya_transaction_complete',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'paygw_mpesakenya',
        'downloadfiles' => 0,
        'uploadfiles' => 0,
    ],
];
