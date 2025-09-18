<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Include the monitor class
require_once(__DIR__ . '/../classes/monitor.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    [
        'help' => false,
        'verbose' => false,
        'alert' => true,
    ],
    [
        'h' => 'help',
        'v' => 'verbose',
        'a' => 'alert',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "M-Pesa Gateway Monitoring Tool\n";
    $help .= "\n";
    $help .= "Options:\n";
    $help .= "  -h, --help            Print out this help\n";
    $help .= "  -v, --verbose         Print verbose output\n";
    $help .= "  -a, --alert           Send email alerts (default: true)\n";
    cli_writeln($help);
    exit(0);
}

// Run checks
$results = \paygw_mpesakenya\monitor::run_checks();

// Send alerts if needed and not disabled
if ($options['alert']) {
    $alertssent = \paygw_mpesakenya\monitor::send_alert_if_needed($results);
}

// Output results
if ($options['verbose']) {
    $status = strtoupper($results['status']);
    $color = '';
    $reset = '';
    
    if (cli_is_colored_theme()) {
        $color = $results['status'] === 'error' ? 'red' : ($results['status'] === 'warning' ? 'yellow' : 'green');
        $reset = '\033[0m';
        $color = '\033[1;' . ($color === 'red' ? '31' : ($color === 'yellow' ? '33' : '32')) . 'm';
    }
    
    cli_heading("M-Pesa Gateway Status: {$color}{$status}{$reset}");
    cli_writeln("Timestamp: " . date('Y-m-d H:i:s', $results['timestamp']));
    cli_writeln("");
    
    foreach ($results['checks'] as $check) {
        $status = strtoupper($check['status']);
        $color = '';
        
        if (cli_is_colored_theme()) {
            $color = $check['status'] === 'error' ? '\033[1;31m' : 
                    ($check['status'] === 'warning' ? '\033[1;33m' : '\033[1;32m');
        }
        
        cli_writeln("{$color}{$status}{$reset} - {$check['name']}");
        
        foreach ($check['details'] as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_PRETTY_UNESCAPED_SLASHES);
            }
            cli_writeln("  {$key}: {$value}");
        }
        
        cli_writeln("");
    }
    
    if (isset($alertssent) && $alertssent) {
        cli_writeln("\nAlerts have been sent to the site administrator.");
    }
}

// Exit with appropriate status code
exit($results['status'] === 'error' ? 2 : ($results['status'] === 'warning' ? 1 : 0));
