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

namespace paygw_mpesakenya\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task for M-Pesa monitoring.
 */
class monitor_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task:monitor', 'paygw_mpesakenya');
    }

    /**
     * Run the task.
     */
    public function execute() {
        global $CFG;
        
        require_once($CFG->dirroot . '/payment/gateway/mpesakenya/classes/monitor.php');
        
        // Run monitoring checks
        $results = \paygw_mpesakenya\monitor::run_checks();
        
        // Send alerts if needed
        if ($results['status'] !== 'ok') {
            \paygw_mpesakenya\monitor::send_alert_if_needed($results);
        }
        
        // Log the results
        $this->log_monitoring_results($results);
        
        return true;
    }
    
    /**
     * Log monitoring results
     * 
     * @param array $results Monitoring results
     */
    private function log_monitoring_results($results) {
        $logfile = $this->get_log_file_path();
        $logdata = [
            'time' => date('Y-m-d H:i:s'),
            'status' => $results['status'],
            'checks' => []
        ];
        
        // Extract relevant information for logging
        foreach ($results['checks'] as $check) {
            $logcheck = [
                'name' => $check['name'],
                'status' => $check['status']
            ];
            
            // Add specific details based on check type
            switch ($check['name']) {
                case 'API Connectivity':
                    $logcheck['http_code'] = $check['details']['http_code'] ?? 0;
                    break;
                    
                case 'Recent Failed Transactions':
                    $logcheck['count'] = $check['details']['count'] ?? 0;
                    break;
                    
                case 'Stale Pending Transactions':
                    $logcheck['count'] = $check['details']['count'] ?? 0;
                    break;
            }
            
            $logdata['checks'][] = $logcheck;
        }
        
        // Ensure directory exists
        $logdir = dirname($logfile);
        if (!file_exists($logdir)) {
            mkdir($logdir, 0777, true);
        }
        
        // Append to log file
        file_put_contents(
            $logfile,
            json_encode($logdata, JSON_PRETTY_PRINT) . ",\n",
            FILE_APPEND
        );
        
        // Rotate logs if needed
        $this->rotate_logs();
    }
    
    /**
     * Get the path to the log file
     */
    private function get_log_file_path() {
        global $CFG;
        $logdir = $CFG->dataroot . '/mpesalogs';
        $today = date('Y-m-d');
        return "$logdir/monitoring_$today.log";
    }
    
    /**
     * Rotate log files
     */
    private function rotate_logs() {
        $logdir = dirname($this->get_log_file_path());
        $files = glob("$logdir/monitoring_*.log");
        $keep_days = 30; // Keep logs for 30 days
        
        foreach ($files as $file) {
            // Extract date from filename
            if (preg_match('/monitoring_(\d{4}-\d{2}-\d{2})\.log$/', $file, $matches)) {
                $filedate = strtotime($matches[1]);
                $days_old = (time() - $filedate) / (60 * 60 * 24);
                
                if ($days_old > $keep_days) {
                    @unlink($file);
                }
            }
        }
    }
}
