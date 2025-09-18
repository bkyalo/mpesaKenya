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
 * Event for when a payment is successfully processed.
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_mpesakenya\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event for when a payment is successfully processed.
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class payment_success extends \core\event\base {

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['objecttable'] = 'paygw_mpesakenya_transactions';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event:payment_success', 'paygw_mpesakenya');
    }

    /**
     * Returns non-localised event description.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' has successfully completed a payment with M-Pesa Kenya. " .
               "Payment ID: '{$this->other['paymentid']}', Amount: '{$this->other['amount']} {$this->other['currency']}', " .
               "M-Pesa Receipt: '{$this->other['mpesa_receipt']}'";
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['paymentid'])) {
            throw new \coding_exception('The \'paymentid\' value must be set in other.');
        }
        if (!isset($this->other['userid'])) {
            throw new \coding_exception('The \'userid\' value must be set in other.');
        }
        if (!isset($this->other['amount'])) {
            throw new \coding_exception('The \'amount\' value must be set in other.');
        }
        if (!isset($this->other['currency'])) {
            throw new \coding_exception('The \'currency\' value must be set in other.');
        }
        if (!isset($this->other['mpesa_receipt'])) {
            throw new \coding_exception('The \'mpesa_receipt\' value must be set in other.');
        }
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/payment/gateway/mpesakenya/transactions.php', ['id' => $this->objectid]);
    }

    /**
     * Return the legacy event log data.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        return [
            $this->courseid,
            'mpesakenya',
            'payment received',
            "transactions.php?id={$this->objectid}",
            "Payment ID: {$this->other['paymentid']}, Amount: {$this->other['amount']} {$this->other['currency']}",
            0,
            $this->userid
        ];
    }
}
