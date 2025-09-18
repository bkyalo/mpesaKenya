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

namespace paygw_mpesakenya\payment;

defined('MOODLE_INTERNAL') || die();

use core_payment\form\account_gateway;
use core_payment\helper as payment_helper;
use paygw_mpesakenya\mpesa_helper;
use stdClass;

/**
 * The gateway class for M-Pesa Kenya payment gateway.
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends \core_payment\gateway {
    /**
     * @var string The component name
     */
    public static function get_system_icon(): string {
        global $OUTPUT;
        return $OUTPUT->image_url('icon', 'paygw_mpesakenya');
    }

    /**
     * Configuration form for the gateway instance.
     *
     * @param account_gateway $form The form to add elements to
     * @param stdClass $config The gateway configuration
     * @param string $supports The features supported by this gateway
     */
    public static function add_configuration_to_gateway_form(account_gateway $form, stdClass $config, string $supports): void {
        $mform = $form->get_mform();

        $mform->addElement('text', 'clientid', get_string('clientid', 'paygw_mpesakenya'));
        $mform->setType('clientid', PARAM_TEXT);
        $mform->addHelpButton('clientid', 'clientid', 'paygw_mpesakenya');
        $mform->addRule('clientid', get_string('required'), 'required', null, 'client');
        $mform->setDefault('clientid', $config->clientid ?? '');

        $mform->addElement('passwordunmask', 'clientsecret', get_string('clientsecret', 'paygw_mpesakenya'));
        $mform->setType('clientsecret', PARAM_TEXT);
        $mform->addHelpButton('clientsecret', 'clientsecret', 'paygw_mpesakenya');
        $mform->addRule('clientsecret', get_string('required'), 'required', null, 'client');
        $mform->setDefault('clientsecret', $config->clientsecret ?? '');

        $environments = [
            'sandbox' => get_string('sandbox', 'paygw_mpesakenya'),
            'production' => get_string('production', 'paygw_mpesakenya'),
        ];
        $mform->addElement('select', 'environment', get_string('environment', 'paygw_mpesakenya'), $environments);
        $mform->setType('environment', PARAM_ALPHA);
        $mform->setDefault('environment', $config->environment ?? 'sandbox');
        $mform->addHelpButton('environment', 'environment', 'paygw_mpesakenya');
        $mform->addRule('environment', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'shortcode', get_string('shortcode', 'paygw_mpesakenya'));
        $mform->setType('shortcode', PARAM_TEXT);
        $mform->addHelpButton('shortcode', 'shortcode', 'paygw_mpesakenya');
        $mform->addRule('shortcode', get_string('required'), 'required', null, 'client');
        $mform->setDefault('shortcode', $config->shortcode ?? '');

        $mform->addElement('text', 'initiator_name', get_string('initiator_name', 'paygw_mpesakenya'));
        $mform->setType('initiator_name', PARAM_TEXT);
        $mform->addHelpButton('initiator_name', 'initiator_name', 'paygw_mpesakenya');
        $mform->addRule('initiator_name', get_string('required'), 'required', null, 'client');
        $mform->setDefault('initiator_name', $config->initiator_name ?? '');

        $mform->addElement('passwordunmask', 'security_credential', get_string('security_credential', 'paygw_mpesakenya'));
        $mform->setType('security_credential', PARAM_TEXT);
        $mform->addHelpButton('security_credential', 'security_credential', 'paygw_mpesakenya');
        $mform->addRule('security_credential', get_string('required'), 'required', null, 'client');
        $mform->setDefault('security_credential', $config->security_credential ?? '');

        $mform->addElement('text', 'passkey', get_string('passkey', 'paygw_mpesakenya'));
        $mform->setType('passkey', PARAM_TEXT);
        $mform->addHelpButton('passkey', 'passkey', 'paygw_mpesakenya');
        $mform->addRule('passkey', get_string('required'), 'required', null, 'client');
        $mform->setDefault('passkey', $config->passkey ?? '');
    }

    /**
     * Validates the gateway configuration form.
     *
     * @param account_gateway $form The form to validate
     * @param stdClass $data The form data
     * @param array $files The uploaded files
     * @param array $errors The form errors
     */
    public static function validate_gateway_form(account_gateway $form, stdClass $data, array &$errors): void {
        // Add any custom validation here if needed.
    }

    /**
     * Returns the list of currencies supported by this plugin.
     *
     * @return string[] An array of currency codes in uppercase
     */
    public static function get_supported_currencies(): array {
        return ['KES'];
    }

    /**
     * Returns the list of countries supported by this plugin.
     *
     * @return string[] An array of country codes in uppercase
     */
    public static function get_supported_countries(): array {
        return ['KE'];
    }
}
