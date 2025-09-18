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
     * Log a debug message.
     *
     * @param string $message The message to log
     * @param mixed $data Optional data to include in the log
     * @param string $method The method where the log is called from
     */
    protected static function debug(string $message, $data = null, string $method = '') {
        global $CFG;
        
        if (!empty($method)) {
            $message = "[{$method}] {$message}";
        }
        
        if ($data !== null) {
            if (is_scalar($data)) {
                $message .= ": {$data}";
            } else {
                $message .= ": " . print_r($data, true);
            }
        }
        
        debugging($message, DEBUG_DEVELOPER, self::DEBUG_LEVEL);
        
        // Also log to a file if debugging is enabled
        if (!empty($CFG->debug) && $CFG->debug >= DEBUG_DEVELOPER) {
            $logfile = $CFG->dataroot . '/temp/mpesa_debug.log';
            $timestamp = date('Y-m-d H:i:s');
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = isset($backtrace[1]) ? $backtrace[1]['class'] . '::' . $backtrace[1]['function'] : 'unknown';
            
            $logmessage = "[{$timestamp}] [{$caller}] {$message}" . PHP_EOL;
            @file_put_contents($logfile, $logmessage, FILE_APPEND);
        }
    }
    
    /**
     * Log an error message.
     *
     * @param string $message The error message
     * @param mixed $data Optional data to include in the log
     * @param string $method The method where the error occurred
     */
    protected static function error(string $message, $data = null, string $method = '') {
        self::debug("ERROR: {$message}", $data, $method);
    }
}
