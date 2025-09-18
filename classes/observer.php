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
 * Event observer for the M-Pesa Kenya payment gateway
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_mpesakenya;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for paygw_mpesakenya
 */
class observer {
    
    /**
     * Triggered when a payment is created
     *
     * @param \paygw_mpesakenya\\event\\payment_created $event The event
     * @return bool Success/Failure
     */
    public static function payment_created(\paygw_mpesakenya\event\payment_created $event) {
        global $DB, $CFG;
        
        $eventdata = $event->get_data();
        $transaction = $event->get_record_snapshot('paygw_mpesakenya_transactions', $event->objectid);
        
        // Log the event
        $log = new \stdClass();
        $log->transactionid = $transaction->id;
        $log->userid = $transaction->userid;
        $log->status = 'created';
        $log->amount = $transaction->amount;
        $log->currency = $transaction->currency;
        $log->timecreated = time();
        
        $DB->insert_record('paygw_mpesakenya_logs', $log);
        
        return true;
    }
    
    /**
     * Triggered when a payment is completed
     *
     * @param \paygw_mpesakenya\\event\\payment_completed $event The event
     * @return bool Success/Failure
     */
    public static function payment_completed(\paygw_mpesakenya\event\payment_completed $event) {
        global $DB, $CFG, $USER;
        
        $eventdata = $event->get_data();
        $transaction = $event->get_record_snapshot('paygw_mpesakenya_transactions', $event->objectid);
        
        // Update transaction status
        $transaction->status = 'completed';
        $transaction->timemodified = time();
        $DB->update_record('paygw_mpesakenya_transactions', $transaction);
        
        // Log the event
        $log = new \stdClass();
        $log->transactionid = $transaction->id;
        $log->userid = $transaction->userid;
        $log->status = 'completed';
        $log->amount = $transaction->amount;
        $log->currency = $transaction->currency;
        $log->timecreated = time();
        
        $DB->insert_record('paygw_mpesakenya_logs', $log);
        
        // Send payment confirmation to user
        $user = $DB->get_record('user', ['id' => $transaction->userid]);
        
        if ($user) {
            $message = new \core\message\message();
            $message->component = 'paygw_mpesakenya';
            $message->name = 'payment_received';
            $message->userfrom = \core_user::get_noreply_user();
            $message->userto = $user;
            $message->subject = get_string('payment_received_subject', 'paygw_mpesakenya');
            $message->fullmessage = get_string('payment_received_body', 'paygw_mpesakenya', [
                'fullname' => fullname($user),
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'transactionid' => $transaction->transactionid,
                'date' => userdate(time())
            ]);
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = get_string('payment_received_body_html', 'paygw_mpesakenya', [
                'fullname' => fullname($user),
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'transactionid' => $transaction->transactionid,
                'date' => userdate(time())
            ]);
            $message->smallmessage = get_string('payment_received_small', 'paygw_mpesakenya', [
                'amount' => $transaction->amount,
                'currency' => $transaction->currency
            ]);
            $message->notification = 1;
            
            message_send($message);
        }
        
        return true;
    }
    
    /**
     * Triggered when a payment fails
     *
     * @param \paygw_mpesakenya\\event\\payment_failed $event The event
     * @return bool Success/Failure
     */
    public static function payment_failed(\paygw_mpesakenya\event\payment_failed $event) {
        global $DB, $CFG;
        
        $eventdata = $event->get_data();
        $transaction = $event->get_record_snapshot('paygw_mpesakenya_transactions', $event->objectid);
        
        // Update transaction status
        $transaction->status = 'failed';
        $transaction->timemodified = time();
        $DB->update_record('paygw_mpesakenya_transactions', $transaction);
        
        // Log the event
        $log = new \stdClass();
        $log->transactionid = $transaction->id;
        $log->userid = $transaction->userid;
        $log->status = 'failed';
        $log->amount = $transaction->amount;
        $log->currency = $transaction->currency;
        $log->errormessage = $event->other['error_message'] ?? '';
        $log->timecreated = time();
        
        $DB->insert_record('paygw_mpesakenya_logs', $log);
        
        return true;
    }
    
    /**
     * Triggered when a payment is refunded
     *
     * @param \paygw_mpesakenya\\event\\payment_refunded $event The event
     * @return bool Success/Failure
     */
    public static function payment_refunded(\paygw_mpesakenya\event\payment_refunded $event) {
        global $DB, $CFG;
        
        $eventdata = $event->get_data();
        $transaction = $event->get_record_snapshot('paygw_mpesakenya_transactions', $event->objectid);
        
        // Update transaction status
        $transaction->status = 'refunded';
        $transaction->timemodified = time();
        $DB->update_record('paygw_mpesakenya_transactions', $transaction);
        
        // Log the event
        $log = new \stdClass();
        $log->transactionid = $transaction->id;
        $log->userid = $transaction->userid;
        $log->status = 'refunded';
        $log->amount = $transaction->amount;
        $log->currency = $transaction->currency;
        $log->refundid = $event->other['refund_id'] ?? '';
        $log->timecreated = time();
        
        $DB->insert_record('paygw_mpesakenya_logs', $log);
        
        return true;
    }
}
