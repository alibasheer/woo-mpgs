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

/**
 * Add the gateway to WC Available Gateways
 *
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + Areeba MPGS gateway
 */
function wc_areeba_mpgs_add_to_gateways( $gateways ) {
	$gateways[] = 'WC_Areeba_MPGS';
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'wc_areeba_mpgs_add_to_gateways' );

/**
 * WooCommerce Areeba MPGS
 *
 * Extends WooCommerce with Areeba MasterCard Payment Gateway Services (MPGS).
 *
 * @class 		WC_Areeba_MPGS
 * @extends		WC_Payment_Gateway
 * @version		1.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		Ali Basheer
 */
function wc_areeba_mpgs_init() {
	class WC_Areeba_MPGS extends WC_Payment_Gateway {

	}
}
add_action( 'plugins_loaded', 'wc_areeba_mpgs_init', 11 );