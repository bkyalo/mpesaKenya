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
 * This class provides configuration data for the M-Pesa Kenya payment gateway JS.
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace paygw_mpesakenya\external;

use context_system;
use core_payment\helper as payment_helper;
use core_external\{external_api, external_function_parameters, external_value, external_single_structure};
use moodle_exception;

/**
 * This class provides configuration data for the M-Pesa Kenya payment gateway JS.
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_config_for_js extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'component' => new external_value(PARAM_COMPONENT, 'The component name'),
            'paymentarea' => new external_value(PARAM_AREA, 'Payment area in the component'),
            'itemid' => new external_value(PARAM_INT, 'The item id in the context of the component area')
        ]);
    }

    /**
     * Get the configuration required to initialize the payment JS.
     *
     * @param string $component Name of the component that the itemid belongs to
     * @param string $paymentarea The payment area
     * @param int $itemid An internal identifier that is used by the component
     * @return array
     */
    public static function execute(
        string $component,
        string $paymentarea,
        int $itemid
    ): array {
        global $USER, $CFG;
        
        // Validate context and parameters.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/payment:view', $context);
        
        self::validate_parameters(self::execute_parameters(), [
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid
        ]);
        
        // Get payment configuration.
        $config = payment_helper::get_gateway_configuration($component, $paymentarea, $itemid, 'mpesakenya');
        
        // Get the payable amount and currency.
        $payable = payment_helper::get_payable($component, $paymentarea, $itemid);
        $amount = $payable->get_amount();
        $currency = $payable->get_currency();
        
        // Apply surcharge if any.
        $surcharge = payment_helper::get_gateway_surcharge('mpesakenya');
        $cost = payment_helper::get_rounded_cost($amount, $currency, $surcharge);
        
        // Get the user's phone number if available.
        $phonenumber = '';
        $userfields = get_extra_user_fields($context);
        foreach ($userfields as $field) {
            if ($field === 'phone1' || $field === 'phone2') {
                $phonenumber = $USER->$field ?? '';
                if (!empty($phonenumber)) {
                    break;
                }
            }
        }
        
        // Format the amount for display.
        $displaycost = payment_helper::get_cost_as_string($cost, $currency);
        
        // Check if test mode is enabled.
        $testmode = !empty($config->environment) && $config->environment === 'sandbox';
        
        // Prepare the configuration data.
        $configdata = [
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
            'amount' => $cost,
            'currency' => $currency,
            'displaycost' => $displaycost,
            'phonenumber' => $phonenumber,
            'testmode' => $testmode,
            'sitename' => format_string($CFG->sitename, true, ['context' => $context]),
            'country' => $config->country ?? 'KE',
            'currency' => $config->currency ?? 'KES',
            'shortcode' => $config->shortcode ?? '',
            'initiator_name' => $config->initiator_name ?? '',
            'callback_url' => $CFG->wwwroot . '/payment/gateway/mpesakenya/callback.php',
            'process_url' => $CFG->wwwroot . '/payment/gateway/mpesakenya/process.php',
            'check_status_url' => $CFG->wwwroot . '/payment/gateway/mpesakenya/check_status.php',
            'sesskey' => sesskey()
        ];
        
        // Add language strings.
        $configdata['strings'] = [
            'enter_phone' => get_string('enter_phone', 'paygw_mpesakenya'),
            'invalid_phone' => get_string('invalid_phone', 'paygw_mpesakenya'),
            'processing' => get_string('processing', 'paygw_mpesakenya'),
            'confirm_payment' => get_string('confirm_payment', 'paygw_mpesakenya'),
            'pay_with_mpesa' => get_string('pay_with_mpesa', 'paygw_mpesakenya'),
            'checking_status' => get_string('checking_status', 'paygw_mpesakenya'),
            'payment_success' => get_string('payment_success', 'paygw_mpesakenya'),
            'payment_failed' => get_string('payment_failed', 'paygw_mpesakenya'),
            'payment_pending' => get_string('payment_pending', 'paygw_mpesakenya'),
            'try_again' => get_string('try_again', 'paygw_mpesakenya'),
            'close' => get_string('close', 'paygw_mpesakenya'),
            'error' => get_string('error', 'paygw_mpesakenya'),
            'success' => get_string('success', 'paygw_mpesakenya'),
            'warning' => get_string('warning', 'paygw_mpesakenya'),
            'info' => get_string('info', 'paygw_mpesakenya')
        ];
        
        return $configdata;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_function_parameters
     */
    public static function execute_returns() {
        return new external_function_parameters([
            'component' => new external_value(PARAM_COMPONENT, 'The component name'),
            'paymentarea' => new external_value(PARAM_AREA, 'Payment area in the component'),
            'itemid' => new external_value(PARAM_INT, 'The item id in the context of the component area'),
            'amount' => new external_value(PARAM_FLOAT, 'The payment amount'),
            'currency' => new external_value(PARAM_TEXT, 'The payment currency'),
            'displaycost' => new external_value(PARAM_TEXT, 'The formatted cost with currency symbol'),
            'phonenumber' => new external_value(PARAM_TEXT, 'The user\'s phone number if available', VALUE_OPTIONAL, ''),
            'testmode' => new external_value(PARAM_BOOL, 'Whether test mode is enabled'),
            'sitename' => new external_value(PARAM_TEXT, 'The site name'),
            'country' => new external_value(PARAM_TEXT, 'The country code'),
            'shortcode' => new external_value(PARAM_TEXT, 'The M-Pesa shortcode'),
            'initiator_name' => new external_value(PARAM_TEXT, 'The initiator name'),
            'callback_url' => new external_value(PARAM_URL, 'The callback URL'),
            'process_url' => new external_value(PARAM_URL, 'The process URL'),
            'check_status_url' => new external_value(PARAM_URL, 'The check status URL'),
            'sesskey' => new external_value(PARAM_RAW, 'The session key'),
            'strings' => new external_single_structure([
                'enter_phone' => new external_value(PARAM_TEXT, 'Enter phone string'),
                'invalid_phone' => new external_value(PARAM_TEXT, 'Invalid phone string'),
                'processing' => new external_value(PARAM_TEXT, 'Processing string'),
                'confirm_payment' => new external_value(PARAM_TEXT, 'Confirm payment string'),
                'pay_with_mpesa' => new external_value(PARAM_TEXT, 'Pay with M-Pesa string'),
                'checking_status' => new external_value(PARAM_TEXT, 'Checking status string'),
                'payment_success' => new external_value(PARAM_TEXT, 'Payment success string'),
                'payment_failed' => new external_value(PARAM_TEXT, 'Payment failed string'),
                'payment_pending' => new external_value(PARAM_TEXT, 'Payment pending string'),
                'try_again' => new external_value(PARAM_TEXT, 'Try again string'),
                'close' => new external_value(PARAM_TEXT, 'Close string'),
                'error' => new external_value(PARAM_TEXT, 'Error string'),
                'success' => new external_value(PARAM_TEXT, 'Success string'),
                'warning' => new external_value(PARAM_TEXT, 'Warning string'),
                'info' => new external_value(PARAM_TEXT, 'Info string')
            ], 'Language strings for the UI')
        ]);
    }
}
