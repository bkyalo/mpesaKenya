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

require_once($CFG->dirroot . '/payment/gateway/mpesakenya/lib.php');

/**
 * The gateway class for M-Pesa Kenya payment gateway.
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway implements \core_payment\local\gateway\gateway_interface {
    /**
     * Configuration form for the gateway instance.
     *
     * @param \core_payment\form\account_gateway $form The form to add elements to
     * @param \stdClass $config The gateway configuration
     * @param string $supports The features supported by this gateway
     */
    public static function add_configuration_to_gateway(\core_payment\form\account_gateway $form, \stdClass $config, string $supports): void {
        $mform = $form->get_mform();

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
     * @return void
     */
    public static function validate_gateway_form(\core_payment\form\account_gateway $form, \stdClass $data, array &$errors): void {
        // Add any validation logic here if needed
    }

    /**
     * Returns the list of payment areas that this gateway supports.
     *
     * @return array List of payment area names
     */
    public static function get_supported_currencies(): array {
        return ['KES'];
    }
}
