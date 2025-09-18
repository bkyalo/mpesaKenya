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
 * Debug trait for M-Pesa Kenya payment gateway.
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_mpesakenya\traits;

defined('MOODLE_INTERNAL') || die();

/**
 * Trait containing debug functionality.
 */
trait debug_trait {
    
    /** @var string Component name for debugging */
    const COMPONENT = 'paygw_mpesakenya';
    
    /** @var int Debug level - DEBUG_DEVELOPER for development, DEBUG_NONE for production */
    const DEBUG_LEVEL = DEBUG_DEVELOPER;
    
    /**
     * Log a debug message with context.
     *
     * @param string $message The message to log
     * @param mixed $data Optional data to include in the log
     * @param string $method The method where the log is called from
     * @param array $backtrace Optional backtrace data
     * @param bool $logtofile Whether to log to file (in addition to debug output)
     */
    protected static function debug(string $message, $data = null, string $method = '', array $backtrace = null, bool $logtofile = true) {
        global $CFG, $USER, $SESSION;
        
        // Only proceed if debugging is enabled
        if (empty($CFG->debug) || $CFG->debug < DEBUG_DEVELOPER) {
            return;
        }
        
        // Get backtrace if not provided
        if ($backtrace === null) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        }
        
        // Get calling method/class info
        $caller = '';
        if (!empty($backtrace[1])) {
            $caller = ($backtrace[1]['class'] ?? '') . ($backtrace[1]['type'] ?? '') . ($backtrace[1]['function'] ?? '');
        }
        
        // Format the message with context
        $context = [
            'timestamp' => microtime(true),
            'method' => $method ?: $caller,
            'userid' => $USER->id ?? 0,
            'session' => session_id(),
            'ip' => getremoteaddr(),
            'request' => $_SERVER['REQUEST_URI'] ?? ''
        ];
        
        // Format the final message
        $logMessage = sprintf(
            "[%s] [%s] [user:%s] %s",
            date('Y-m-d H:i:s'),
            $context['method'],
            $context['userid'],
            $message
        );
        
        // Add data if provided
        if ($data !== null) {
            $logMessage .= ": " . (is_scalar($data) ? $data : json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
        
        // Log to Moodle's debugging system
        debugging($logMessage, DEBUG_DEVELOPER, self::DEBUG_LEVEL);
        
        // Log to file if enabled
        if ($logtofile) {
            $logdir = $CFG->dataroot . '/temp/paygw_mpesakenya';
            $logfile = $logdir . '/debug.log';
            
            // Create log directory if it doesn't exist
            if (!file_exists($logdir)) {
                mkdir($logdir, 0777, true);
            }
            
            // Add context to file log
            $fileLog = $logMessage . PHP_EOL . "Context: " . json_encode($context, JSON_PRETTY_PRINT) . PHP_EOL . str_repeat('-', 80) . PHP_EOL;
            @file_put_contents($logfile, $fileLog, FILE_APPEND);
        }
    }
    
    /**
     * Log an error message with stack trace.
     *
     * @param string $message The error message
     * @param mixed $data Optional data to include in the log
     * @param string $method The method where the error occurred
     * @param \Throwable $exception Optional exception object
     * @param bool $notifyadmin Whether to notify site admin
     */
    protected static function error(string $message, $data = null, string $method = '', ?\Throwable $exception = null, bool $notifyadmin = true) {
        $backtrace = $exception ? $exception->getTrace() : debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        
        $errorData = [
            'error' => $message,
            'exception' => $exception ? [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ] : null,
            'data' => $data
        ];
        
        self::debug("ERROR: {$message}", $errorData, $method, $backtrace);
        
        // Optionally notify admin
        if ($notifyadmin && !empty($CFG->siteadmins)) {
            $admin = get_admin();
            if ($admin) {
                $subject = "[MPesa Gateway Error] " . substr($message, 0, 50) . (strlen($message) > 50 ? '...' : '');
                $body = "Error: {$message}\n\n" . json_encode($errorData, JSON_PRETTY_PRINT);
                email_to_user($admin, $admin, $subject, $body);
            }
        }
    }
    
    /**
     * Log API request/response.
     *
     * @param string $endpoint API endpoint
     * @param array $request Request data
     * @param mixed $response Response data
     * @param array $info cURL info
     * @param string $method HTTP method
     */
    protected static function logApiCall(string $endpoint, array $request, $response, array $info, string $method = 'POST') {
        $logData = [
            'endpoint' => $endpoint,
            'method' => $method,
            'request' => $request,
            'response' => $response,
            'http_code' => $info['http_code'] ?? 0,
            'total_time' => $info['total_time'] ?? 0,
            'connect_time' => $info['connect_time'] ?? 0,
            'namelookup_time' => $info['namelookup_time'] ?? 0,
            'pretransfer_time' => $info['pretransfer_time'] ?? 0,
            'starttransfer_time' => $info['starttransfer_time'] ?? 0,
            'redirect_count' => $info['redirect_count'] ?? 0,
            'redirect_time' => $info['redirect_time'] ?? 0,
            'primary_ip' => $info['primary_ip'] ?? '',
            'primary_port' => $info['primary_port'] ?? 0,
            'local_ip' => $info['local_ip'] ?? '',
            'local_port' => $info['local_port'] ?? 0
        ];
        
        self::debug("API Call: {$method} {$endpoint}", $logData, __METHOD__, null, true);
        
        // Log to dedicated API log file
        $logdir = $CFG->dataroot . '/temp/paygw_mpesakenya';
        $apilogfile = $logdir . '/api.log';
        
        if (!file_exists($logdir)) {
            mkdir($logdir, 0777, true);
        }
        
        $logEntry = sprintf(
            "[%s] %s %s\nRequest: %s\nResponse: %s\nTime: %.3fs\nHTTP: %s\n\n",
            date('Y-m-d H:i:s'),
            $method,
            $endpoint,
            json_encode($request, JSON_PRETTY_PRINT),
            is_string($response) ? $response : json_encode($response, JSON_PRETTY_PRINT),
            $info['total_time'] ?? 0,
            $info['http_code'] ?? 0
        );
        
        @file_put_contents($apilogfile, $logEntry, FILE_APPEND);
    }
}
