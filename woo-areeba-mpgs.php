<?php
/**
 * Plugin Name: WooCommerce Areeba MPGS
 * Description: Extends WooCommerce with Areeba MasterCard Payment Gateway Services (MPGS).
 * Version: 1.0.0
 * Text Domain: areeba-mpgs
 * Domain Path: /languages
 * Author: Ali Basheer
 * Author URI: http://alibasheer.com
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/*
 *  Make sure WooCommerce is active
 */
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}