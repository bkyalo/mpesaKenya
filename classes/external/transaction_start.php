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
 * This class starts a payment with the M-Pesa Kenya payment gateway.
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
 * This class starts a payment with the M-Pesa Kenya payment gateway.
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class transaction_start extends external_api {
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
            'phonenumber' => new external_value(PARAM_TEXT, 'The user\'s phone number for M-Pesa payment')
        ]);
    }

    /**
     * Initiate an M-Pesa STK push payment request.
     *
     * @param string $component Name of the component that the itemid belongs to
     * @param string $paymentarea The payment area
     * @param int $itemid An internal identifier that is used by the component
     * @param string $phonenumber User's phone number for M-Pesa payment
     * @return array
     */
    public static function execute(
        string $component,
        string $paymentarea,
        int $itemid,
        string $phonenumber
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
            'phonenumber' => $phonenumber
        ]);
        
        // Get payment configuration and helper.
        $config = payment_helper::get_gateway_configuration($component, $paymentarea, $itemid, 'mpesakenya');
        $helper = new mpesa_helper($config);
        
        // Get payment details.
        $payable = payment_helper::get_payable($component, $paymentarea, $itemid);
        $amount = $payable->get_amount();
        $currency = $payable->get_currency();
        
        // Apply surcharge if any.
        $surcharge = payment_helper::get_gateway_surcharge('mpesakenya');
        $cost = payment_helper::get_rounded_cost($amount, $currency, $surcharge);
        
        // Generate a unique reference.
        $reference = $component . '_' . $paymentarea . '_' . $itemid . '_' . $USER->id;
        $reference = substr($reference, 0, 12); // Ensure it fits M-Pesa's reference limit
        
        // Generate a unique transaction ID.
        $transactionid = time() . mt_rand(1000, 9999);
        
        // Format phone number for M-Pesa.
        $phonenumber = $helper->format_phone_number($phonenumber);
        
        // Initiate STK push.
        $response = $helper->stk_push($transactionid, $reference, $cost, $currency, $phonenumber);
        
        // Process the response.
        if (isset($response['ResponseCode']) && $response['ResponseCode'] === '0') {
            // STK push initiated successfully.
            $checkoutrequestid = $response['CheckoutRequestID'] ?? '';
            $merchantrequestid = $response['MerchantRequestID'] ?? '';
            
            // Log the transaction in our database.
            $transaction = (object)[
                'paymentid' => $itemid,
                'userid' => $USER->id,
                'transactionid' => $transactionid,
                'checkoutrequestid' => $checkoutrequestid,
                'merchantrequestid' => $merchantrequestid,
                'reference' => $reference,
                'amount' => $cost,
                'currency' => $currency,
                'status' => 'PENDING',
                'timecreated' => time(),
                'timemodified' => time(),
                'component' => $component,
                'paymentarea' => $paymentarea,
                'phonenumber' => $phonenumber
            ];
            
            // Delete any existing pending transaction for this payment.
            $DB->delete_records('paygw_mpesakenya_transactions', [
                'paymentid' => $itemid,
                'userid' => $USER->id,
                'status' => 'PENDING'
            ]);
            
            // Insert the new transaction.
            $transaction->id = $DB->insert_record('paygw_mpesakenya_transactions', $transaction);
            
            return [
                'success' => true,
                'transactionid' => (string)$transactionid,
                'checkoutrequestid' => $checkoutrequestid,
                'merchantrequestid' => $merchantrequestid,
                'message' => get_string('stk_push_sent', 'paygw_mpesakenya')
            ];
        } else {
            // Failed to initiate STK push.
            $errormessage = $response['errorMessage'] ?? get_string('unknown_error', 'paygw_mpesakenya');
            return [
                'success' => false,
                'transactionid' => '0',
                'checkoutrequestid' => '',
                'merchantrequestid' => '',
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
            'success' => new external_value(PARAM_BOOL, 'Whether the STK push was initiated successfully'),
            'transactionid' => new external_value(PARAM_TEXT, 'The transaction ID'),
            'checkoutrequestid' => new external_value(PARAM_TEXT, 'The checkout request ID from M-Pesa'),
            'merchantrequestid' => new external_value(PARAM_TEXT, 'The merchant request ID from M-Pesa'),
            'message' => new external_value(PARAM_RAW, 'Response message')
        ]);
    }
}
