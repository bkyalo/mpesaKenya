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
 * Steps definitions for M-Pesa Kenya payment gateway.
 *
 * @package    paygw_mpesakenya
 * @category   test
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;

/**
 * Steps definitions for M-Pesa Kenya payment gateway.
 */
class behat_paygw_mpesakenya extends behat_base {

    /**
     * Configure M-Pesa settings for testing.
     *
     * @Given /^I configure mpesa$/
     */
    public function i_configure_mpesa() {
        global $CFG, $DB;

        // Set test mode.
        set_config('environment', 'sandbox', 'paygw_mpesakenya');
        
        // Set test credentials.
        set_config('consumerkey', 'test_consumer_key', 'paygw_mpesakenya');
        set_config('consumersecret', 'test_consumer_secret', 'paygw_mpesakenya');
        set_config('shortcode', '174379', 'paygw_mpesakenya');
        set_config('initiator', 'testapi', 'paygw_mpesakenya');
        set_config('securitycredential', 'test_credential', 'paygw_mpesakenya');
        set_config('passkey', 'test_passkey', 'paygw_mpesakenya');
        set_config('callbackurl', $CFG->wwwroot . '/payment/gateway/mpesakenya/callback.php', 'paygw_mpesakenya');
    }

    /**
     * Check if user is enrolled in a course.
     *
     * @Then /^I should be enrolled in course "([^"]*)"$/
     * @param string $courseshortname
     * @throws Exception
     */
    public function i_should_be_enrolled_in_course($courseshortname) {
        global $DB, $USER;

        $course = $DB->get_record('course', ['shortname' => $courseshortname], '*', MUST_EXIST);
        $context = context_course::instance($course->id);
        
        if (!is_enrolled($context, $USER->id, '', true)) {
            throw new Exception("User is not enrolled in course $courseshortname");
        }
    }

    /**
     * Check if user is not enrolled in a course.
     *
     * @Then /^I should not be enrolled in course "([^"]*)"$/
     * @param string $courseshortname
     * @throws Exception
     */
    public function i_should_not_be_enrolled_in_course($courseshortname) {
        global $DB, $USER;

        $course = $DB->get_record('course', ['shortname' => $courseshortname], '*', MUST_EXIST);
        $context = context_course::instance($course->id);
        
        if (is_enrolled($context, $USER->id, '', true)) {
            throw new Exception("User is enrolled in course $courseshortname but should not be");
        }
    }

    /**
     * Wait for the page to be ready.
     *
     * @Given /^I wait until the page is ready$/
     */
    public function i_wait_until_the_page_is_ready() {
        $this->getSession()->wait(10000, "(0 === jQuery.active && 0 === jQuery(':animated').length)");
    }
}
