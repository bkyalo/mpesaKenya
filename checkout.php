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
 * M-Pesa Kenya checkout page
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/payment/gateway/mpesakenya/lib.php');

// Require login and session
require_login(null, false);
require_sesskey();

// Get parameters
$component = required_param('component', PARAM_COMPONENT);
$paymentarea = required_param('paymentarea', PARAM_AREA);
$itemid = required_param('itemid', PARAM_INT);
$phonenumber = required_param('phonenumber', PARAM_TEXT);

try {
    // Validate phone number
    $phonenumber = clean_param($phonenumber, PARAM_TEXT);
    if (!preg_match('/^[0-9]{9,12}$/', $phonenumber)) {
        throw new moodle_exception('invalidphonenumber', 'paygw_mpesakenya');
    }

    // Format phone number (add country code if missing)
    if (strpos($phonenumber, '254') !== 0) {
        if (strpos($phonenumber, '0') === 0) {
            $phonenumber = '254' . substr($phonenumber, 1);
        } else {
            $phonenumber = '254' . $phonenumber;
        }
    }

    // Initialize payment
    $paymentid = paygw_mpesakenya_initiate_payment($component, $paymentarea, $itemid, $phonenumber);
    
    // Redirect to payment status page
    $redirecturl = new moodle_url('/payment/gateway/mpesakenya/status.php', [
        'id' => $paymentid,
        'sesskey' => sesskey()
    ]);
    redirect($redirecturl);
    
} catch (Exception $e) {
    // Handle errors
    $returnurl = new moodle_url('/payment/index.php', [
        'component' => $component,
        'paymentarea' => $paymentarea,
        'itemid' => $itemid,
        'error' => $e->getMessage()
    ]);
    redirect($returnurl);
}
