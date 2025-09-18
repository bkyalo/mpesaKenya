<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

// Require login and proper permissions
require_login();
require_capability('moodle/site:config', context_system::instance());

// Include the monitor class
require_once(__DIR__ . '/classes/monitor.php');

// Set up the page
$PAGE->set_url(new moodle_url('/payment/gateway/mpesakenya/dashboard.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('mpesadashboard', 'paygw_mpesakenya'));
$PAGE->set_heading(get_string('mpesadashboard', 'paygw_mpesakenya'));
$PAGE->set_pagelayout('admin');

// Add CSS
$PAGE->requires->css(new moodle_url('/payment/gateway/mpesakenya/styles/dashboard.css'));

// Run checks
$results = \paygw_mpesakenya\monitor::run_checks();

// Output the page header
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('mpesadashboard', 'paygw_mpesakenya'));

echo '<div class="mpesa-dashboard">';

// Status overview
$statusclass = $results['status'] === 'error' ? 'alert-danger' : 
              ($results['status'] === 'warning' ? 'alert-warning' : 'alert-success');

echo '<div class="status-overview ' . $statusclass . '">';
echo '<h3>Status: <strong>' . strtoupper($results['status']) . '</strong></h3>';
echo '<p>Last checked: ' . userdate($results['timestamp']) . '</p>';
echo '</div>';

// Summary of checks
echo '<div class="check-summary">';
echo '<h4>Checks Summary</h4>';

echo '<div class="row">';
foreach ($results['checks'] as $check) {
    $icon = $check['status'] === 'ok' ? 'fa-check-circle' : 
           ($check['status'] === 'warning' ? 'fa-exclamation-triangle' : 'fa-times-circle');
    $statusclass = 'check-' . $check['status'];
    
    echo '<div class="col-md-4">';
    echo '<div class="check-item ' . $statusclass . '">';
    echo '<i class="fa ' . $icon . '"></i> ';
    echo '<span class="check-name">' . $check['name'] . '</span>';
    echo ' <span class="check-status">(' . $check['status'] . ')</span>';
    
    // Show a brief summary
    if (isset($check['details']['count'])) {
        echo '<div class="check-detail">';
        echo 'Count: ' . $check['details']['count'];
        
        if (isset($check['details']['threshold'])) {
            echo ' (Threshold: ' . $check['details']['threshold'] . ')';
        }
        
        echo '</div>';
    } elseif (isset($check['details']['http_code'])) {
        echo '<div class="check-detail">';
        echo 'HTTP ' . $check['details']['http_code'];
        echo '</div>';
    }
    
    echo '</div>'; // .check-item
    echo '</div>'; // .col-md-4
}
echo '</div>'; // .row
echo '</div>'; // .check-summary

// Detailed view
echo '<div class="detailed-view">';
echo '<h4>Detailed Status</h4>';

echo '<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">';

foreach ($results['checks'] as $checkid => $check) {
    $statusclass = 'check-' . $check['status'];
    $collapsed = $check['status'] === 'ok' ? 'collapsed' : '';
    $expanded = $check['status'] !== 'ok' ? 'true' : 'false';
    $show = $check['status'] !== 'ok' ? 'show' : '';
    
    echo '<div class="panel panel-default ' . $statusclass . '">';
    echo '<div class="panel-heading" role="tab" id="heading-' . $checkid . '">';
    echo '<h5 class="panel-title">';
    echo '<a role="button" data-toggle="collapse" data-parent="#accordion" ';
    echo 'href="#collapse-' . $checkid . '" aria-expanded="' . $expanded . '" ';
    echo 'aria-controls="collapse-' . $checkid . '" class="' . $collapsed . '">';
    echo '<i class="fa ' . ($check['status'] === 'ok' ? 'fa-check' : 
         ($check['status'] === 'warning' ? 'fa-exclamation-triangle' : 'fa-times')) . '"></i> ';
    echo $check['name'];
    echo '</a>';
    echo '</h5>';
    echo '</div>';
    
    echo '<div id="collapse-' . $checkid . '" class="panel-collapse collapse ' . $show . '" ';
    echo 'role="tabpanel" aria-labelledby="heading-' . $checkid . '">';
    echo '<div class="panel-body">';
    
    if (!empty($check['details'])) {
        echo '<dl class="dl-horizontal">';
        foreach ($check['details'] as $key => $value) {
            echo '<dt>' . ucfirst(str_replace('_', ' ', $key)) . ':</dt>';
            echo '<dd>';
            
            if (is_array($value) || is_object($value)) {
                echo '<pre>' . htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>';
            } else {
                echo htmlspecialchars($value);
            }
            
            echo '</dd>';
        }
        echo '</dl>';
    } else {
        echo '<p>No details available.</p>';
    }
    
    echo '</div>'; // .panel-body
    echo '</div>'; // .panel-collapse
    echo '</div>'; // .panel
}

echo '</div>'; // .panel-group
echo '</div>'; // .detailed-view

// Action buttons
echo '<div class="action-buttons">';
echo '<a href="' . new moodle_url('/payment/gateway/mpesakenya/monitoring.php') . '" class="btn btn-primary" target="_blank">';
echo '<i class="fa fa-arrow-circle-right"></i> View Raw JSON';
echo '</a> ';

echo '<a href="' . $PAGE->url->out(false, ['refresh' => 1]) . '" class="btn btn-default">';
echo '<i class="fa fa-refresh"></i> Refresh';
echo '</a>';
echo '</div>';

echo '</div>'; // .mpesa-dashboard

// Add JavaScript for auto-refresh and interactivity
$PAGE->requires->js_init_code('$(".panel-heading a").click(function() {
    $(this).find("i").toggleClass("fa-chevron-down fa-chevron-up");
});');

// Output the page footer
echo $OUTPUT->footer();
