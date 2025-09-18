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
 * M-Pesa Kenya payment gateway language strings
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin information
$string['pluginname'] = 'M-Pesa Kenya';
$string['pluginname_desc'] = 'M-Pesa Kenya payment gateway for Moodle';

// Gateway settings
$string['clientid'] = 'Consumer Key';
$string['clientid_help'] = 'Enter your M-Pesa Daraja API Consumer Key';
$string['clientsecret'] = 'Consumer Secret';
$string['clientsecret_help'] = 'Enter your M-Pesa Daraja API Consumer Secret';
$string['shortcode'] = 'Paybill/Till Number';
$string['shortcode_help'] = 'Enter your M-Pesa Paybill or Till Number';
$string['environment'] = 'Environment';
$string['environment_help'] = 'Select whether to use the sandbox (test) or production environment';
$string['sandbox'] = 'Sandbox (Test)';
$string['production'] = 'Production';
$string['initiator_name'] = 'Initiator Name';
$string['initiator_name_help'] = 'The name of the M-Pesa API Initiator';
$string['security_credential'] = 'Security Credential';
$string['security_credential_help'] = 'The encrypted credential for authenticating the transaction request';
$string['passkey'] = 'Passkey';
$string['passkey_help'] = 'The M-Pesa API passkey';

// Payment process
$string['payment_pending'] = 'Payment pending. Please check your phone to complete the M-Pesa payment.';
$string['payment_successful'] = 'Payment received successfully!';
$string['payment_failed'] = 'Payment failed. Please try again.';
$string['payment_cancelled'] = 'Payment was cancelled.';
$string['payment_timeout'] = 'Payment request timed out. Please try again.';
$string['payment_error'] = 'An error occurred while processing your payment. Please contact support.';

// Errors
$string['error:token_request_failed'] = 'Failed to get access token from M-Pesa API: {$a}';
$string['error:invalid_token_response'] = 'Invalid response when requesting access token';
$string['error:stk_push_failed'] = 'Failed to initiate STK push: {$a}';
$string['error:query_failed'] = 'Failed to query transaction status: {$a}';
$string['error:invalid_phone'] = 'Invalid phone number. Please use the format 2547XXXXXXXX';
$string['error:invalid_amount'] = 'Invalid amount. Amount must be a positive number';
$string['error:missing_config'] = 'M-Pesa payment gateway is not properly configured';
$string['error:transaction_not_found'] = 'Transaction not found';
$string['error:transaction_failed'] = 'Transaction failed: {$a}';

// Transaction status
$string['status:pending'] = 'Pending';
$string['status:success'] = 'Completed';
$string['status:failed'] = 'Failed';
$string['status:cancelled'] = 'Cancelled';

// Other
$string['pay_via_mpesa'] = 'Pay via M-Pesa';
$string['enter_phone_number'] = 'Enter your M-Pesa phone number (format: 2547XXXXXXXX)';
$string['phone_number'] = 'Phone Number';
$string['phone_number_help'] = 'Enter your M-Pesa registered phone number starting with 254 (e.g., 254712345678)';
$string['initiating_payment'] = 'Initiating M-Pesa payment...';
$string['checking_status'] = 'Checking payment status...';
$string['transaction_reference'] = 'Transaction Reference';
$string['receipt_number'] = 'M-Pesa Receipt Number';
$string['check_status'] = 'Check Payment Status';
$string['complete_payment'] = 'Complete Payment via M-Pesa';

// Errors
$string['error_missing_config'] = 'M-Pesa payment gateway is not properly configured.';
$string['error_missing_phone'] = 'Your phone number is required to process M-Pesa payments. Please update your profile with a valid phone number.';
$string['error_invalid_amount'] = 'Invalid payment amount.';
$string['error_api'] = 'An error occurred while communicating with M-Pesa: {$a}';
$string['error_auth'] = 'Authentication failed. Please check your API credentials.';
$string['error_processing'] = 'Error processing payment: {$a}';

// Capabilities
$string['mpesakenya:manage'] = 'Manage M-Pesa Kenya payment gateway';
$string['mpesakenya:view'] = 'View M-Pesa Kenya payment gateway';

// Privacy API
$string['privacy:metadata:paygw_mpesakenya'] = 'Stores M-Pesa payment information';
$string['privacy:metadata:paygw_mpesakenya:userid'] = 'The ID of the user making the payment';

// Monitoring
$string['mpesadashboard'] = 'M-Pesa Gateway Dashboard';
$string['monitoring'] = 'Monitoring';
$string['monitoring:status'] = 'Status';
$string['monitoring:lastchecked'] = 'Last checked';
$string['monitoring:checkstatus'] = 'Check Status';
$string['monitoring:apistatus'] = 'API Status';
$string['monitoring:transactions'] = 'Transactions';
$string['monitoring:failedtx'] = 'Failed Transactions';
$string['monitoring:pendingtx'] = 'Pending Transactions';
$string['monitoring:dbstatus'] = 'Database Status';
$string['monitoring:configstatus'] = 'Configuration Status';
$string['monitoring:ok'] = 'OK';
$string['monitoring:warning'] = 'Warning';
$string['monitoring:error'] = 'Error';
$string['monitoring:viewdashboard'] = 'View M-Pesa Dashboard';
$string['monitoring:runmonitor'] = 'Run M-Pesa Monitor';
$string['monitoring:statusok'] = 'All systems operational';
$string['monitoring:statuswarning'] = 'Some issues detected';
$string['monitoring:statuserror'] = 'Critical issues detected';
$string['monitoring:refresh'] = 'Refresh';
$string['monitoring:viewraw'] = 'View Raw JSON';
$string['monitoring:apiconnectivity'] = 'API Connectivity';
$string['monitoring:recentfailures'] = 'Recent Failures';
$string['monitoring:stale'] = 'Stale Pending';
$string['monitoring:dbconnection'] = 'Database Connection';
$string['monitoring:configuration'] = 'Configuration';
$string['monitoring:missingconfig'] = 'Missing configuration';
$string['monitoring:insecureconfig'] = 'Insecure configuration';
$string['monitoring:environment'] = 'Environment';
$string['monitoring:callbackurl'] = 'Callback URL';
$string['monitoring:alertsent'] = 'Alerts have been sent to the site administrator.';
$string['privacy:metadata:paygw_mpesakenya:transactionid'] = 'The transaction ID from M-Pesa';
$string['privacy:metadata:paygw_mpesakenya:paymentid'] = 'The payment ID in Moodle';
$string['privacy:metadata:paygw_mpesakenya:checkoutrequestid'] = 'The checkout request ID from M-Pesa';
$string['privacy:metadata:paygw_mpesakenya:merchantrequestid'] = 'The merchant request ID from M-Pesa';
$string['privacy:metadata:paygw_mpesakenya:reference'] = 'The payment reference';
$string['privacy:metadata:paygw_mpesakenya:amount'] = 'The payment amount';
$string['privacy:metadata:paygw_mpesakenya:currency'] = 'The payment currency';
$string['privacy:metadata:paygw_mpesakenya:status'] = 'The payment status';
$string['privacy:metadata:paygw_mpesakenya:phonenumber'] = 'The phone number used for payment';
$string['privacy:metadata:paygw_mpesakenya:mpesareceipt'] = 'The M-Pesa receipt number';
$string['privacy:metadata:paygw_mpesakenya:timecreated'] = 'When the payment was initiated';
$string['privacy:metadata:paygw_mpesakenya:timemodified'] = 'When the payment was last updated';

// Logs
$string['privacy:metadata:paygw_mpesakenya_logs'] = 'Logs M-Pesa API requests and responses';
$string['privacy:metadata:paygw_mpesakenya_logs:transactionid'] = 'The transaction ID this log entry is associated with';
$string['privacy:metadata:paygw_mpesakenya_logs:logdata'] = 'The log data';
$string['privacy:metadata:paygw_mpesakenya_logs:type'] = 'The type of log entry';
$string['privacy:metadata:paygw_mpesakenya_logs:timecreated'] = 'When the log entry was created';

// Cleanup task
$string['cleanuptask'] = 'M-Pesa Kenya cleanup task';
$string['privacy:metadata:paygw_mpesakenya:transactionid'] = 'The transaction ID from M-Pesa';
$string['privacy:metadata:paygw_mpesakenya:amount'] = 'The amount paid';
$string['privacy:metadata:paygw_mpesakenya:phone'] = 'The phone number used for payment';
$string['privacy:metadata:paygw_mpesakenya:timecreated'] = 'The time when the payment was initiated';
$string['privacy:metadata:paygw_mpesakenya:timemodified'] = 'The time when the payment status was last updated';
