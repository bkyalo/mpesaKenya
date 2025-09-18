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
 * M-Pesa Kenya payment status page
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

// Get payment ID
$paymentid = required_param('id', PARAM_INT);

// Get payment record
$payment = $DB->get_record('payments', ['id' => $paymentid], '*', MUST_EXIST);

// Verify the payment belongs to the current user
if ($payment->userid != $USER->id) {
    throw new moodle_exception('invalidpayment', 'paygw_mpesakenya');
}

// Set up the page
$PAGE->set_url('/payment/gateway/mpesakenya/status.php', ['id' => $paymentid]);
$PAGE->set_title(get_string('paymentstatus', 'paygw_mpesakenya'));
$PAGE->set_heading(get_string('paymentstatus', 'paygw_mpesakenya'));

echo $OUTPUT->header();

try {
    // Check payment status
    $status = paygw_mpesakenya_check_payment_status($paymentid);
    
    // Display status to user
    if ($status['success']) {
        // Payment successful
        echo $OUTPUT->notification(get_string('paymentsuccessful', 'paygw_mpesakenya'), 'notifysuccess');
        
        // Show receipt
        echo $OUTPUT->box_start('generalbox', 'paymentreceipt');
        echo html_writer::tag('p', get_string('transactionid', 'paygw_mpesakenya') . ': ' . s($status['transactionid']));
        echo html_writer::tag('p', get_string('amount', 'paygw_mpesakenya') . ': ' . 
            format_float($payment->amount, 2) . ' ' . $payment->currency);
        echo html_writer::tag('p', get_string('date'), 'paygw_mpesakenya' . ': ' . userdate(time()));
        
        // Add continue button
        $returnurl = new moodle_url('/course/view.php', ['id' => $payment->itemid]);
        echo $OUTPUT->single_button($returnurl, get_string('continue'), 'get');
        
        echo $OUTPUT->box_end();
        
    } else {
        // Payment failed or pending
        echo $OUTPUT->notification($status['message'], $status['pending'] ? 'notifymessage' : 'notifyerror');
        
        // Add retry button
        $retryurl = new moodle_url('/payment/index.php', [
            'component' => $payment->component,
            'paymentarea' => $payment->paymentarea,
            'itemid' => $payment->itemid
        ]);
        echo $OUTPUT->single_button($retryurl, get_string('retrypayment', 'paygw_mpesakenya'), 'get');
    }
    
} catch (Exception $e) {
    // Handle errors
    echo $OUTPUT->notification($e->getMessage(), 'notifyerror');
    
    // Add return button
    $returnurl = new moodle_url('/');
    echo $OUTPUT->single_button($returnurl, get_string('returntositedashboard', 'core'), 'get');
}

echo $OUTPUT->footer();
