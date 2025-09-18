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

namespace paygw_mpesakenya;

defined('MOODLE_INTERNAL') || die();

use core_payment\helper as payment_helper;
use paygw_mpesakenya\mpesa_helper;
use stdClass;

/**
 * Service class for M-Pesa Kenya payment gateway.
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service implements \core_payment\service_provider {
    /**
     * @var string The component name
     */
    const COMPONENT = 'paygw_mpesakenya';

    /**
     * @var string The database table name for transactions
     */
    const TRANSACTION_TABLE = 'paygw_mpesakenya_transactions';

    /**
     * @var string The database table name for logs
     */
    const LOG_TABLE = 'paygw_mpesakenya_logs';

    /**
     * @var mpesa_helper The M-Pesa helper instance
     */
    private $mpesaservice;

    /**
     * @var stdClass The gateway configuration
     */
    private $config;

    /**
     * Constructor.
     *
     * @param stdClass $config The gateway configuration
     */
    public function __construct(stdClass $config) {
        $this->config = $config;
        $this->mpesaservice = new mpesa_helper(
            $config->clientid,
            $config->clientsecret,
            $config->environment,
            $config->shortcode,
            $config->initiator_name,
            $config->security_credential,
            $config->passkey
        );
    }

    /**
     * Process a payment.
     *
     * @param stdClass $payment The payment data
     * @return array The result of the payment processing
     */
    public function process_payment(stdClass $payment): array {
        global $DB, $USER, $CFG;

        // Validate the payment data.
        if (empty($payment->component) || empty($payment->paymentarea) || empty($payment->itemid)) {
            throw new \moodle_exception('error:invalid_payment_data', 'paygw_mpesakenya');
        }

        // Get the payment amount and currency.
        $amount = payment_helper::get_cost_as_float($payment->amount, $payment->currency);
        if ($amount <= 0) {
            throw new \moodle_exception('error:invalid_amount', 'paygw_mpesakenya');
        }

        // Get the user's phone number.
        $phonenumber = $this->get_user_phone_number($USER->id);
        if (empty($phonenumber)) {
            throw new \moodle_exception('error:missing_phone_number', 'paygw_mpesakenya');
        }

        // Create a transaction record.
        $transaction = $this->create_transaction($payment, $phonenumber);

        // Prepare the callback URL.
        $callbackurl = new \moodle_url('/payment/gateway/mpesakenya/callback.php', [
            'paymentid' => $payment->paymentid,
            'component' => $payment->component,
            'paymentarea' => $payment->paymentarea,
            'itemid' => $payment->itemid,
        ]);

        try {
            // Initiate the STK push.
            $response = $this->mpesaservice->stk_push(
                $phonenumber,
                $amount,
                'Moodle Payment',
                'Payment for ' . $payment->description,
                $callbackurl->out(false)
            );

            // Update the transaction with the checkout request ID.
            $transaction->checkout_request_id = $response->CheckoutRequestID;
            $transaction->status = 'pending';
            $transaction->timemodified = time();
            $DB->update_record(self::TRANSACTION_TABLE, $transaction);

            // Log the successful STK push.
            $this->log_transaction($transaction->id, $response, 'stk_push');

            return [
                'success' => true,
                'message' => get_string('payment_pending', 'paygw_mpesakenya'),
                'transactionid' => $transaction->id,
                'checkout_request_id' => $response->CheckoutRequestID,
            ];
        } catch (\Exception $e) {
            // Update the transaction status to failed.
            $transaction->status = 'failed';
            $transaction->timemodified = time();
            $DB->update_record(self::TRANSACTION_TABLE, $transaction);

            // Log the error.
            $this->log_transaction($transaction->id, ['error' => $e->getMessage()], 'stk_push_error');

            throw new \moodle_exception('error:payment_failed', 'paygw_mpesakenya', '', $e->getMessage());
        }
    }

    /**
     * Check the status of a payment.
     *
     * @param string $transactionid The transaction ID
     * @return array The payment status
     */
    public function check_payment_status(string $transactionid): array {
        global $DB;

        // Get the transaction record.
        $transaction = $DB->get_record(self::TRANSACTION_TABLE, ['id' => $transactionid], '*', MUST_EXIST);

        if (empty($transaction->checkout_request_id)) {
            throw new \moodle_exception('error:invalid_transaction', 'paygw_mpesakenya');
        }

        try {
            // Query the transaction status from M-Pesa.
            $response = $this->mpesaservice->query_transaction_status($transaction->checkout_request_id);

            // Log the query response.
            $this->log_transaction($transaction->id, $response, 'query_status');

            // Check the response code.
            if (isset($response->ResultCode) && $response->ResultCode == '0') {
                // Payment was successful.
                $transaction->status = 'completed';
                $transaction->mpesa_receipt = $response->MpesaReceiptNumber ?? '';
                $transaction->transaction_id = $response->TransactionID ?? '';
                $transaction->timemodified = time();
                $DB->update_record(self::TRANSACTION_TABLE, $transaction);

                // Trigger payment success event.
                $this->trigger_payment_success_event($transaction);

                return [
                    'success' => true,
                    'status' => 'completed',
                    'message' => get_string('payment_successful', 'paygw_mpesakenya'),
                    'transactionid' => $transaction->id,
                    'receipt_number' => $transaction->mpesa_receipt,
                ];
            } else {
                // Payment failed or is still pending.
                $status = $response->ResultCode == '1032' ? 'cancelled' : 'failed';
                $transaction->status = $status;
                $transaction->timemodified = time();
                $DB->update_record(self::TRANSACTION_TABLE, $transaction);

                $message = $status == 'cancelled' 
                    ? get_string('payment_cancelled', 'paygw_mpesakenya')
                    : get_string('payment_failed', 'paygw_mpesakenya');

                return [
                    'success' => false,
                    'status' => $status,
                    'message' => $message,
                    'transactionid' => $transaction->id,
                ];
            }
        } catch (\Exception $e) {
            // Log the error.
            $this->log_transaction($transaction->id, ['error' => $e->getMessage()], 'query_status_error');

            throw new \moodle_exception('error:status_check_failed', 'paygw_mpesakenya', '', $e->getMessage());
        }
    }

    /**
     * Get the user's phone number.
     *
     * @param int $userid The user ID
     * @return string The user's phone number or empty string if not found
     */
    private function get_user_phone_number(int $userid): string {
        global $DB;

        // Try to get the phone number from the user profile.
        $user = $DB->get_record('user', ['id' => $userid], 'phone1, phone2');
        if (!empty($user->phone1)) {
            return $this->format_phone_number($user->phone1);
        }
        if (!empty($user->phone2)) {
            return $this->format_phone_number($user->phone2);
        }

        // Try to get the phone number from the user profile fields.
        $profilefields = $DB->get_records_sql(
            "SELECT f.shortname, d.data 
               FROM {user_info_field} f 
               JOIN {user_info_data} d ON d.fieldid = f.id 
              WHERE d.userid = ? AND f.shortname IN ('phone', 'mobile', 'phonenumber')", 
            [$userid]
        );

        foreach ($profilefields as $field) {
            if (!empty($field->data)) {
                return $this->format_phone_number($field->data);
            }
        }

        return '';
    }

    /**
     * Format a phone number to the M-Pesa format (2547XXXXXXXX).
     *
     * @param string $phonenumber The phone number to format
     * @return string The formatted phone number or empty string if invalid
     */
    private function format_phone_number(string $phonenumber): string {
        // Remove all non-numeric characters.
        $phonenumber = preg_replace('/[^0-9]/', '', $phonenumber);

        // Handle different phone number formats.
        if (strlen($phonenumber) == 9 && substr($phonenumber, 0, 1) == '7') {
            // 712345678 -> 254712345678
            return '254' . $phonenumber;
        } elseif (strlen($phonenumber) == 10 && substr($phonenumber, 0, 1) == '0') {
            // 0712345678 -> 254712345678
            return '254' . substr($phonenumber, 1);
        } elseif (strlen($phonenumber) == 12 && substr($phonenumber, 0, 3) == '254') {
            // 254712345678 -> 254712345678
            return $phonenumber;
        }

        return '';
    }

    /**
     * Create a new transaction record.
     *
     * @param stdClass $payment The payment data
     * @param string $phonenumber The user's phone number
     * @return stdClass The created transaction record
     */
    private function create_transaction(stdClass $payment, string $phonenumber): stdClass {
        global $DB, $USER;

        $transaction = new stdClass();
        $transaction->paymentid = $payment->paymentid;
        $transaction->userid = $USER->id;
        $transaction->component = $payment->component;
        $transaction->paymentarea = $payment->paymentarea;
        $transaction->itemid = $payment->itemid;
        $transaction->amount = $payment->amount;
        $transaction->currency = $payment->currency;
        $transaction->phone = $phonenumber;
        $transaction->status = 'pending';
        $transaction->timecreated = time();
        $transaction->timemodified = $transaction->timecreated;
        $transaction->environment = $this->config->environment;

        $transaction->id = $DB->insert_record(self::TRANSACTION_TABLE, $transaction);
        return $transaction;
    }

    /**
     * Log a transaction.
     *
     * @param int $transactionid The transaction ID
     * @param mixed $data The data to log
     * @param string $type The log type
     */
    private function log_transaction(int $transactionid, $data, string $type): void {
        global $DB;

        $log = new stdClass();
        $log->transactionid = $transactionid;
        $log->request = is_string($data) ? $data : json_encode($data);
        $log->status = $type;
        $log->timecreated = time();

        $DB->insert_record(self::LOG_TABLE, $log);
    }

    /**
     * Trigger the payment success event.
     *
     * @param stdClass $transaction The transaction record
     */
    private function trigger_payment_success_event(stdClass $transaction): void {
        $event = \paygw_mpesakenya\event\payment_success::create([
            'context' => \context_system::instance(),
            'objectid' => $transaction->id,
            'other' => [
                'paymentid' => $transaction->paymentid,
                'userid' => $transaction->userid,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'mpesa_receipt' => $transaction->mpesa_receipt,
            ]
        ]);
        $event->trigger();
    }
}
