=== WooCommerce MPGS ===
Contributors: alibasheer
Donate link: http://alibasheer.com
Tags: woocommerce, mastercard, mpgs, payment, gateway
Requires at least: 4.0
Tested up to: 5.2
Stable tag: 1.2.0
Requires PHP: 5.6
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

This plugin extends WooCommerce with MasterCard Payment Gateway Services (MPGS).

== Description ==

This plugin implement a Hosted Checkout integration, the user either redirected to MPGS payment gateway page, or pay through a popup/lightbox.

== Installation ==

1. Download the WooCommerce MPGS plugin zip file and extract to the /wp-content/plugins/ directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Go to WooCommerce backend settings page
1. Navigate to Checkout tab
1. Then go to settings of MPGS gateway. Fill the form with credentials and save changes then you are ready.

== Frequently Asked Questions ==

= Does it support WooCommerce? =

Yes, it is a WooCommerce add-on.

= Do I need to install WooCommerce plugin? =

Yes, you should install WooCommerce plugin first.

== Screenshots ==

1. WooCommerce MPGS setting page

== Changelog ==

= 1.2.0 =
* Support MPGS API 52
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

= 1.2.0 =
This version comes with lots of enhancement related to MPGS API 52, supporting creating orders by admin to customers

= 1.1.0 =
This version comes with lots of enhancement related to multisite, payment verification, error handling...

= 1.0.1 =
Added the option to edit payment icon.

= 1.0 =
Initial release