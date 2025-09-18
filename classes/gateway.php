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
 * Contains class for M-Pesa Kenya payment gateway.
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_mpesakenya;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/payment/classes/gateway.php');
require_once($CFG->dirroot . '/payment/gateway/mpesakenya/lib.php');

/**
 * The gateway class for M-Pesa Kenya payment gateway.
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends \core_payment\gateway {
    use \paygw_mpesakenya\traits\debug_trait;
    
    // Debug levels
    const LOG_LEVEL_DEBUG = 1;
    const LOG_LEVEL_INFO = 2;
    const LOG_LEVEL_WARNING = 3;
    const LOG_LEVEL_ERROR = 4;
    
    // Log file paths
    private static $logFiles = [
        'debug' => 'debug.log',
        'error' => 'error.log',
        'api' => 'api.log',
        'transaction' => 'transactions.log'
    ];
    
    // Cache for config
    private static $configCache = null;
    /**
     * Configuration form for the gateway instance.
     *
     * @param \core_payment\form\account_gateway $form The form to add elements to
     */
    /**
     * Get the plugin configuration with caching.
     *
     * @param \core_payment\form\account_gateway|null $form Optional form instance
     * @return \stdClass Configuration object
     */
    private static function get_plugin_config(\core_payment\form\account_gateway $form = null) {
        if (self::$configCache === null) {
            if ($form) {
                self::$configCache = $form->get_config();
            } else {
                // Fallback to get_config if form is not provided
                self::$configCache = (object)get_config('paygw_mpesakenya');
            }
            self::debug('Loaded plugin configuration', self::$configCache, __METHOD__);
        }
        return self::$configCache;
    }
    
    /**
     * Log a transaction with all relevant details.
     *
     * @param string $type Transaction type (e.g., 'payment', 'refund')
     * @param array $data Transaction data
     * @param string $status Transaction status
     * @param string $message Additional message
     */
    protected static function log_transaction(string $type, array $data, string $status, string $message = '') {
        global $CFG, $USER;
        
        try {
            $logData = [
                'timestamp' => time(),
                'type' => $type,
                'status' => $status,
                'data' => $data,
                'message' => $message,
                'userid' => $USER->id ?? 0,
                'ip' => getremoteaddr(),
                'session' => session_id()
            ];
            
            $logdir = $CFG->dataroot . '/temp/paygw_mpesakenya';
            $logfile = $logdir . '/transactions.log';
            
            if (!file_exists($logdir)) {
                mkdir($logdir, 0777, true);
            }
            
            $logEntry = json_encode($logData, JSON_PRETTY_PRINT) . ",\n";
            @file_put_contents($logfile, $logEntry, FILE_APPEND);
            
            self::debug("Transaction logged: {$type} - {$status}", $logData, __METHOD__);
        } catch (\Exception $e) {
            self::error('Error logging transaction', [
                'type' => $type,
                'status' => $status,
                'message' => $message,
                'error' => $e->getMessage()
            ], __METHOD__, $e);
        }
    }
    
    public static function add_configuration_to_gateway(\core_payment\form\account_gateway $form): void {
        global $CFG;
        
        try {
            self::debug('Starting configuration form setup', null, __METHOD__);
            
            $mform = $form->get_mform();
            $config = self::get_plugin_config($form);
            
            self::debug('Current config', $config, __METHOD__);

        // Add consumer key field
        $mform->addElement('text', 'consumerkey', get_string('consumerkey', 'paygw_mpesakenya'));
        $mform->setType('consumerkey', PARAM_TEXT);
        $mform->addHelpButton('consumerkey', 'consumerkey', 'paygw_mpesakenya');
        $mform->addRule('consumerkey', get_string('required'), 'required', null, 'client');
        $mform->setDefault('consumerkey', $config->consumerkey ?? '');

        // Add consumer secret field
        $mform->addElement('passwordunmask', 'consumersecret', get_string('consumersecret', 'paygw_mpesakenya'));
        $mform->setType('consumersecret', PARAM_TEXT);
        $mform->addHelpButton('consumersecret', 'consumersecret', 'paygw_mpesakenya');
        $mform->addRule('consumersecret', get_string('required'), 'required', null, 'client');
        $mform->setDefault('consumersecret', $config->consumersecret ?? '');

        // Add environment selector
        $mform->addElement('select', 'environment', get_string('environment', 'paygw_mpesakenya'), [
            'sandbox' => get_string('sandbox', 'paygw_mpesakenya'),
            'production' => get_string('production', 'paygw_mpesakenya'),
        ]);
        $mform->addHelpButton('environment', 'environment', 'paygw_mpesakenya');
        $mform->setType('environment', PARAM_ALPHA);
        $mform->setDefault('environment', $config->environment ?? 'sandbox');
        $mform->addRule('environment', get_string('required'), 'required', null, 'client');

        // Add shortcode field
        $mform->addElement('text', 'shortcode', get_string('shortcode', 'paygw_mpesakenya'));
        $mform->setType('shortcode', PARAM_TEXT);
        $mform->addHelpButton('shortcode', 'shortcode', 'paygw_mpesakenya');
        $mform->addRule('shortcode', get_string('required'), 'required', null, 'client');
        $mform->setDefault('shortcode', $config->shortcode ?? '');

        // Add initiator name field
        $mform->addElement('text', 'initiator_name', get_string('initiator_name', 'paygw_mpesakenya'));
        $mform->setType('initiator_name', PARAM_TEXT);
        $mform->addHelpButton('initiator_name', 'initiator_name', 'paygw_mpesakenya');
        $mform->addRule('initiator_name', get_string('required'), 'required', null, 'client');
        $mform->setDefault('initiator_name', $config->initiator_name ?? '');

        // Add security credential field
        $mform->addElement('passwordunmask', 'security_credential', get_string('security_credential', 'paygw_mpesakenya'));
        $mform->setType('security_credential', PARAM_TEXT);
        $mform->addHelpButton('security_credential', 'security_credential', 'paygw_mpesakenya');
        $mform->addRule('security_credential', get_string('required'), 'required', null, 'client');
        $mform->setDefault('security_credential', $config->security_credential ?? '');

        // Add passkey field
        $mform->addElement('text', 'passkey', get_string('passkey', 'paygw_mpesakenya'));
        $mform->setType('passkey', PARAM_TEXT);
        $mform->addHelpButton('passkey', 'passkey', 'paygw_mpesakenya');
        $mform->addRule('passkey', get_string('required'), 'required', null, 'client');
        $mform->setDefault('passkey', $config->passkey ?? '');
    }

    /**
     * Validates the gateway configuration form when it is being saved.
     *
     * @param \core_payment\form\account_gateway $form
     * @param \stdClass $data
     * @param array $files
     * @param array $errors
     */
    public static function validate_gateway_form(\core_payment\form\account_gateway $form, \stdClass $data, array $files, array &$errors): void {
        try {
            self::debug('Starting gateway form validation', $data, __METHOD__);
            
            // Validate required fields
            $requiredfields = [
                'consumerkey' => get_string('consumerkey', 'paygw_mpesakenya'),
                'consumersecret' => get_string('consumersecret', 'paygw_mpesakenya'),
                'environment' => get_string('environment', 'paygw_mpesakenya'),
                'shortcode' => get_string('shortcode', 'paygw_mpesakenya'),
                'initiator_name' => get_string('initiator_name', 'paygw_mpesakenya'),
                'security_credential' => get_string('security_credential', 'paygw_mpesakenya'),
                'passkey' => get_string('passkey', 'paygw_mpesakenya')
            ];
            
            $validationErrors = [];
            foreach ($requiredfields as $field => $fieldname) {
                if (empty($data->$field)) {
                    $errors[$field] = get_string('required');
                    $validationErrors[] = "Missing required field: {$field} ($fieldname)";
                }
            }
            
            // Validate environment value
            if (!empty($data->environment) && !in_array($data->environment, ['sandbox', 'production'])) {
                $errors['environment'] = get_string('invalidenvironment', 'paygw_mpesakenya');
                $validationErrors[] = "Invalid environment value: {$data->environment}";
            }
            
            // Log validation results
            if (!empty($validationErrors)) {
                self::debug('Form validation failed', [
                    'errors' => $validationErrors,
                    'data' => $data,
                    'files' => array_keys($files)
                ], __METHOD__);
                
                // Log to error log as well
                self::error('Gateway configuration validation failed', [
                    'errors' => $validationErrors,
                    'data' => $data
                ], __METHOD__);
            } else {
                self::debug('Gateway configuration validation passed', null, __METHOD__);
            }
        } catch (\Exception $e) {
            self::error('Error validating gateway form', null, __METHOD__, $e);
            throw $e;
        }

    /**
     * Returns the list of currencies supported by this gateway.
     *
     * @return string[] Array of currency codes in ISO 4217 format
     */
    public static function get_supported_currencies(): array {
        try {
            $currencies = ['KES'];
            self::debug('Returning supported currencies', [
                'currencies' => $currencies,
                'caller' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? null
            ], __METHOD__);
            
            return $currencies;
        } catch (\Exception $e) {
            self::error('Error getting supported currencies', null, __METHOD__, $e);
            return ['KES']; // Default to KES even if there's an error
        }
    }
    
    /**
     * Process payment.
     *
     * @param \core_payment\payment\payment_interface $payment The payment
     * @return void
     */
    public static function process_payment(\core_payment\payment\payment_interface $payment): void {
        global $DB, $USER;
        
        try {
            $paymentid = $payment->get_paymentid();
            $amount = $payment->get_amount();
            $currency = $payment->get_currency();
            $component = $payment->get_component();
            $paymentarea = $payment->get_paymentarea();
            $itemid = $payment->get_itemid();
            
            self::debug('Processing payment', [
                'paymentid' => $paymentid,
                'amount' => $amount,
                'currency' => $currency,
                'component' => $component,
                'paymentarea' => $paymentarea,
                'itemid' => $itemid,
                'userid' => $USER->id ?? 0
            ], __METHOD__);
            
            // Get the gateway configuration
            $config = self::get_plugin_config();
            
            // Log the transaction
            self::log_transaction('payment', [
                'paymentid' => $paymentid,
                'amount' => $amount,
                'currency' => $currency,
                'component' => $component,
                'paymentarea' => $paymentarea,
                'itemid' => $itemid,
                'userid' => $USER->id ?? 0,
                'config' => [
                    'environment' => $config->environment ?? 'sandbox',
                    'shortcode' => $config->shortcode ?? ''
                ]
            ], 'pending', 'Payment processing started');
            
            // TODO: Implement actual payment processing
            
            // If we get here, the payment was successful
            $payment->set_status(\core_payment\payment\payment_interface::STATUS_COMPLETED);
            
            self::log_transaction('payment', [
                'paymentid' => $paymentid,
                'status' => 'completed'
            ], 'completed', 'Payment processed successfully');
            
        } catch (\Exception $e) {
            self::error('Error processing payment', [
                'paymentid' => $paymentid ?? null,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], __METHOD__, $e);
            
            // Log the failed transaction
            if (isset($paymentid)) {
                self::log_transaction('payment', [
                    'paymentid' => $paymentid,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ], 'failed', 'Payment processing failed');
            }
            
            throw new \moodle_exception('paymenterror', 'paygw_mpesakenya', '', null, $e->getMessage());
        }
    }
}
