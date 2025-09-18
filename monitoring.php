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

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

// Require login for web access
if (!CLI_SCRIPT) {
    require_login();
    require_capability('moodle/site:config', context_system::instance());
}

// Include the monitor class
require_once(__DIR__ . '/classes/monitor.php');

// Set JSON output
header('Content-Type: application/json');

// Run checks
$results = \paygw_mpesakenya\monitor::run_checks();

// Send alerts if needed
if (!CLI_SCRIPT) {
    \paygw_mpesakenya\monitor::send_alert_if_needed($results);
}

// Output results
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

// Set appropriate HTTP status code
if ($results['status'] === 'error') {
    http_response_code(503); // Service Unavailable
} elseif ($results['status'] === 'warning') {
    http_response_code(206); // Partial Content
}
