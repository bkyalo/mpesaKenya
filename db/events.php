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
 * Events for the M-Pesa Kenya payment gateway
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    // Payment created event
    [
        'eventname' => '\paygw_mpesakenya\\event\\payment_created',
        'callback' => 'paygw_mpesakenya_observer::payment_created',
        'internal' => false,
    ],
    
    // Payment completed event
    [
        'eventname' => '\paygw_mpesakenya\\event\\payment_completed',
        'callback' => 'paygw_mpesakenya_observer::payment_completed',
        'internal' => false,
    ],
    
    // Payment failed event
    [
        'eventname' => '\paygw_mpesakenya\\event\\payment_failed',
        'callback' => 'paygw_mpesakenya_observer::payment_failed',
        'internal' => false,
    ],
    
    // Payment refunded event
    [
        'eventname' => '\paygw_mpesakenya\\event\\payment_refunded',
        'callback' => 'paygw_mpesakenya_observer::payment_refunded',
        'internal' => false,
    ]
];

// Define the events
$events = [
    // Payment created
    'payment_created' => [
        'class' => '\paygw_mpesakenya\\event\\payment_created',
        'description' => 'A payment has been created',
        'edulevel' => '2', // Level 2 = teaching
        'crud' => 'c', // c(reate), r(ead), u(pdate), d(elete)
        'capture' => '0',
    ],
    
    // Payment completed
    'payment_completed' => [
        'class' => '\paygw_mpesakenya\\event\\payment_completed',
        'description' => 'A payment has been completed successfully',
        'edulevel' => '2',
        'crud' => 'u',
        'capture' => '0',
    ],
    
    // Payment failed
    'payment_failed' => [
        'class' => '\paygw_mpesakenya\\event\\payment_failed',
        'description' => 'A payment has failed',
        'edulevel' => '2',
        'crud' => 'u',
        'capture' => '0',
    ],
    
    // Payment refunded
    'payment_refunded' => [
        'class' => '\paygw_mpesakenya\\event\\payment_refunded',
        'description' => 'A payment has been refunded',
        'edulevel' => '2',
        'crud' => 'u',
        'capture' => '0',
    ]
];

// Register the events
foreach ($events as $event) {
    $event['component'] = 'paygw_mpesakenya';
    $event['internal'] = 0;
    $event['objecttable'] = 'paygw_mpesakenya_transactions';
    
    // Register the event
    $eventname = 'paygw_mpesakenya_' . $event['class']::NAME;
    $event['eventname'] = '\\' . $event['class'];
    $event['handlerfile'] = '/payment/gateway/mpesakenya/classes/observer.php';
    $event['handlerfunction'] = 'handle_' . $eventname;
    
    // Add to the list of events
    $observers[] = $event;
}
