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
 * This class completes a payment with the M-Pesa Kenya payment gateway.
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace paygw_mpesakenya\external;

use context_system;
use context_user;
use core_payment\helper as payment_helper;
use core_external\{external_api, external_function_parameters, external_value, external_single_structure};
use paygw_mpesakenya\mpesa_helper;
use moodle_exception;

/**
 * This class completes a payment with the M-Pesa Kenya payment gateway.
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class transaction_complete extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'component' => new external_value(PARAM_COMPONENT, 'The component name'),
            'paymentarea' => new external_value(PARAM_AREA, 'Payment area in the component'),
            'itemid' => new external_value(PARAM_INT, 'The item id in the context of the component area'),
            'transactionid' => new external_value(PARAM_TEXT, 'The transaction ID from the payment initiation')
        ]);
    }

    /**
     * Complete the payment process by verifying the transaction status.
     *
     * @param string $component Name of the component that the itemid belongs to
     * @param string $paymentarea The payment area
     * @param int $itemid An internal identifier that is used by the component
     * @param string $transactionid The transaction ID from the payment initiation
     * @return array
     */
    public static function execute(
        string $component,
        string $paymentarea,
        int $itemid,
        string $transactionid
    ): array {
        global $DB, $USER;
        
        // Validate context and parameters.
        $usercontext = context_user::instance($USER->id);
        self::validate_context($usercontext);
        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);
        
        self::validate_parameters(self::execute_parameters(), [
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
            'transactionid' => $transactionid
        ]);
        
        // Get the transaction record.
        $transaction = $DB->get_record('paygw_mpesakenya_transactions', [
            'transactionid' => $transactionid,
            'userid' => $USER->id,
            'paymentid' => $itemid,
            'component' => $component,
            'paymentarea' => $paymentarea
        ], '*', MUST_EXIST);
        
        // If already completed, return success.
        if ($transaction->status === 'COMPLETED') {
            return [
                'success' => true,
                'transactionid' => $transactionid,
                'message' => get_string('payment_already_processed', 'paygw_mpesakenya')
            ];
        }
        
        // If failed, return failure.
        if ($transaction->status === 'FAILED' || $transaction->status === 'CANCELLED') {
            return [
                'success' => false,
                'transactionid' => $transactionid,
                'message' => get_string('payment_' . strtolower($transaction->status), 'paygw_mpesakenya')
            ];
        }
        
        // Get payment configuration and helper.
        $config = payment_helper::get_gateway_configuration($component, $paymentarea, $itemid, 'mpesakenya');
        $helper = new mpesa_helper($config);
        
        // Query transaction status from M-Pesa.
        $response = $helper->query_transaction_status($transaction->checkoutrequestid);
        
        if (isset($response['ResultCode']) && $response['ResultCode'] === '0') {
            // Transaction was successful.
            $resultparams = $response['ResultParameters']['ResultParameter'] ?? [];
            $resultdata = [];
            
            // Parse the result parameters.
            foreach ($resultparams as $param) {
                if (isset($param['Key']) && isset($param['Value'])) {
                    $resultdata[$param['Key']] = $param['Value'];
                }
            }
            
            // Update transaction record.
            $transaction->status = 'COMPLETED';
            $transaction->mpesareceipt = $resultdata['MpesaReceiptNumber'] ?? '';
            $transaction->transactiondate = $resultdata['TransactionDate'] ?? '';
            $transaction->phonenumber = $resultdata['PhoneNumber'] ?? $transaction->phonenumber;
            $transaction->amount = $resultdata['Amount'] ?? $transaction->amount;
            $transaction->timemodified = time();
            
            // Save the updated transaction.
            $DB->update_record('paygw_mpesakenya_transactions', $transaction);
            
            // Process the payment.
            $paymentid = payment_helper::save_payment(
                $transaction->paymentarea,
                $transaction->component,
                $transaction->paymentid,
                $USER->id,
                $transaction->amount,
                $transaction->currency,
                'mpesakenya'
            );
            
            // Mark the payment as completed.
            payment_helper::deliver_order($transaction->component, $transaction->paymentarea, $transaction->paymentid, $paymentid, $USER->id);
            
            return [
                'success' => true,
                'transactionid' => $transactionid,
                'receipt' => $transaction->mpesareceipt,
                'message' => get_string('payment_completed', 'paygw_mpesakenya')
            ];
        } else {
            // Transaction failed or is still pending.
            $errormessage = $response['ResultDesc'] ?? get_string('unknown_error', 'paygw_mpesakenya');
            
            // Update transaction status.
            $transaction->status = 'FAILED';
            $transaction->errormessage = $errormessage;
            $transaction->timemodified = time();
            $DB->update_record('paygw_mpesakenya_transactions', $transaction);
            
            return [
                'success' => false,
                'transactionid' => $transactionid,
                'message' => $errormessage
            ];
        }
    }

    /**
     * Returns description of method result value.
     *
     * @return external_function_parameters
     */
    public static function execute_returns() {
        return new external_function_parameters([
            'success' => new external_value(PARAM_BOOL, 'Whether the payment was completed successfully'),
            'transactionid' => new external_value(PARAM_TEXT, 'The transaction ID'),
            'receipt' => new external_value(PARAM_TEXT, 'The M-Pesa receipt number', VALUE_OPTIONAL),
            'message' => new external_value(PARAM_RAW, 'Response message')
        ]);
    }
}
