=== WooCommerce MPGS ===
Contributors: alibasheer
Donate link: http://alibasheer.com
Tags: woocommerce, mastercard, mpgs, payment, gateway
Requires at least: 4.0
Tested up to: 5.7
Stable tag: 1.4.0
Requires PHP: 5.6
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

This plugin extends WooCommerce with MasterCard Payment Gateway Services (MPGS).

== Description ==

This plugin implement a Hosted Checkout integration of the MasterCard Payment Gateway Services (MPGS). It has 2 checkout options, either redirect the user to MPGS payment gateway page, or pay through a popup/lightbox on your website without redirection outside your website.

== Installation ==

1. Download the WooCommerce MPGS plugin zip file and extract to the /wp-content/plugins/ directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Go to WooCommerce backend settings page
1. Navigate to Payments tab
1. Then go to settings of MPGS gateway. Fill the form with credentials and save changes then you are ready.

== Frequently Asked Questions ==

= How to generate Authentication Password? =

First, login to merchant account (credentials given by the bank) and go to Admin -> Integration Settings. From there generate the Integration Authentication Password and use it in the plugin.

= What is MPGS URL and what is it's format? =

It is the URL that is needed to connect with the Mastercard gateway. This link should be given by your bank. Make sure to keep only the base link with only one slash at the end. It should be in this format: https://example.mastercard.com/

= What is the recommended API Version to use? =

We are supporting the latest version of the API, However, API version 49 is the most tested version with our plugin

= I am getting 'invalid request' error =

Make sure that your Merchant Account currency is the same as your website currency.

== Screenshots ==

1. WooCommerce MPGS setting page
2. Lightbox (popup) payment
3. Redirect to Payment page

== Changelog ==

= 1.4.0 =
* Added filter to allow customization on the session request
* Added transaction reference to support some special MID setups

= 1.3.0 =
* Support latest API version 55
* Allow admin orders even without customer info
* Translations support
* Access order properties through get functions instead of the deprecated direct access

= 1.2.0 =
* Fix bug with some American Express cards related to handling JSON response
* Allow admin to create orders for customers
* Remove transaction ID logging and keep only transaction receipt

= 1.1.0 =
* Multisite support
* Fix redirection after payment
* Enhanced error handling
* Enhanced payment verification checking

= 1.0.1 =
* Option to edit payment icon
* Add order notes on error for better debugging

= 1.0 =
* Initial release

== Upgrade Notice ==

= 1.4.0 =
Enhanced compatibility, check change log for more details. If you faced any issue, contact me at alibasheer@hotmail.com

= 1.3.0 =
Support to latest API version 55 and some other enhancements, check change log for more details. If you faced any issue, contact me at alibasheer@hotmail.com

= 1.2.0 =
This version comes with lots of enhancements, check change log for more details. If you faced any issue, contact me at alibasheer@hotmail.com

= 1.1.0 =
This version comes with lots of enhancement related to multisite, payment verification, error handling...

= 1.0.1 =
Added the option to edit payment icon.

= 1.0 =
Initial release