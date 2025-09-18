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
 * M-Pesa Kenya payment gateway upgrade script
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the M-Pesa Kenya payment gateway
 *
 * @param int $oldversion The version number of the plugin that was installed
 * @return bool
 */
function xmldb_paygw_mpesakenya_upgrade($oldversion) {
    global $DB;
    
    $dbman = $DB->get_manager();
    
    if ($oldversion < 2024091801) {
        // Define field environment to be added to paygw_mpesakenya_transactions.
        $table = new xmldb_table('paygw_mpesakenya_transactions');
        $field = new xmldb_field('environment', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'sandbox', 'currency');
        
        // Conditionally launch add field environment.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Mpesakenya savepoint reached.
        upgrade_plugin_savepoint(true, 2024091801, 'paygw', 'mpesakenya');
    }
    
    if ($oldversion < 2024091802) {
        // Define table paygw_mpesakenya_logs to be created.
        $table = new xmldb_table('paygw_mpesakenya_logs');
        
        // Adding fields to table paygw_mpesakenya_logs.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('transactionid', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'id');
        $table->add_field('checkout_request_id', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'transactionid');
        $table->add_field('request', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'checkout_request_id');
        $table->add_field('response', XMLDB_TYPE_TEXT, null, null, null, null, null, 'request');
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, 'response');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'status');
        
        // Adding keys to table paygw_mpesakenya_logs.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('transactionid_idx', XMLDB_KEY_INDEX, ['transactionid']);
        $table->add_key('checkout_request_id_idx', XMLDB_KEY_INDEX, ['checkout_request_id']);
        
        // Conditionally launch create table for paygw_mpesakenya_logs.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        // Mpesakenya savepoint reached.
        upgrade_plugin_savepoint(true, 2024091802, 'paygw', 'mpesakenya');
    }
    
    if ($oldversion < 2024091803) {
        // Define field rawdata to be added to paygw_mpesakenya_transactions.
        $table = new xmldb_table('paygw_mpesakenya_transactions');
        $field = new xmldb_field('rawdata', XMLDB_TYPE_TEXT, null, null, null, null, null, 'timemodified');
        
        // Conditionally launch add field rawdata.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Mpesakenya savepoint reached.
        upgrade_plugin_savepoint(true, 2024091803, 'paygw', 'mpesakenya');
    }
    
    return true;
}
