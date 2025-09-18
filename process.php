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
 * M-Pesa Kenya payment processing page
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
$transactionid = required_param('id', PARAM_INT);
$checkoutrequestid = required_param('checkout_request_id', PARAM_ALPHANUM);

// Get the transaction record
$transaction = $DB->get_record('payments', ['id' => $transactionid], '*', MUST_EXIST);

// Verify the transaction belongs to the current user
if ($transaction->userid != $USER->id) {
    throw new moodle_exception('invalidtransaction', 'paygw_mpesakenya');
}

// Set up the page
$PAGE->set_url('/payment/gateway/mpesakenya/process.php', [
    'id' => $transactionid,
    'checkout_request_id' => $checkoutrequestid
]);

$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('complete_payment', 'paygw_mpesakenya'));
$PAGE->set_heading(get_string('complete_payment', 'paygw_mpesakenya'));

// Add CSS
$PAGE->requires->css(new moodle_url('/payment/gateway/mpesakenya/styles.css'));

// Prepare the page output
echo $OUTPUT->header();

echo html_writer::start_div('paygw_mpesakenya_container');
echo html_writer::div(html_writer::tag('h2', get_string('complete_payment', 'paygw_mpesakenya')), 'page-header');

echo html_writer::start_div('alert alert-info', ['id' => 'payment-status']);
echo html_writer::tag('p', get_string('payment_pending', 'paygw_mpesakenya'));
echo html_writer::tag('div', '', ['class' => 'loading-spinner']);
echo html_writer::end_div();

// Add the check status button
echo html_writer::start_div('text-center mt-4');
$checkurl = new moodle_url('/payment/gateway/mpesakenya/check_status.php', [
    'id' => $transactionid,
    'sesskey' => sesskey()
]);

echo html_writer::tag('button', get_string('check_status', 'paygw_mpesakenya'), [
    'id' => 'check-status',
    'class' => 'btn btn-primary',
    'data-url' => $checkurl->out(false)
]);

echo html_writer::end_div();

// Add JavaScript to check payment status
$PAGE->requires->js_call_amd('paygw_mpesakenya/status_checker', 'init', [
    'transactionid' => $transactionid,
    'checkurl' => $checkurl->out(false),
    'successurl' => new moodle_url('/payment/return.php', [
        'contextid' => $transaction->contextid,
        'component' => $transaction->component,
        'paymentarea' => $transaction->paymentarea,
        'itemid' => $transaction->itemid
    ])
]);

echo html_writer::end_div();
echo $OUTPUT->footer();
