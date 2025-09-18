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
 * M-Pesa Kenya payment status check
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/payment/gateway/mpesakenya/lib.php');

// Require login and session
require_login(null, false);
require_sesskey();

// Get the transaction ID
$transactionid = required_param('id', PARAM_INT);

// Get the transaction record
$transaction = $DB->get_record('payments', ['id' => $transactionid], '*', MUST_EXIST);

// Verify the transaction belongs to the current user
if ($transaction->userid != $USER->id) {
    throw new moodle_exception('invalidtransaction', 'paygw_mpesakenya');
}

// Prepare the response
$response = [
    'success' => false,
    'status' => 'pending',
    'message' => get_string('payment_pending', 'paygw_mpesakenya'),
    'redirect' => ''
];

// Check the transaction status
switch ($transaction->status) {
    case 'complete':
        $response['success'] = true;
        $response['status'] = 'complete';
        $response['message'] = get_string('payment_successful', 'paygw_mpesakenya');
        $response['redirect'] = new moodle_url('/payment/return.php', [
            'contextid' => $transaction->contextid,
            'component' => $transaction->component,
            'paymentarea' => $transaction->paymentarea,
            'itemid' => $transaction->itemid
        ]);
        break;
        
    case 'failed':
        $response['status'] = 'failed';
        $response['message'] = get_string('payment_failed', 'paygw_mpesakenya');
        break;
        
    case 'cancelled':
        $response['status'] = 'cancelled';
        $response['message'] = get_string('payment_cancelled', 'paygw_mpesakenya');
        break;
}

// If still pending, check with M-Pesa API
if ($response['status'] === 'pending') {
    try {
        // Get the gateway configuration
        $gateway = $DB->get_record('payment_gateways', ['id' => $transaction->gatewayid], '*', MUST_EXIST);
        $config = json_decode($gateway->config, true);
        
        // Check transaction status via M-Pesa API
        $endpoint = $config['environment'] === 'sandbox'
            ? 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query'
            : 'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query';
        
        $timestamp = date('YmdHis');
        $password = base64_encode($config['shortcode'] . $config['passkey'] . $timestamp);
        
        $data = [
            'BusinessShortCode' => $config['shortcode'],
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $transaction->gateway_transaction_id
        ];
        
        // Get access token
        $token = paygw_mpesakenya::get_access_token($config);
        
        // Make the API request
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
        
        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($result) {
            $result = json_decode($result, true);
            
            if (isset($result['ResultCode'])) {
                if ($result['ResultCode'] === '0') {
                    // Payment was successful
                    $transaction->status = 'complete';
                    $transaction->timemodified = time();
                    $transaction->gateway_receipt = $result['MpesaReceiptNumber'] ?? '';
                    $DB->update_record('payments', $transaction);
                    
                    $response['success'] = true;
                    $response['status'] = 'complete';
                    $response['message'] = get_string('payment_successful', 'paygw_mpesakenya');
                    $response['redirect'] = new moodle_url('/payment/return.php', [
                        'contextid' => $transaction->contextid,
                        'component' => $transaction->component,
                        'paymentarea' => $transaction->paymentarea,
                        'itemid' => $transaction->itemid
                    ]);
                } else {
                    // Payment failed or was cancelled
                    $transaction->status = 'failed';
                    $transaction->timemodified = time();
                    $transaction->rawdata = json_encode($result);
                    $DB->update_record('payments', $transaction);
                    
                    $response['status'] = 'failed';
                    $response['message'] = $result['ResultDesc'] ?? get_string('payment_failed', 'paygw_mpesakenya');
                }
            }
        }
    } catch (Exception $e) {
        // Log the error but don't expose details to the user
        error_log('M-Pesa status check error: ' . $e->getMessage());
        $response['message'] = get_string('payment_error', 'paygw_mpesakenya');
    }
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
