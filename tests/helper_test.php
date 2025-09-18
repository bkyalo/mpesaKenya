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
 * M-Pesa Kenya payment gateway test cases.
 *
 * @package    paygw_mpesakenya
 * @category   test
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_mpesakenya;

use core_payment\helper;
use paygw_mpesakenya\mpesa_helper;

/**
 * Test cases for M-Pesa Kenya payment gateway.
 */
class mpesa_helper_test extends \advanced_testcase {

    /** @var mpesa_helper */
    protected $mpesahelper;

    /**
     * Set up for every test
     */
    public function setUp(): void {
        global $CFG;
        require_once($CFG->dirroot . '/payment/gateway/mpesakenya/classes/mpesa_helper.php');
        
        $this->resetAfterTest();
        
        // Create a test course and user.
        $this->course = $this->getDataGenerator()->create_course();
        $this->user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, 'student');
        
        // Set up M-Pesa configuration.
        set_config('environment', 'sandbox', 'paygw_mpesakenya');
        set_config('consumerkey', 'test_key', 'paygw_mpesakenya');
        set_config('consumersecret', 'test_secret', 'paygw_mpesakenya');
        set_config('shortcode', '174379', 'paygw_mpesakenya');
        set_config('initiator', 'test', 'paygw_mpesakenya');
        set_config('securitycredential', 'test', 'paygw_mpesakenya');
        set_config('passkey', 'test', 'paygw_mpesakenya');
        
        $this->mpesahelper = new mpesa_helper([
            'environment' => 'sandbox',
            'consumerkey' => 'test_key',
            'consumersecret' => 'test_secret',
            'shortcode' => '174379',
            'initiator' => 'test',
            'securitycredential' => 'test',
            'passkey' => 'test',
        ]);
    }
    
    /**
     * Test getting the access token.
     */
    public function test_get_access_token() {
        $this->resetAfterTest();
        
        // Mock the HTTP request.
        $mockresponse = json_encode([
            'access_token' => 'test_token',
            'expires_in' => '3599'
        ]);
        
        $mock = $this->createMock(\curl::class);
        $mock->expects($this->once())
             ->method('getResponse')
             ->willReturn($mockresponse);
        $mock->expects($this->once())
             ->method('get_errno')
             ->willReturn(0);
             
        $reflection = new \ReflectionClass($this->mpesahelper);
        $property = $reflection->getProperty('curl');
        $property->setAccessible(true);
        $property->setValue($this->mpesahelper, $mock);
        
        $token = $this->mpesahelper->get_access_token();
        $this->assertEquals('test_token', $token);
    }
    
    /**
     * Test STK push request.
     */
    public function test_stk_push() {
        $this->resetAfterTest();
        
        // Mock the HTTP request.
        $mockresponse = json_encode([
            'ResponseCode' => '0',
            'ResponseDescription' => 'Success',
            'MerchantRequestID' => 'test_merchant_id',
            'CheckoutRequestID' => 'test_checkout_id',
            'CustomerMessage' => 'Success. Request accepted for processing'
        ]);
        
        $mock = $this->createMock(\curl::class);
        $mock->expects($this->once())
             ->method('getResponse')
             ->willReturn($mockresponse);
        $mock->expects($this->once())
             ->method('get_errno')
             ->willReturn(0);
             
        $reflection = new \ReflectionClass($this->mpesahelper);
        $property = $reflection->getProperty('curl');
        $property->setAccessible(true);
        $property->setValue($this->mpesahelper, $mock);
        
        $response = $this->mpesahelper->stk_push(100, '254712345678', 'test_ref', 'Test payment');
        
        $this->assertEquals('test_merchant_id', $response['MerchantRequestID']);
        $this->assertEquals('test_checkout_id', $response['CheckoutRequestID']);
    }
    
    /**
     * Test transaction status check.
     */
    public function test_check_transaction_status() {
        $this->resetAfterTest();
        
        // Mock the HTTP request.
        $mockresponse = json_encode([
            'ResultCode' => '0',
            'ResultDesc' => 'The service request is processed successfully.',
            'MerchantRequestID' => 'test_merchant_id',
            'CheckoutRequestID' => 'test_checkout_id',
            'ResponseCode' => '0',
            'ResponseDescription' => 'The service request is processed successfully.'
        ]);
        
        $mock = $this->createMock(\curl::class);
        $mock->expects($this->once())
             ->method('getResponse')
             ->willReturn($mockresponse);
        $mock->expects($this->once())
             ->method('get_errno')
             ->willReturn(0);
             
        $reflection = new \ReflectionClass($this->mpesahelper);
        $property = $reflection->getProperty('curl');
        $property->setAccessible(true);
        $property->setValue($this->mpesahelper, $mock);
        
        $status = $this->mpesahelper->check_transaction_status('test_checkout_id');
        
        $this->assertTrue($status['success']);
        $this->assertEquals('0', $status['resultcode']);
    }
}
