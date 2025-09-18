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
 * Test helper for M-Pesa Kenya payment gateway.
 *
 * @package    paygw_mpesakenya
 * @category   test
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test helper class for M-Pesa Kenya payment gateway.
 */
class paygw_mpesakenya_test_helper {
    
    /**
     * Create a test payment account.
     *
     * @return stdClass The payment account
     */
    public static function create_payment_account() {
        global $DB;
        
        $account = new stdClass();
        $account->name = 'Test M-Pesa Account';
        $account->id = $DB->insert_record('payment_accounts', $account);
        
        // Enable M-Pesa Kenya gateway.
        $gateway = new stdClass();
        $gateway->accountid = $account->id;
        $gateway->gateway = 'mpesakenya';
        $gateway->enabled = 1;
        $gateway->config = json_encode([
            'environment' => 'sandbox',
            'consumerkey' => 'test_key',
            'consumersecret' => 'test_secret',
            'shortcode' => '174379',
            'initiator' => 'test',
            'securitycredential' => 'test',
            'passkey' => 'test',
        ]);
        $gateway->id = $DB->insert_record('payment_gateways', $gateway);
        
        return $account;
    }
    
    /**
     * Create a test payment.
     *
     * @param int $userid The user ID
     * @param int $amount The amount in cents
     * @param string $currency The currency code
     * @return stdClass The payment record
     */
    public static function create_payment($userid, $amount, $currency = 'KES') {
        global $DB;
        
        $payment = new stdClass();
        $payment->component = 'enrol_fee';
        $payment->paymentarea = 'fee';
        $payment->itemid = 1;
        $payment->userid = $userid;
        $payment->amount = $amount;
        $payment->currency = $currency;
        $payment->accountid = 1;
        $payment->gateway = 'mpesakenya';
        $payment->timecreated = time();
        $payment->timemodified = time();
        $payment->id = $DB->insert_record('payments', $payment);
        
        return $payment;
    }
    
    /**
     * Create a test M-Pesa transaction.
     *
     * @param int $paymentid The payment ID
     * @param string $status The transaction status
     * @return stdClass The transaction record
     */
    public static function create_transaction($paymentid, $status = 'PENDING') {
        global $DB;
        
        $transaction = new stdClass();
        $transaction->paymentid = $paymentid;
        $transaction->transactionid = 'TEST' . time();
        $transaction->merchantrequestid = 'MERCHANT' . time();
        $transaction->checkoutrequestid = 'CHECKOUT' . time();
        $transaction->status = $status;
        $transaction->timecreated = time();
        $transaction->timemodified = time();
        $transaction->id = $DB->insert_record('paygw_mpesakenya_transactions', $transaction);
        
        return $transaction;
    }
}
