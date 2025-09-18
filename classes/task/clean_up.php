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
 * Clean up task for the M-Pesa Kenya payment gateway plugin.
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_mpesakenya\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task to clean up old pending transactions.
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clean_up extends \core\task\scheduled_task {
    
    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('cleanuptask', 'paygw_mpesakenya');
    }
    
    /**
     * Execute the task - clean up old pending transactions.
     */
    public function execute() {
        global $DB, $CFG;
        
        require_once($CFG->dirroot . '/payment/gateway/mpesakenya/lib.php');
        
        // Delete pending transactions older than 24 hours.
        $cutoff = time() - (24 * 3600);
        
        // Find all pending transactions older than the cutoff.
        $oldtransactions = $DB->get_records_sql(
            "SELECT id FROM {paygw_mpesakenya_transactions} 
             WHERE status = ? AND timecreated < ?", 
            ['PENDING', $cutoff],
            0,
            1000 // Limit to 1000 at a time to avoid timeouts
        );
        
        if (!empty($oldtransactions)) {
            $ids = array_keys($oldtransactions);
            list($insql, $params) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
            
            // Log the cleanup for debugging.
            $count = count($ids);
            mtrace("Deleting $count old pending transactions");
            
            // Delete the transactions.
            $DB->delete_records_select(
                'paygw_mpesakenya_transactions',
                "id $insql",
                $params
            );
            
            // Also clean up any related log entries.
            $DB->delete_records_select(
                'paygw_mpesakenya_logs',
                "transactionid $insql",
                $params
            );
        }
        
        // Clean up completed transactions older than 90 days.
        $oldcutoff = time() - (90 * 24 * 3600);
        $DB->delete_records_select(
            'paygw_mpesakenya_transactions',
            'status = :status AND timemodified < :cutoff',
            ['status' => 'COMPLETED', 'cutoff' => $oldcutoff]
        );
        
        // Clean up failed/cancelled transactions older than 30 days.
        $oldcutoff = time() - (30 * 24 * 3600);
        $DB->delete_records_select(
            'paygw_mpesakenya_transactions',
            'status IN (:status1, :status2) AND timemodified < :cutoff',
            ['status1' => 'FAILED', 'status2' => 'CANCELLED', 'cutoff' => $oldcutoff]
        );
    }
}
