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
 * M-Pesa Kenya payment gateway installer script.
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your.email@example.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Install function for the M-Pesa Kenya payment gateway.
 *
 * Enables the M-Pesa payment gateway on installation.
 * It still needs to be configured and enabled for accounts.
 */
function xmldb_paygw_mpesakenya_install() {
    global $CFG;

    // Enable the M-Pesa payment gateway on installation.
    $order = !empty($CFG->paygw_plugins_sortorder) ? explode(',', $CFG->paygw_plugins_sortorder) : [];
    
    // Add our plugin to the list if it's not already there.
    if (!in_array('mpesakenya', $order)) {
        $order[] = 'mpesakenya';
        set_config('paygw_plugins_sortorder', implode(',', $order));
    }
    
    // Set default settings if they don't exist.
    if (!isset($CFG->paygw_mpesakenya_environment)) {
        set_config('paygw_mpesakenya_environment', 'sandbox', 'paygw_mpesakenya');
    }
    
    if (!isset($CFG->paygw_mpesakenya_country)) {
        set_config('paygw_mpesakenya_country', 'KE', 'paygw_mpesakenya');
    }
    
    if (!isset($CFG->paygw_mpesakenya_currency)) {
        set_config('paygw_mpesakenya_currency', 'KES', 'paygw_mpesakenya');
    }
}
