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
 * Message definitions for the M-Pesa Kenya payment gateway
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$messageproviders = [
    // Payment notifications to users
    'payment_received' => [
        'capability' => 'paygw/mpesakenya:receivenotifications',
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN,
            'email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN,
            'moodle' => [
                'capability' => 'paygw/mpesakenya:receivenotifications',
                'defaults' => [
                    'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN,
                    'email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN,
                ],
            ],
        ],
    ],
    
    // Payment notifications to administrators
    'payment_notification' => [
        'capability' => 'paygw/mpesakenya:receivenotifications',
        'defaults' => [
            'email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN,
        ],
    ],
];

// Define the message templates
$message = [
    // Payment received confirmation
    [
        'name' => 'payment_received',
        'subject' => 'Payment Received',
        'fullmessage' => 'Dear {user_firstname},\n\nYour payment of {amount} {currency} has been received successfully.\n\nPayment Details:\n- Transaction ID: {transaction_id}\n- Date: {date}\n- Reference: {reference}\n\nThank you for your payment.\n\nThis is an automated message, please do not reply.',
        'fullmessageformat' => FORMAT_PLAIN,
        'fullmessagehtml' => '<p>Dear {user_firstname},</p>
<p>Your payment of {amount} {currency} has been received successfully.</p>
<p><strong>Payment Details:</strong><br />
- Transaction ID: {transaction_id}<br />
- Date: {date}<br />
- Reference: {reference}</p>
<p>Thank you for your payment.</p>
<p><em>This is an automated message, please do not reply.</em></p>',
        'smallmessage' => 'Your payment of {amount} {currency} has been received. Transaction ID: {transaction_id}',
    ],
    
    // Payment failed notification
    [
        'name' => 'payment_failed',
        'subject' => 'Payment Failed',
        'fullmessage' => 'Dear {user_firstname},\n\nWe were unable to process your payment of {amount} {currency}.\n\nTransaction ID: {transaction_id}\nError: {error_message}\n\nPlease try again or contact support if the issue persists.\n\nThis is an automated message, please do not reply.',
        'fullmessageformat' => FORMAT_PLAIN,
        'fullmessagehtml' => '<p>Dear {user_firstname},</p>
<p>We were unable to process your payment of {amount} {currency}.</p>
<p><strong>Transaction Details:</strong><br />
- Transaction ID: {transaction_id}<br />
- Error: {error_message}</p>
<p>Please try again or contact support if the issue persists.</p>
<p><em>This is an automated message, please do not reply.</em></p>',
        'smallmessage' => 'Your payment of {amount} {currency} failed. Error: {error_message}',
    ],
    
    // Admin notification for payment received
    [
        'name' => 'admin_payment_received',
        'subject' => 'New Payment Received - {site_name}',
        'fullmessage' => 'A new payment has been received through M-Pesa Kenya.\n\nPayment Details:\n- User: {user_fullname} ({user_email})\n- Amount: {amount} {currency}\n- Transaction ID: {transaction_id}\n- Reference: {reference}\n- Date: {date}\n\nView transaction: {transaction_url}',
        'fullmessageformat' => FORMAT_PLAIN,
        'fullmessagehtml' => '<p>A new payment has been received through M-Pesa Kenya.</p>
<p><strong>Payment Details:</strong><br />
- User: {user_fullname} ({user_email})<br />
- Amount: {amount} {currency}<br />
- Transaction ID: {transaction_id}<br />
- Reference: {reference}<br />
- Date: {date}</p>
<p><a href="{transaction_url}">View transaction details</a></p>',
        'smallmessage' => 'New payment received from {user_fullname} for {amount} {currency}',
    ]
];

// Register the message templates
$messagetemplates = [];
foreach ($message as $template) {
    $messagetemplates[] = (object)[
        'name' => $template['name'],
        'subject' => $template['subject'],
        'fullmessage' => $template['fullmessage'],
        'fullmessageformat' => $template['fullmessageformat'],
        'fullmessagehtml' => $template['fullmessagehtml'],
        'smallmessage' => $template['smallmessage'],
        'component' => 'paygw_mpesakenya',
        'custom' => 0,
    ];
}

// This function is called after install to add the message templates
function paygw_mpesakenya_add_message_templates() {
    global $DB, $CFG;
    
    require_once($CFG->dirroot . '/message/lib.php');
    
    // Get the message providers
    $messageproviders = message_get_providers();
    $provider = 'payment_received';
    
    // Add message templates
    foreach ($messagetemplates as $template) {
        // Check if the template already exists
        if (!$DB->record_exists('message_providers', ['component' => 'paygw_mpesakenya', 'name' => $template->name])) {
            $messageprovider = new stdClass();
            $messageprovider->name = $template->name;
            $messageprovider->component = 'paygw_mpesakenya';
            $messageprovider->capability = 'paygw/mpesakenya:receivenotifications';
            $DB->insert_record('message_providers', $messageprovider);
        }
        
        // Add the message template
        if (!$DB->record_exists('message_templates', ['eventtype' => $template->name, 'component' => 'paygw_mpesakenya'])) {
            $DB->insert_record('message_templates', $template);
        }
    }
}

// Register the post-install function
$observers = [
    [
        'eventname' => '\core\event\role_capabilities_updated',
        'callback' => 'paygw_mpesakenya_add_message_templates',
        'includefile' => '/payment/gateway/mpesakenya/db/messages.php'
    ]
];
