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

namespace paygw_mpesakenya;

defined('MOODLE_INTERNAL') || die();

class monitor {
    /**
     * Run all monitoring checks
     */
    public static function run_checks() {
        global $DB;
        
        $results = [
            'timestamp' => time(),
            'status' => 'ok',
            'checks' => []
        ];
        
        // Check 1: API Connectivity
        $apicheck = self::check_api_connectivity();
        $results['checks']['api_connectivity'] = $apicheck;
        
        // Check 2: Recent Failed Transactions
        $failedcheck = self::check_recent_failures();
        $results['checks']['recent_failures'] = $failedcheck;
        
        // Check 3: Stale Pending Transactions
        $pendingcheck = self::check_stale_pending();
        $results['checks']['stale_pending'] = $pendingcheck;
        
        // Check 4: Database Connection
        $dbcheck = self::check_database_connection();
        $results['checks']['database_connection'] = $dbcheck;
        
        // Check 5: Configuration
        $configcheck = self::check_configuration();
        $results['checks']['configuration'] = $configcheck;
        
        // Update overall status if any checks failed
        foreach ($results['checks'] as $check) {
            if (isset($check['status']) && $check['status'] !== 'ok') {
                $results['status'] = 'error';
                break;
            } elseif (isset($check['status']) && $check['status'] === 'warning' && $results['status'] !== 'error') {
                $results['status'] = 'warning';
            }
        }
        
        return $results;
    }
    
    /**
     * Check M-Pesa API connectivity
     */
    private static function check_api_connectivity() {
        global $CFG;
        
        $result = [
            'name' => 'API Connectivity',
            'status' => 'ok',
            'details' => []
        ];
        
        try {
            $apiurl = get_config('paygw_mpesakenya', 'environment') === 'sandbox' 
                ? 'https://sandbox.safaricom.co.ke/'
                : 'https://api.safaricom.co.ke/';
                
            $ch = curl_init($apiurl . 'oauth/v1/generate?grant_type=client_credentials');
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => ['Authorization: Basic ' . base64_encode(
                    get_config('paygw_mpesakenya', 'consumerkey') . ':' . 
                    get_config('paygw_mpesakenya', 'consumersecret')
                )],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            ]);
            
            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlerrno = curl_errno($ch);
            $curlerror = curl_error($ch);
            curl_close($ch);
            
            $result['details']['http_code'] = $httpcode;
            $result['details']['response'] = $response;
            
            if ($curlerrno) {
                $result['status'] = 'error';
                $result['details']['error'] = "cURL Error ($curlerrno): $curlerror";
            } elseif ($httpcode !== 200) {
                $result['status'] = 'error';
                $result['details']['error'] = "API returned HTTP $httpcode";
            }
            
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['details']['error'] = $e->getMessage();
            $result['details']['trace'] = $e->getTraceAsString();
        }
        
        return $result;
    }
    
    /**
     * Check for recent failed transactions
     */
    private static function check_recent_failures() {
        global $DB;
        
        $result = [
            'name' => 'Recent Failed Transactions',
            'status' => 'ok',
            'details' => []
        ];
        
        try {
            $time = time() - 3600; // Last hour
            $count = $DB->count_records_select('paygw_mpesakenya_transactions', 
                'status = ? AND timecreated > ?', 
                ['FAILED', $time]
            );
            
            $result['details']['count'] = $count;
            $result['details']['timeframe'] = 'last hour';
            
            if ($count > 10) {
                $result['status'] = 'error';
                $result['details']['message'] = 'High number of failed transactions detected';
            } elseif ($count > 0) {
                $result['status'] = 'warning';
                $result['details']['message'] = 'Some failed transactions detected';
            }
            
            // Get the last 3 failed transactions for reference
            if ($count > 0) {
                $transactions = $DB->get_records_select('paygw_mpesakenya_transactions',
                    'status = ? AND timecreated > ?',
                    ['FAILED', $time],
                    'timecreated DESC',
                    'id, transactionid, amount, timecreated, errormessage',
                    0,
                    3
                );
                $result['details']['recent_failures'] = array_values($transactions);
            }
            
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['details']['error'] = $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Check for stale pending transactions
     */
    private static function check_stale_pending() {
        global $DB;
        
        $result = [
            'name' => 'Stale Pending Transactions',
            'status' => 'ok',
            'details' => []
        ];
        
        try {
            $time = time() - 900; // 15 minutes ago
            $count = $DB->count_records_select('paygw_mpesakenya_transactions',
                'status = ? AND timecreated < ?',
                ['PENDING', $time]
            );
            
            $result['details']['count'] = $count;
            $result['details']['threshold'] = '15 minutes';
            
            if ($count > 0) {
                $result['status'] = 'warning';
                $result['details']['message'] = 'Transactions pending for more than 15 minutes';
                
                // Get the oldest pending transaction
                $oldest = $DB->get_record_sql(
                    'SELECT id, transactionid, amount, timecreated 
                     FROM {paygw_mpesakenya_transactions} 
                     WHERE status = ? 
                     ORDER BY timecreated ASC',
                    ['PENDING'],
                    IGNORE_MULTIPLE
                );
                
                if ($oldest) {
                    $age = time() - $oldest->timecreated;
                    $result['details']['oldest_pending'] = [
                        'id' => $oldest->id,
                        'transactionid' => $oldest->transactionid,
                        'age_seconds' => $age,
                        'age_human' => format_time($age)
                    ];
                }
            }
            
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['details']['error'] = $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Check database connection and tables
     */
    private static function check_database_connection() {
        global $DB;
        
        $result = [
            'name' => 'Database Connection',
            'status' => 'ok',
            'details' => []
        ];
        
        try {
            // Check if tables exist
            $tables = ['paygw_mpesakenya_transactions', 'paygw_mpesakenya_logs'];
            $dbman = $DB->get_manager();
            
            foreach ($tables as $table) {
                if (!$dbman->table_exists($table)) {
                    $result['status'] = 'error';
                    $result['details']['missing_tables'][] = $table;
                }
            }
            
            // Check if we can query the transactions table
            if ($result['status'] === 'ok') {
                $count = $DB->count_records('paygw_mpesakenya_transactions');
                $result['details']['total_transactions'] = $count;
            }
            
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['details']['error'] = $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Check plugin configuration
     */
    private static function check_configuration() {
        $result = [
            'name' => 'Configuration',
            'status' => 'ok',
            'details' => []
        ];
        
        $required = [
            'consumerkey' => 'Consumer Key',
            'consumersecret' => 'Consumer Secret',
            'shortcode' => 'Shortcode',
            'passkey' => 'Passkey'
        ];
        
        $missing = [];
        $insecure = [];
        
        foreach ($required as $key => $name) {
            $value = get_config('paygw_mpesakenya', $key);
            
            if (empty($value)) {
                $missing[] = $name;
            } elseif (strpos($key, 'secret') !== false && $value === 'YOUR_SECRET_HERE') {
                $insecure[] = $name . ' is using default value';
            }
        }
        
        if (!empty($missing)) {
            $result['status'] = 'error';
            $result['details']['missing_config'] = $missing;
        }
        
        if (!empty($insecure)) {
            $result['status'] = $result['status'] === 'ok' ? 'warning' : $result['status'];
            $result['details']['insecure_config'] = $insecure;
        }
        
        // Check environment
        $environment = get_config('paygw_mpesakenya', 'environment');
        $result['details']['environment'] = $environment;
        
        if ($environment === 'production') {
            $result['details']['callback_url'] = (new \moodle_url('/payment/gateway/mpesakenya/callback.php'))->out(false);
        }
        
        return $result;
    }
    
    /**
     * Send alert if needed
     */
    public static function send_alert_if_needed($results) {
        if ($results['status'] === 'ok') {
            return false;
        }
        
        $subject = "[M-Pesa Gateway Alert] ";
        $subject .= $results['status'] === 'error' ? 'Critical Issues Detected' : 'Warning';
        
        $message = "M-Pesa Gateway Monitoring Alert\n";
        $message .= "Status: " . strtoupper($results['status']) . "\n";
        $message .= "Time: " . date('Y-m-d H:i:s', $results['timestamp']) . "\n\n";
        
        foreach ($results['checks'] as $check) {
            if ($check['status'] !== 'ok') {
                $message .= "\n=== {$check['name']} ({$check['status']}) ===\n";
                
                foreach ($check['details'] as $key => $value) {
                    if (is_array($value) || is_object($value)) {
                        $value = json_encode($value, JSON_PRETTY_PRINT);
                    }
                    $message .= "$key: $value\n";
                }
            }
        }
        
        // Send email to admin
        $admin = get_admin();
        if ($admin && !empty($admin->email)) {
            $supportuser = \core_user::get_support_user();
            email_to_user($admin, $supportuser, $subject, $message);
            return true;
        }
        
        return false;
    }
}
