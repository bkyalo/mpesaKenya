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
// along with Moodle.  If not, see <http://www.gnu.org/>.

/**
 * M-Pesa Kenya payment gateway callback handler
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Disable debug messages and any output.
defined('NO_DEBUG_DISPLAY') || define('NO_DEBUG_DISPLAY', true);

define('NO_MOODLE_COOKIES', true);

global $CFG, $DB;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/payment/gateway/mpesakenya/lib.php');

// Get the raw POST data
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

// Log the incoming request
$logid = $DB->insert_record('paygw_mpesakenya_logs', [
    'timecreated' => time(),
    'request' => $payload,
    'response' => '',
    'transactionid' => $data['TransID'] ?? 'unknown',
    'status' => 'received'
]);

// Verify the request
if (empty($data) || !isset($data['Body']['stkCallback'])) {
    http_response_code(400);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid request']);
    exit;
}

$callback = $data['Body']['stkCallback'];
$checkoutRequestID = $callback['CheckoutRequestID'];
$resultCode = $callback['ResultCode'];
$resultDesc = $callback['ResultDesc'];

// Find the transaction
$transaction = $DB->get_record('payments', [
    'gateway_transaction_id' => $checkoutRequestID,
    'gateway' => 'mpesakenya'
]);

if (!$transaction) {
    // Log the error
    $DB->update_record('paygw_mpesakenya_logs', [
        'id' => $logid,
        'response' => 'Transaction not found',
        'status' => 'error'
    ]);
    
    http_response_code(404);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Transaction not found']);
    exit;
}

// Process the callback
if ($resultCode === 0) {
    // Payment was successful
    $metadata = $callback['CallbackMetadata']['Item'] ?? [];
    $mpesaReceiptNumber = '';
    $phoneNumber = '';
    $amount = 0;
    $transactionDate = '';
    
    // Extract metadata
    foreach ($metadata as $item) {
        switch ($item['Name']) {
            case 'Amount':
                $amount = $item['Value'];
                break;
            case 'MpesaReceiptNumber':
                $mpesaReceiptNumber = $item['Value'];
                break;
            case 'PhoneNumber':
                $phoneNumber = $item['Value'];
                break;
            case 'TransactionDate':
                $transactionDate = $item['Value'];
                break;
        }
    }
    
    // Update the transaction
    $transaction->status = 'complete';
    $transaction->timemodified = time();
    $transaction->gateway_receipt = $mpesaReceiptNumber;
    $transaction->rawdata = json_encode($data);
    $DB->update_record('payments', $transaction);
    
    // Trigger payment complete event
    $event = \paygw_mpesakenya\event\payment_completed::create([
        'context' => context_system::instance(),
        'objectid' => $transaction->id,
        'other' => [
            'orderid' => $transaction->itemid,
            'amount' => $amount,
            'receipt' => $mpesaReceiptNumber
        ]
    ]);
    $event->trigger();
    
    // Log the success
    $DB->update_record('paygw_mpesakenya_logs', [
        'id' => $logid,
        'response' => 'Payment processed successfully',
        'status' => 'success',
        'transactionid' => $mpesaReceiptNumber
    ]);
    
    // Send success response
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'The service was accepted successfully']);
    
} else {
    // Payment failed
    $transaction->status = 'failed';
    $transaction->timemodified = time();
    $transaction->rawdata = json_encode($data);
    $DB->update_record('payments', $transaction);
    
    // Log the failure
    $DB->update_record('paygw_mpesakenya_logs', [
        'id' => $logid,
        'response' => $resultDesc,
        'status' => 'failed'
    ]);
    
    // Send failure response
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => $resultDesc]);
}
