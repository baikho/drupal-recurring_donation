INTRODUCTION
------------

Simple 8.x version of the [PayPal Donations](https://www.drupal.org/project/paypal_donations) module.

- Provides a custom block, embedding a single AND/OR recurring form directing to PayPal's off-site donation page
- Easy configuration interface
- Input style choice of either radio buttons or a dropdown
- Custom amount option
- Recurring duration control
- Instant Payment Notification (IPN) support

REQUIREMENTS
------------

This module requires the following composer packages:

 * [PayPal Core SDK](https://github.com/paypal/sdk-core-php)

INSTALLATION
------------

Install as usual, see [Installing contributed modules](https://drupal.org/node/895232) for further information.

CONFIGURATION
-------------

1. Navigate to settings form through `Admin > Configuration > Web services > Recurring PayPal donations`

   or directly at path `/admin/config/services/recurring-paypal-donations`
