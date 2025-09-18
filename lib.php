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
 * M-Pesa Kenya payment gateway
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core_payment\form\account_gateway;
use core_payment\local\entities\sale;
use core_payment\local\gateway\gateway_interface;
use core_payment\local\entities\transaction;

/**
 * M-Pesa Kenya payment gateway class
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class paygw_mpesakenya implements gateway_interface {

    /**
     * Configuration form for the gateway instance
     *
     * @param account_gateway $form The form to add elements to
     * @param \stdClass $gateway The gateway object
     * @return void
     */
    public static function add_configuration_to_gateway_form(account_gateway $form, \stdClass $gateway): void {
        $mform = $form->get_mform();
        
        $mform->addElement('text', 'consumerkey', get_string('consumerkey', 'paygw_mpesakenya'));
        $mform->setType('consumerkey', PARAM_TEXT);
        $mform->addHelpButton('consumerkey', 'consumerkey', 'paygw_mpesakenya');
        $mform->addRule('consumerkey', get_string('required'), 'required', null, 'client');
        
        $mform->addElement('passwordunmask', 'consumersecret', get_string('consumersecret', 'paygw_mpesakenya'));
        $mform->setType('consumersecret', PARAM_TEXT);
        $mform->addHelpButton('consumersecret', 'consumersecret', 'paygw_mpesakenya');
        $mform->addRule('consumersecret', get_string('required'), 'required', null, 'client');
        
        $mform->addElement('text', 'shortcode', get_string('shortcode', 'paygw_mpesakenya'));
        $mform->setType('shortcode', PARAM_TEXT);
        $mform->addHelpButton('shortcode', 'shortcode', 'paygw_mpesakenya');
        $mform->addRule('shortcode', get_string('required'), 'required', null, 'client');
        
        $mform->addElement('select', 'environment', get_string('environment', 'paygw_mpesakenya'), [
            'sandbox' => get_string('sandbox', 'paygw_mpesakenya'),
            'production' => get_string('production', 'paygw_mpesakenya')
        ]);
        $mform->setDefault('environment', 'sandbox');
        
        $mform->addElement('text', 'callback_url', get_string('callback_url', 'paygw_mpesakenya'), ['disabled' => 'disabled']);
        $mform->setType('callback_url', PARAM_URL);
        $mform->setDefault('callback_url', (new moodle_url('/payment/gateway/mpesakenya/callback.php'))->out(false));
    }
    
    /**
     * Validates the gateway configuration form
     *
     * @param account_gateway $form The form to validate
     * @param \stdClass $data The form data
     * @param array $files The uploaded files
     * @param array $errors The form errors
     * @return void
     */
    public static function validate_gateway_form(account_gateway $form, \stdClass $data, array &$files, array &$errors): void {
        // Add any custom validation here
    }
    
    /**
     * Processes the payment
     *
     * @param transaction $transaction The transaction to process
     * @param array $payload The payment data
     * @return void
     */
    public static function process_payment(transaction $transaction, array $payload): void {
        global $CFG, $DB, $USER;
        
        // Get the gateway configuration
        $gateway = $DB->get_record('payment_gateways', ['id' => $transaction->get_gateway_id()], '*', MUST_EXIST);
        $config = json_decode($gateway->config, true);
        
        // Generate a unique transaction reference
        $reference = 'MPESA-' . time() . '-' . $transaction->get_id();
        
        // Prepare the payment data
        $paymentdata = [
            'BusinessShortCode' => $config['shortcode'],
            'Password' => self::generate_password($config['shortcode'], $config['passkey'] ?? ''),
            'Timestamp' => date('YmdHis'),
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => number_format($transaction->get_amount(), 2, '.', ''),
            'PartyA' => $USER->phone1 ?: $USER->phone2,
            'PartyB' => $config['shortcode'],
            'PhoneNumber' => $USER->phone1 ?: $USER->phone2,
            'CallBackURL' => (string)new moodle_url('/payment/gateway/mpesakenya/callback.php'),
            'AccountReference' => $reference,
            'TransactionDesc' => 'Payment for order ' . $transaction->get_id(),
        ];
        
        // Initiate STK push
        $response = self::initiate_stk_push($config, $paymentdata);
        
        // Handle the response
        if (isset($response['ResponseCode']) && $response['ResponseCode'] === '0') {
            // STK push initiated successfully
            $transaction->set_gateway_transaction_id($response['CheckoutRequestID']);
            $transaction->set_status(transaction::STATUS_PENDING);
            $transaction->update();
            
            // Redirect to payment page
            redirect(new moodle_url('/payment/gateway/mpesakenya/process.php', [
                'id' => $transaction->get_id(),
                'checkout_request_id' => $response['CheckoutRequestID']
            ]));
        } else {
            // Handle error
            throw new moodle_exception('payment_error', 'paygw_mpesakenya', '', null, 
                'Failed to initiate M-Pesa payment: ' . ($response['errorMessage'] ?? 'Unknown error'));
        }
    }
    
    /**
     * Generates the password for M-Pesa API
     *
     * @param string $shortcode The business shortcode
     * @param string $passkey The passkey
     * @return string The generated password
     */
    private static function generate_password(string $shortcode, string $passkey): string {
        $timestamp = date('YmdHis');
        $password = base64_encode($shortcode . $passkey . $timestamp);
        return $password;
    }
    
    /**
     * Initiates an STK push to the customer's phone
     *
     * @param array $config The gateway configuration
     * @param array $data The payment data
     * @return array The API response
     */
    private static function initiate_stk_push(array $config, array $data): array {
        $endpoint = $config['environment'] === 'sandbox' 
            ? 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
            : 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
        
        // Get access token
        $token = self::get_access_token($config);
        
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
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new moodle_exception('api_error', 'paygw_mpesakenya', '', null, $error);
        }
        
        return json_decode($response, true) ?: [];
    }
    
    /**
     * Gets an access token from the M-Pesa API
     *
     * @param array $config The gateway configuration
     * @return string The access token
     */
    private static function get_access_token(array $config): string {
        $cache = cache::make('paygw_mpesakenya', 'tokens');
        $token = $cache->get('access_token');
        
        // Return cached token if it's still valid
        if ($token && $cache->get('token_expires') > time()) {
            return $token;
        }
        
        // Request new token
        $endpoint = $config['environment'] === 'sandbox'
            ? 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
            : 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        
        $credentials = base64_encode($config['consumerkey'] . ':' . $config['consumersecret']);
        
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . $credentials,
                'Content-Type: application/json'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new moodle_exception('api_error', 'paygw_mpesakenya', '', null, $error);
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['access_token'])) {
            throw new moodle_exception('auth_error', 'paygw_mpesakenya', '', null, $response);
        }
        
        // Cache the token (expires in 1 hour)
        $cache->set('access_token', $data['access_token']);
        $cache->set('token_expires', time() + 3500); // 58.33 minutes
        
        return $data['access_token'];
    }
}

/**
 * Serves the plugin files.
 *
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function paygw_mpesakenya_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    // No files to serve yet
    return false;
}
