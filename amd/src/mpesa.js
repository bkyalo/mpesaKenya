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
 * M-Pesa Kenya payment gateway JavaScript module
 *
 * @module     paygw_mpesakenya/mpesa
 * @copyright  2025 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/str', 'core/notification'], function($, ajax, str, notification) {
    return {
        /**
         * Initialize the M-Pesa payment form
         *
         * @param {string} formId The ID of the payment form
         * @param {object} config Configuration options
         */
        init: function(formId, config) {
            const form = document.getElementById(formId);
            if (!form) {
                return;
            }

            // Handle form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Get form data
                const formData = new FormData(form);
                const phoneNumber = formData.get('phonenumber');
                
                // Validate phone number
                if (!phoneNumber || !/^[0-9]{9,12}$/.test(phoneNumber)) {
                    notification.alert(
                        'Error',
                        'Please enter a valid M-Pesa phone number (9-12 digits)',
                        'OK'
                    );
                    return;
                }
                
                // Show loading indicator
                const submitButton = form.querySelector('button[type="submit"]');
                const originalText = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
                
                // Send payment request
                ajax.call([{
                    methodname: 'paygw_mpesakenya_initiate_payment',
                    args: {
                        component: config.component,
                        paymentarea: config.paymentarea,
                        itemid: config.itemid,
                        phonenumber: phoneNumber
                    },
                    done: function(response) {
                        // Redirect to payment status page
                        window.location.href = config.statusurl + '?id=' + response.paymentid + '&sesskey=' + config.sesskey;
                    },
                    fail: notification.exception
                }]);
            });
            
            // Format phone number input
            const phoneInput = form.querySelector('input[name="phonenumber"]');
            if (phoneInput) {
                phoneInput.addEventListener('input', function(e) {
                    // Remove any non-numeric characters
                    let value = e.target.value.replace(/\D/g, '');
                    
                    // Format as Kenyan phone number (7XX XXX XXX)
                    if (value.length > 3) {
                        value = value.replace(/^(\d{3})(\d{3})(\d{3}).*/, '$1 $2 $3');
                    } else if (value.length > 0) {
                        value = value.replace(/^(\d{0,3})/, '$1');
                    }
                    
                    e.target.value = value;
                });
            }
        },
        
        /**
         * Initialize the payment status checker
         * 
         * @param {string} containerId The ID of the status container
         * @param {object} config Configuration options
         */
        initStatusChecker: function(containerId, config) {
            const container = document.getElementById(containerId);
            if (!container) {
                return;
            }
            
            // Check payment status every 5 seconds
            const checkInterval = setInterval(function() {
                ajax.call([{
                    methodname: 'paygw_mpesakenya_check_payment_status',
                    args: {
                        paymentid: config.paymentid
                    },
                    done: function(response) {
                        if (response.completed) {
                            // Payment completed, stop checking
                            clearInterval(checkInterval);
                            
                            // Update UI
                            container.innerHTML = response.message;
                            
                            // Redirect if needed
                            if (response.redirect) {
                                setTimeout(function() {
                                    window.location.href = response.redirect;
                                }, 3000);
                            }
                        } else if (response.error) {
                            // Error occurred, stop checking
                            clearInterval(checkInterval);
                            container.innerHTML = '<div class="alert alert-danger">' + response.message + '</div>';
                        }
                        // If not completed and no error, the interval will check again
                    },
                    fail: function(error) {
                        clearInterval(checkInterval);
                        container.innerHTML = '<div class="alert alert-danger">An error occurred while checking payment status.</div>';
                        notification.exception(error);
                    }
                }]);
            }, 5000);
        }
    };
});
