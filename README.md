# M-Pesa Kenya Payment Gateway for Moodle

This plugin provides integration with M-Pesa Kenya's payment system for Moodle, allowing your site to accept payments via M-Pesa's mobile money service.

## Features

- Seamless integration with M-Pesa's Daraja API
- Support for both sandbox and production environments
- Secure payment processing with transaction verification
- Support for all M-Pesa payment methods (STK Push, Paybill, Till Number)
- Comprehensive transaction logging
- Mobile-friendly payment interface
- Support for multiple currencies (KES, USD, etc.)

## Requirements

- Moodle 4.1 or later
- PHP 7.4 or later
- cURL extension enabled
- OpenSSL extension enabled
- M-Pesa Daraja API credentials (Consumer Key and Secret)
- Valid M-Pesa Paybill or Till Number

## Installation

1. Copy the `mpesakenya` folder to `moodle/payment/gateway/`
2. Log in to your Moodle site as an administrator
3. Go to Site administration > Notifications
4. Follow the on-screen instructions to complete the installation
5. Configure the plugin (see Configuration section below)

## Configuration

1. Go to Site administration > Plugins > Payment gateways > Manage payment gateways
2. Click on the eye icon to enable the M-Pesa Kenya payment gateway
3. Click on "Settings" to configure the gateway
4. Enter the following information:
   - **Consumer Key**: Your M-Pesa Daraja API Consumer Key
   - **Consumer Secret**: Your M-Pesa Daraja API Consumer Secret
   - **Shortcode**: Your M-Pesa Paybill or Till Number
   - **Passkey**: Your M-Pesa API Passkey
   - **Environment**: Select either Sandbox (for testing) or Production
   - **Callback URL**: Use the provided URL in your M-Pesa Daraja API settings

## Setting up M-Pesa Daraja API

1. Register for an account at [Safaricom Developer Portal](https://developer.safaricom.co.ke/)
2. Create a new app to get your Consumer Key and Secret
3. Request for STK Push permissions for your app
4. Whitelist your server's IP address in the Daraja portal
5. Configure the callback URL in the Daraja portal to point to:
   `https://your-moodle-site.com/payment/gateway/mpesakenya/callback.php`

## Testing

Before going live, test the payment gateway in sandbox mode:

1. Set the environment to "Sandbox" in the plugin settings
2. Use the test credentials provided by M-Pesa
3. Make a test payment using a test phone number
4. Verify that the payment is recorded in Moodle

## Going Live

1. Test all payment flows thoroughly in sandbox mode
2. Request to go live from the Safaricom Developer Portal
3. Update the plugin settings with your production credentials
4. Change the environment to "Production"
5. Test the payment gateway with a small amount before processing real payments

## Support

For support, please open an issue in the GitHub repository or contact the plugin maintainer.

## License

This plugin is licensed under the GNU General Public License v3.0. See the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please submit a pull request or open an issue to discuss your ideas.

## Security

If you discover any security issues, please report them to the maintainer directly instead of using the issue tracker.

## Changelog

### 1.0.0 (2024-09-18)
- Initial release
- Support for M-Pesa STK Push
- Sandbox and production environments
- Transaction logging
- Mobile-friendly interface
