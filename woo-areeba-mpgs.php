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
 * Adds plugin page links
 *
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom link (i.e., "Configure")
 */
function wc_areeba_mpgs_gateway_plugin_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=areeba_mpgs' ) . '">' . __( 'Configure', 'areeba-mpgs' ) . '</a>'
	);
	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_areeba_mpgs_gateway_plugin_links' );

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

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {

			$this->id                 = 'areeba_mpgs';
			$this->icon               = apply_filters( 'wc_areeba_mpgs_icon', plugins_url( 'images/mastercard.png' , __FILE__ ) );
			$this->has_fields         = false;
			$this->method_title       = __( 'Areeba MPGS', 'areeba-mpgs' );
			$this->method_description = __( 'Allows Areeba MasterCard Payment Gateway Services (MPGS)', 'areeba-mpgs' );

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables
			$this->title           = $this->get_option( 'title' );
			$this->description     = $this->get_option( 'description' );
			$this->merchant_id     = $this->get_option( 'merchant_id' );
			$this->auth_pass       = $this->get_option( 'authentication_password' );
			$this->service_host    = $this->get_option( 'service_host' );
			$this->success_message = $this->get_option( 'thank_you_msg' );
			$this->failed_message  = $this->get_option( 'transaction_failed_msg' );

			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}

		/**
		 * Initialize Gateway Settings Form Fields
		 */
		public function init_form_fields() {

			$this->form_fields = apply_filters( 'wc_areeba_mpgs_form_fields', array(
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'areeba-mpgs' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable Areeba MPGS Payment Module.', 'areeba-mpgs' ),
					'default' => 'yes',
				),
				'title' => array(
					'title'       => __( 'Title', 'areeba-mpgs' ),
					'type'        => 'text',
					'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'areeba-mpgs' ),
					'default'     => __( 'Credit Card', 'areeba-mpgs' ),
					'desc_tip'    => true
				),
				'description' => array(
					'title'       => __( 'Description', 'areeba-mpgs' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'areeba-mpgs' ),
					'default'     => __( 'Pay securely by Credit/Debit Card.', 'areeba-mpgs' ),
					'desc_tip'    => true
				),
				'merchant_id' => array(
					'title'       => __( 'Merchant ID', 'areeba-mpgs' ),
					'type'        => 'text',
					'description' => __( 'Merchant ID, given by Areeba', 'areeba-mpgs' ),
					'placeholder' => __( 'Merchant ID', 'woocommerce' ),
					'desc_tip'    => true
				),
				'authentication_password' => array(
					'title'       => __( 'Authentication Password', 'areeba-mpgs' ),
					'type'        => 'text',
					'description' => __( 'Authentication Password, given by Areeba', 'areeba-mpgs' ),
					'placeholder' => __( 'Authentication Password', 'areeba-mpgs' ),
					'desc_tip'    => true
				),
				'service_host' => array(
					'title'       => __( 'MPGS URL', 'areeba-mpgs' ),
					'type'        => 'text',
					'css'         => 'width:100%',
					'description' => __( 'MPGS URL, given by Areeba. This is an example: https://ap-gateway.mastercard.com/', 'areeba-mpgs' ),
					'placeholder' => __( 'MPGS URL', 'areeba-mpgs' ),
					'default'     => __( 'https://ap-gateway.mastercard.com/', 'areeba-mpgs' ),
					'desc_tip'    => true
				),
				'mpgs_order_status' => array(
					'title'       => __( 'Order Status', 'gate_mpgs' ),
					'type'        => 'select',
					'description' => __( 'Set order status wen payment success.', 'areeba-mpgs' ),
					'options'     => array( '1' => 'Processing', '2' => 'Completed' ),
					'default'     => '1',
				),
				'thank_you_msg' => array(
					'title'       => __( 'Transaction Success Message', 'areeba-mpgs' ),
					'type'        => 'textarea',
					'description' => __( 'Put the message you want to display after a successfull transaction.', 'areeba-mpgs' ),
					'placeholder' => __( 'Transaction Success Message', 'areeba-mpgs' ),
					'default'     => __( 'Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.', 'areeba-mpgs' ),
					'desc_tip'    => true
				),
				'transaction_failed_msg' => array(
					'title'       => __( 'Transaction Failed Message', 'areeba-mpgs' ),
					'type'        => 'textarea',
					'description' => __( 'Put whatever message you want to display after a transaction failed.', 'areeba-mpgs' ),
					'placeholder' => __( 'Transaction Failed Message', 'areeba-mpgs' ),
					'default'     => __( 'Thank you for shopping with us. However, the transaction has been declined.', 'areeba-mpgs' ),
					'desc_tip'    => true
				)
			) );
		}

		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */
		public function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );

			// Prepare session request
			$session_request = array();
			$session_request['apiOperation']      = "CREATE_CHECKOUT_SESSION";
			$session_request['userId']            = $order->user_id;
			$session_request['order']['id']       = $order_id;
			$session_request['order']['amount']   = $order->order_total;
			$session_request['order']['currency'] = get_woocommerce_currency();

			$request_url = $this->service_host . "api/rest/version/49/merchant/" . $this->merchant_id . "/session";

			// Request the session
			$response_json = wp_remote_post( $request_url, array(
				'body'    => json_encode ( $session_request ),
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( "merchant." . $this->merchant_id . ":" . $this->auth_pass ),
				),
			) );

			$response = json_decode( $response_json['body'], true );

			if( $response['result'] == 'SUCCESS' && ! empty( $response['successIndicator'] ) ) {

				update_post_meta( $order_id,'areeba_mpgs_successIndicator', $response['successIndicator'] );
				update_post_meta( $order_id,'areeba_mpgs_sessionVersion', $response['session']['version'] );

				$pay_url = add_query_arg( array(
					'sessionId' => $response['session']['id'],
					'order'     => $order->get_id(),
					'key'       => $order->order_key,
				), wc_get_checkout_url() );

				return array(
					'result'   => 'success',
					'redirect' => $pay_url
				);

			} else {
				wc_add_notice( __( 'Payment error : ', 'areeba-mpgs' ) . $response['error']['explanation'], 'error' );
			}
		}
	}
}
add_action( 'plugins_loaded', 'wc_areeba_mpgs_init' );