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
 * Code coverage information for M-Pesa Kenya payment gateway.
 *
 * @package    paygw_mpesakenya
 * @category   test
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Code coverage information.
 *
 * @return stdClass The code coverage information
 */
function paygw_mpesakenya_coverage_info() {
    $coverage = new stdClass();
    $coverage->includes = [
        'classes/mpesa_helper.php',
        'classes/gateway.php',
        'classes/event/*.php',
        'classes/external/*.php',
        'lib.php',
    ];
    $coverage->excludes = [
        'db/*.php',
        'lang/*',
        'templates/*',
        'tests/*',
        'vendor/*',
    ];
    $coverage->extension = '.php';
    $coverage->includesre = '~^paygw/mpesakenya/([a-z0-9_/]+)\.php$~';
    $coverage->excludesre = '~^paygw/mpesakenya/(tests|templates|lang|db|vendor)/~';
    
    return $coverage;
}
