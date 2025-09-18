/**
 * M-Pesa Kenya payment status checker
 *
 * @module     paygw_mpesakenya/status_checker
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/str', 'core/notification', 'core/ajax'], function($, str, notification, ajax) {
    
    // Private variables
    var SELECTORS = {
        PAYMENT_STATUS: '#payment-status',
        CHECK_STATUS_BUTTON: '#check-status'
    };
    
    var TIMEOUT = 30000; // 30 seconds
    var INTERVAL = 5000; // 5 seconds
    var timeoutId;
    var checkUrl;
    var successUrl;
    
    /**
     * Initialize the status checker
     * 
     * @param {Object} params The parameters
     * @param {Number} params.transactionid The transaction ID
     * @param {String} params.checkurl The URL to check the payment status
     * @param {String} params.successurl The URL to redirect to on success
     */
    var init = function(params) {
        checkUrl = params.checkurl;
        successUrl = params.successurl;
        
        // Start checking the payment status
        startChecking();
        
        // Handle manual status check
        $(SELECTORS.CHECK_STATUS_BUTTON).on('click', function() {
            checkStatus();
        });
    };
    
    /**
     * Start the automatic status checking
     */
    var startChecking = function() {
        // Clear any existing timeout
        if (timeoutId) {
            clearTimeout(timeoutId);
        }
        
        // Set a timeout to stop checking after a while
        timeoutId = setTimeout(function() {
            stopChecking();
            showTimeoutMessage();
        }, TIMEOUT);
        
        // Start the first check
        checkStatus();
    };
    
    /**
     * Stop the automatic status checking
     */
    var stopChecking = function() {
        if (timeoutId) {
            clearTimeout(timeoutId);
            timeoutId = null;
        }
    };
    
    /**
     * Check the payment status
     */
    var checkStatus = function() {
        // Show loading state
        $(SELECTORS.PAYMENT_STATUS)
            .removeClass('alert-info alert-success alert-danger')
            .addClass('alert-info')
            .html('<p>' + M.util.get_string('checking_status', 'paygw_mpesakenya') + '</p><div class="loading-spinner"></div>');
        
        // Make the AJAX request
        $.ajax({
            url: checkUrl,
            method: 'GET',
            dataType: 'json',
            cache: false
        })
        .done(function(response) {
            if (response.success) {
                // Payment successful
                handleSuccess(response);
            } else if (response.status === 'failed' || response.status === 'cancelled') {
                // Payment failed or was cancelled
                handleFailure(response);
            } else {
                // Still pending, check again after a delay
                setTimeout(checkStatus, INTERVAL);
            }
        })
        .fail(function() {
            // Error occurred, try again after a delay
            setTimeout(checkStatus, INTERVAL);
        });
    };
    
    /**
     * Handle a successful payment
     * 
     * @param {Object} response The response from the server
     */
    var handleSuccess = function(response) {
        stopChecking();
        
        // Update the status message
        $(SELECTORS.PAYMENT_STATUS)
            .removeClass('alert-info alert-danger')
            .addClass('alert-success')
            .html('<p>' + response.message + '</p>');
        
        // Disable the check status button
        $(SELECTORS.CHECK_STATUS_BUTTON).prop('disabled', true);
        
        // Redirect to the success page after a short delay
        setTimeout(function() {
            window.location.href = response.redirect || successUrl;
        }, 2000);
    };
    
    /**
     * Handle a failed payment
     * 
     * @param {Object} response The response from the server
     */
    var handleFailure = function(response) {
        stopChecking();
        
        // Update the status message
        $(SELECTORS.PAYMENT_STATUS)
            .removeClass('alert-info alert-success')
            .addClass('alert-danger')
            .html('<p>' + response.message + '</p>');
    };
    
    /**
     * Show a timeout message
     */
    var showTimeoutMessage = function() {
        str.get_string('payment_timeout', 'paygw_mpesakenya')
            .then(function(string) {
                $(SELECTORS.PAYMENT_STATUS)
                    .removeClass('alert-info')
                    .addClass('alert-warning')
                    .html('<p>' + string + '</p>');
                return string;
            })
            .catch(notification.exception);
    };
    
    // Public API
    return {
        init: init
    };
});
