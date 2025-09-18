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
 * Privacy Subsystem implementation for paygw_mpesakenya.
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_mpesakenya\privacy;

defined('MOODLE_INTERNAL') || die();

use core_payment\privacy\paygw_provider;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;

/**
 * Privacy Subsystem implementation for paygw_mpesakenya.
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements 
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_payment\privacy\paygw_provider {
    
    use \core_payment\privacy\paygw_provider_trait;
    
    /**
     * Returns metadata about this plugin's privacy policy.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('paygw_mpesakenya_transactions', [
            'userid' => 'privacy:metadata:paygw_mpesakenya_transactions:userid',
            'transactionid' => 'privacy:metadata:paygw_mpesakenya_transactions:transactionid',
            'paymentid' => 'privacy:metadata:paygw_mpesakenya_transactions:paymentid',
            'checkoutrequestid' => 'privacy:metadata:paygw_mpesakenya_transactions:checkoutrequestid',
            'merchantrequestid' => 'privacy:metadata:paygw_mpesakenya_transactions:merchantrequestid',
            'reference' => 'privacy:metadata:paygw_mpesakenya_transactions:reference',
            'amount' => 'privacy:metadata:paygw_mpesakenya_transactions:amount',
            'currency' => 'privacy:metadata:paygw_mpesakenya_transactions:currency',
            'status' => 'privacy:metadata:paygw_mpesakenya_transactions:status',
            'phonenumber' => 'privacy:metadata:paygw_mpesakenya_transactions:phonenumber',
            'mpesareceipt' => 'privacy:metadata:paygw_mpesakenya_transactions:mpesareceipt',
            'timecreated' => 'privacy:metadata:paygw_mpesakenya_transactions:timecreated',
            'timemodified' => 'privacy:metadata:paygw_mpesakenya_transactions:timemodified',
        ], 'privacy:metadata:paygw_mpesakenya_transactions');
        
        $collection->add_database_table('paygw_mpesakenya_logs', [
            'transactionid' => 'privacy:metadata:paygw_mpesakenya_logs:transactionid',
            'logdata' => 'privacy:metadata:paygw_mpesakenya_logs:logdata',
            'type' => 'privacy:metadata:paygw_mpesakenya_logs:type',
            'timecreated' => 'privacy:metadata:paygw_mpesakenya_logs:timecreated',
        ], 'privacy:metadata:paygw_mpesakenya_logs');
        
        return $collection;
    }
    
    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        
        if ($context->contextlevel != CONTEXT_USER) {
            return;
        }
        
        $sql = "SELECT userid FROM {paygw_mpesakenya_transactions} WHERE userid = ?";
        $params = [$context->instanceid];
        $userlist->add_from_sql('userid', $sql, $params);
    }
    
    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        
        $user = $contextlist->get_user();
        
        foreach ($contextlist as $context) {
            if ($context->contextlevel == CONTEXT_USER && $context->instanceid == $user->id) {
                $DB->delete_records('paygw_mpesakenya_transactions', ['userid' => $user->id]);
                
                // Also clean up any related log entries.
                $transactionids = $DB->get_fieldset_select(
                    'paygw_mpesakenya_logs',
                    'DISTINCT transactionid',
                    'transactionid IN (SELECT id FROM {paygw_mpesakenya_transactions} WHERE userid = ?)',
                    [$user->id]
                );
                
                if (!empty($transactionids)) {
                    list($insql, $params) = $DB->get_in_or_equal($transactionids, SQL_PARAMS_NAMED);
                    $DB->delete_records_select('paygw_mpesakenya_logs', "transactionid $insql", $params);
                }
            }
        }
    }
    
    /**
     * Delete all users in the user list.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        
        $context = $userlist->get_context();
        
        if ($context->contextlevel != CONTEXT_USER) {
            return;
        }
        
        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }
        
        list($insql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        
        // Delete transactions.
        $DB->delete_records_select('paygw_mpesakenya_transactions', "userid $insql", $params);
        
        // Delete related logs.
        $transactionids = $DB->get_fieldset_select(
            'paygw_mpesakenya_logs',
            'DISTINCT transactionid',
            "transactionid IN (SELECT id FROM {paygw_mpesakenya_transactions} WHERE userid $insql)",
            $params
        );
        
        if (!empty($transactionids)) {
            list($insql, $params) = $DB->get_in_or_equal($transactionids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('paygw_mpesakenya_logs', "transactionid $insql", $params);
        }
    }
    
    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        
        if ($context->contextlevel != CONTEXT_USER) {
            return;
        }
        
        $userid = $context->instanceid;
        
        // Delete all transactions for the user.
        $DB->delete_records('paygw_mpesakenya_transactions', ['userid' => $userid]);
        
        // Also clean up any related log entries.
        $transactionids = $DB->get_fieldset_select(
            'paygw_mpesakenya_logs',
            'DISTINCT transactionid',
            'transactionid IN (SELECT id FROM {paygw_mpesakenya_transactions} WHERE userid = ?)',
            [$userid]
        );
        
        if (!empty($transactionids)) {
            list($insql, $params) = $DB->get_in_or_equal($transactionids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('paygw_mpesakenya_logs', "transactionid $insql", $params);
        }
    }
}
