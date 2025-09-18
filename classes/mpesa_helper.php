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
 * Contains helper class to work with M-Pesa Kenya API.
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_mpesakenya;

use core_payment\helper as payment_helper;
use curl;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Contains helper class to work with M-Pesa Kenya API.
 *
 * @package    paygw_mpesakenya
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mpesa_helper {
    /** @var string The base API URL */
    private $baseurl;
    
    /** @var string Client ID */
    private $clientid;
    
    /** @var string Client Secret */
    private $clientsecret;
    
    /** @var string Environment (sandbox or production) */
    private $environment;
    
    /** @var string Business Short Code */
    private $shortcode;
    
    /** @var string Initiator Name */
    private $initiatorname;
    
    /** @var string Security Credential */
    private $securitycredential;
    
    /** @var string Passkey */
    private $passkey;
    
    /** @var string Access Token */
    private $accesstoken;
    
    /** @var int Token expiry timestamp */
    private $tokenexpires;

    /**
     * Constructor.
     *
     * @param string $clientid The client ID
     * @param string $clientsecret The client secret
     * @param string $environment The environment (sandbox or production)
     * @param string $shortcode The business short code
     * @param string $initiatorname The initiator name
     * @param string $securitycredential The security credential
     * @param string $passkey The passkey
     */
    public function __construct(
        string $clientid,
        string $clientsecret,
        string $environment,
        string $shortcode,
        string $initiatorname,
        string $securitycredential,
        string $passkey
    ) {
        $this->clientid = $clientid;
        $this->clientsecret = $clientsecret;
        $this->environment = $environment;
        $this->shortcode = $shortcode;
        $this->initiatorname = $initiatorname;
        $this->securitycredential = $securitycredential;
        $this->passkey = $passkey;
        
        // Set the base URL based on the environment
        $this->baseurl = $environment === 'production' 
            ? 'https://api.safaricom.co.ke' 
            : 'https://sandbox.safaricom.co.ke';
            
        $this->accesstoken = '';
        $this->tokenexpires = 0;
    }

    /**
     * Get an access token from the M-Pesa API.
     *
     * @return string The access token
     * @throws \Exception If the token request fails
     */
    public function get_access_token(): string {
        // Return cached token if it's still valid
        if (!empty($this->accesstoken) && time() < $this->tokenexpires) {
            return $this->accesstoken;
        }
        
        $url = "{$this->baseurl}/oauth/v1/generate?grant_type=client_credentials";
        
        $curl = new curl();
        $curl->setHeader([
            'Authorization: Basic ' . base64_encode("{$this->clientid}:{$this->clientsecret}"),
            'Content-Type: application/json'
        ]);
        
        $response = $curl->get($url);
        $httpcode = $curl->get_info()['http_code'];
        
        if ($httpcode !== 200) {
            throw new \Exception(get_string('error:token_request_failed', 'paygw_mpesakenya', $response));
        }
        
        $data = json_decode($response);
        
        if (empty($data->access_token)) {
            throw new \Exception(get_string('error:invalid_token_response', 'paygw_mpesakenya'));
        }
        
        // Cache the token, setting expiry to 50 minutes to be safe (tokens are valid for 1 hour)
        $this->accesstoken = $data->access_token;
        $this->tokenexpires = time() + (50 * 60);
        
        return $this->accesstoken;
    }
    
    /**
     * Initiate an STK push payment request.
     *
     * @param string $phonenumber The customer's phone number (format: 2547XXXXXXXX)
     * @param float $amount The amount to charge
     * @param string $accountreference An account reference
     * @param string $description A description of the transaction
     * @param string $callbackurl The callback URL
     * @return stdClass The API response
     * @throws \Exception If the request fails
     */
    public function stk_push(
        string $phonenumber,
        float $amount,
        string $accountreference,
        string $description,
        string $callbackurl
    ): stdClass {
        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
        
        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) $amount,
            'PartyA' => $phonenumber,
            'PartyB' => $this->shortcode,
            'PhoneNumber' => $phonenumber,
            'CallBackURL' => $callbackurl,
            'AccountReference' => $accountreference,
            'TransactionDesc' => $description
        ];
        
        $url = "{$this->baseurl}/mpesa/stkpush/v1/processrequest";
        $token = $this->get_access_token();
        
        $curl = new curl();
        $curl->setHeader([
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);
        
        $response = $curl->post($url, json_encode($payload));
        $httpcode = $curl->get_info()['http_code'];
        
        $result = json_decode($response);
        
        if ($httpcode !== 200 || !isset($result->ResponseCode) || $result->ResponseCode !== '0') {
            $errormessage = $result->errorMessage ?? ($result->errorMessage ?? 'Unknown error');
            throw new \Exception(get_string('error:stk_push_failed', 'paygw_mpesakenya', $errormessage));
        }
        
        return $result;
    }
    
    /**
     * Query the status of a transaction.
     *
     * @param string $checkoutrequestid The checkout request ID from the STK push response
     * @return stdClass The API response
     * @throws \Exception If the request fails
     */
    public function query_transaction_status(string $checkoutrequestid): stdClass {
        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
        
        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutrequestid
        ];
        
        $url = "{$this->baseurl}/mpesa/stkpushquery/v1/query";
        $token = $this->get_access_token();
        
        $curl = new curl();
        $curl->setHeader([
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);
        
        $response = $curl->post($url, json_encode($payload));
        $httpcode = $curl->get_info()['http_code'];
        
        $result = json_decode($response);
        
        if ($httpcode !== 200) {
            $errormessage = $result->errorMessage ?? ($result->errorMessage ?? 'Unknown error');
            throw new \Exception(get_string('error:query_failed', 'paygw_mpesakenya', $errormessage));
        }
        
        return $result;
    }
}
