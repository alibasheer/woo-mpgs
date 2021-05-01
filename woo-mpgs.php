<?php
/**
 * Plugin Name: WooCommerce MPGS
 * Description: Extends WooCommerce with MasterCard Payment Gateway Services (MPGS).
 * Version: 1.4.0
 * Text Domain: woo-mpgs
 * Domain Path: /languages
 * Author: Ali Basheer
 * Author URI: https://alibasheer.com
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Loading text domain
 */
function load_woo_mpgs_textdomain() {
	load_plugin_textdomain( 'woo-mpgs', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'load_woo_mpgs_textdomain' );

/**
 * Adds plugin page links
 *
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom link (i.e., "Configure")
 */
function woo_mpgs_gateway_plugin_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=woo_mpgs' ) . '">' . __( 'Configure', 'woo-mpgs' ) . '</a>'
	);
	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'woo_mpgs_gateway_plugin_links' );

/**
 * Add the gateway to WC Available Gateways
 *
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + Woo MPGS gateway
 */
function woo_mpgs_add_to_gateways( $gateways ) {
	$gateways[] = 'WOO_MPGS';
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'woo_mpgs_add_to_gateways' );

/**
 * WooCommerce MPGS
 *
 * Extends WooCommerce with MasterCard Payment Gateway Services (MPGS).
 *
 * @class 		WOO_MPGS
 * @extends		WC_Payment_Gateway
 * @version		1.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		Ali Basheer
 */
function woo_mpgs_init() {

	/**
	 * Make sure WooCommerce is active
	 */
	if ( ! class_exists( 'WooCommerce' ) ) {
		echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'MPGS requires WooCommerce to be installed and active. You can download %s here.', 'woo-mpgs' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
		return;
	}

	class WOO_MPGS extends WC_Payment_Gateway {

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {

			$this->id                   = 'woo_mpgs';
			$this->mpgs_icon            = $this->get_option( 'mpgs_icon' );
			$this->icon                 = ( ! empty( $this->mpgs_icon ) ) ? $this->mpgs_icon : apply_filters( 'woo_mpgs_icon', plugins_url( 'assets/images/mastercard.png' , __FILE__ ) );
			$this->has_fields           = false;
			$this->method_title         = __( 'MPGS', 'woo-mpgs' );
			$this->method_description   = __( 'Allows MasterCard Payment Gateway Services (MPGS)', 'woo-mpgs' );

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables
			$this->title                = $this->get_option( 'title' );
			$this->description          = $this->get_option( 'description' );
			$this->service_host         = $this->get_option( 'service_host' );
			$this->api_version          = $this->get_option( 'api_version' );
			$this->merchant_id          = $this->get_option( 'merchant_id' );
			$this->auth_pass            = $this->get_option( 'authentication_password' );
			$this->merchant_name        = $this->get_option( 'merchant_name' );
			$this->merchant_address1    = $this->get_option( 'merchant_address1' );
			$this->merchant_address2    = $this->get_option( 'merchant_address2' );
			$this->checkout_interaction = $this->get_option( 'checkout_interaction' );

			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_receipt_woo_mpgs', array( $this, 'receipt_page' ) );
			add_action( 'woocommerce_api_woo_mpgs', array( $this, 'process_response' ) );
			add_action( 'wp_head', array( $this, 'add_checkout_script' ) );
		}

		/**
		 * Initialize Gateway Settings Form Fields
		 */
		public function init_form_fields() {

			$this->form_fields = apply_filters( 'woo_mpgs_form_fields', array(
				'enabled' => array(
					'title'         => __( 'Enable/Disable', 'woo-mpgs' ),
					'type'          => 'checkbox',
					'label'         => __( 'Enable MPGS Payment Module.', 'woo-mpgs' ),
					'default'       => 'yes',
				),
				'title' => array(
					'title'         => __( 'Title', 'woo-mpgs' ),
					'type'          => 'text',
					'description'   => __( 'This controls the title for the payment method the customer sees during checkout.', 'woo-mpgs' ),
					'default'       => __( 'Credit Card', 'woo-mpgs' ),
					'desc_tip'      => true
				),
				'description' => array(
					'title'         => __( 'Description', 'woo-mpgs' ),
					'type'          => 'textarea',
					'description'   => __( 'Payment method description that the customer will see on your checkout.', 'woo-mpgs' ),
					'default'       => __( 'Pay securely by Credit/Debit Card.', 'woo-mpgs' ),
					'desc_tip'      => true
				),
				'mpgs_icon' => array(
					'title'         => __( 'Icon', 'woo-mpgs' ),
					'type'          => 'text',
					'css'           => 'width:100%',
					'description'   => __( 'Enter an image URL to change the icon.', 'woo-mpgs' ),
					'desc_tip'      => true
				),
				'service_host' => array(
					'title'         => __( 'MPGS URL', 'woo-mpgs' ),
					'type'          => 'text',
					'css'           => 'width:100%',
					'description'   => __( 'MPGS URL, given by the Bank. This is an example: https://ap-gateway.mastercard.com/', 'woo-mpgs' ),
					'placeholder'   => __( 'MPGS URL', 'woo-mpgs' ),
					'default'       => __( 'https://ap-gateway.mastercard.com/', 'woo-mpgs' ),
					'desc_tip'      => true
				),
				'api_version' => array(
					'title'         => __( 'API Version', 'woo-mpgs' ),
					'type'          => 'text',
					'description'   => __( 'API version, given by the Bank', 'woo-mpgs' ),
					'placeholder'   => __( 'MPGS API Version (49 is recommended)', 'woo-mpgs' ),
					'default'       => 49,
					'desc_tip'      => true
				),
				'merchant_id' => array(
					'title'         => __( 'Merchant ID', 'woo-mpgs' ),
					'type'          => 'text',
					'description'   => __( 'Merchant ID, given by the Bank', 'woo-mpgs' ),
					'placeholder'   => __( 'Merchant ID', 'woocommerce' ),
					'desc_tip'      => true
				),
				'authentication_password' => array(
					'title'         => __( 'Authentication Password', 'woo-mpgs' ),
					'type'          => 'text',
					'description'   => __( 'Authentication Password, given by the Bank', 'woo-mpgs' ),
					'placeholder'   => __( 'Authentication Password', 'woo-mpgs' ),
					'desc_tip'      => true
				),
				'merchant_name' => array(
					'title'         => __( 'Name', 'woo-mpgs' ),
					'type'          => 'text',
					'description'   => __( 'Merchant name that will appear in the gateway page or popup', 'woo-mpgs' ),
					'desc_tip'      => true
				),
				'merchant_address1' => array(
					'title'         => __( 'Merchant Address Line 1', 'woo-mpgs' ),
					'type'          => 'text',
					'description'   => __( 'Merchant Address Line 1 that will appear in the gateway page or popup', 'woo-mpgs' ),
					'desc_tip'      => true
				),
				'merchant_address2' => array(
					'title'         => __( 'Merchant Address Line 2', 'woo-mpgs' ),
					'type'          => 'text',
					'description'   => __( 'Merchant Address Line 2 that will appear in the gateway page or popup', 'woo-mpgs' ),
					'desc_tip'      => true
				),
				'checkout_interaction' => array(
					'title'         => __( 'Checkout Interaction', 'woo-mpgs' ),
					'type'          => 'select',
					'description'   => __( 'Choose checkout interaction type', 'woo-mpgs' ),
					'options'       => array( 'lightbox' => 'Lightbox', 'paymentpage' => 'Payment Page' ),
					'default'       => '1',
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
			$session_request['apiOperation']                = "CREATE_CHECKOUT_SESSION";
			$session_request['userId']                      = $order->get_user_id();
			$session_request['order']['id']                 = $order_id;
			$session_request['order']['amount']             = $order->get_total();
			$session_request['order']['currency']           = get_woocommerce_currency();
			$session_request['interaction']['returnUrl']    = add_query_arg( array( 'order_id' => $order_id, 'wc-api' => 'woo_mpgs' ), home_url('/') );
			if( (int) $this->api_version >= 52 ) {
				$session_request['interaction']['operation']    = "PURCHASE";
            }

			/**
			 * Filters the session request.
			 *
			 * @since 1.3.1
			 *
			 * @param array   $session_request The array that will be sent with the request.
			 * @param WC_ORDER $order  Order object.
			 */
			$session_request = apply_filters( 'woo_mpgs_session_request', $session_request, $order );

			$request_url = $this->service_host . "api/rest/version/" . $this->api_version . "/merchant/" . $this->merchant_id . "/session";

			// Request the session
			$response_json = wp_remote_post( $request_url, array(
				'body'	  => json_encode ( $session_request ),
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( "merchant." . $this->merchant_id . ":" . $this->auth_pass ),
				),
			) );

			if ( is_wp_error( $response_json ) ) {

				wc_add_notice( __( 'Payment error: Failed to communicate with MPGS server. Make sure MPGS URL looks like `https://example.mastercard.com/` by removing `checkout/version/*/checkout.js` and end the URL with a slash "/".', 'woo-mpgs' ), 'error' );

				return array(
					'result'	=> 'fail',
					'redirect'	=> '',
				);
			}

			$response = json_decode( $response_json['body'], true );

			if( $response['result'] == 'SUCCESS' && ! empty( $response['successIndicator'] ) ) {

				update_post_meta( $order_id,'woo_mpgs_successIndicator', $response['successIndicator'] );
				update_post_meta( $order_id,'woo_mpgs_sessionVersion', $response['session']['version'] );

				$pay_url = add_query_arg( array(
					'sessionId'     => $response['session']['id'],
					'key'           => $order->get_order_key(),
					'pay_for_order' => false,
				), $order->get_checkout_payment_url() );

				return array(
					'result'	=> 'success',
					'redirect'	=> $pay_url
				);

			} else {
				wc_add_notice( __( 'Payment error: ', 'woo-mpgs' ) . $response['error']['explanation'], 'error' );
			}
		}

		/**
		 * Print payment buttons in the receipt page
		 *
		 * @param int $order_id
		 */
		public function receipt_page( $order_id ) {

			if( ! empty( $_REQUEST['sessionId'] ) ) {

				$order = wc_get_order( $order_id );
				?>
				<script type="text/javascript">
					function errorCallback( error ) {
						alert( "Error: " + JSON.stringify( error ) );
						window.location.href = "<?php echo wc_get_checkout_url(); ?>";
					}
					Checkout.configure({
						merchant: "<?php echo $this->merchant_id; ?>",
						order: {
							id: "<?php echo $order_id; ?>",
							amount: "<?php echo $order->get_total(); ?>",
							currency: "<?php echo get_woocommerce_currency(); ?>",
							description: "<?php printf( __( 'Pay for order #%d via %s', 'woo-mpgs' ), $order_id, $this->title ); ?>",
							customerOrderDate: "<?php echo date('Y-m-d'); ?>",
							customerReference: "<?php echo $order->get_user_id(); ?>",
							reference: "<?php echo $order_id; ?>"
						},
						session: {
							id: "<?php echo esc_js( $_REQUEST['sessionId'] ); ?>"
						},
                        transaction: {
                            reference: "TRF" + "<?php echo $order_id; ?>"
                        },
						billing: {
							address: {
								city: "<?php echo $order->get_billing_city(); ?>",
								country: "<?php echo $this->kia_convert_country_code( $order->get_billing_country() ); ?>",
								postcodeZip: "<?php echo $order->get_billing_postcode(); ?>",
								stateProvince: "<?php echo $order->get_billing_state(); ?>",
								street: "<?php echo $order->get_billing_address_1(); ?>",
								street2: "<?php echo $order->get_billing_address_2(); ?>"
							}
						},
						<?php if( ! empty( $order->get_billing_email() ) && ! empty( $order->get_billing_first_name() ) && ! empty( $order->get_billing_last_name() ) && ! empty( $order->get_billing_phone() ) ) { ?>
						customer: {
							email: "<?php echo $order->get_billing_email(); ?>",
							firstName: "<?php echo $order->get_billing_first_name(); ?>",
							lastName: "<?php echo $order->get_billing_last_name(); ?>",
							phone: "<?php echo $order->get_billing_phone(); ?>"
						},
						<?php } ?>
						interaction: {
						    <?php if( (int) $this->api_version >= 52 ) { ?>
                            operation: "PURCHASE",
                            <?php } ?>
                            merchant: {
								name: "<?php echo ( ! empty( $this->merchant_name ) ) ? $this->merchant_name : 'MPGS'; ?>",
								address: {
									line1: "<?php echo $this->merchant_address1; ?>",
									line2: "<?php echo $this->merchant_address2; ?>"
								}
							},
							displayControl: {
								billingAddress  : "HIDE",
								customerEmail   : "HIDE",
								orderSummary    : "HIDE",
								shipping        : "HIDE"
							}
						}
					});
				</script>
				<p class="loading-payment-text"><?php echo __( 'Loading payment method, please wait. This may take up to 30 seconds.', 'woo-mpgs' ); ?></p>
				<script type="text/javascript">
					<?php echo ( $this->checkout_interaction === 'paymentpage' ) ? 'Checkout.showPaymentPage();' : 'Checkout.showLightbox();';?>
				</script>
				<?php
			} else {
				wc_add_notice( __( 'Payment error: Session not found.', 'woo-mpgs' ), 'error' );
				wp_redirect( wc_get_checkout_url() );
				exit;
			}
		}

		/**
		 * Handle MPGS response
		 */
		public function process_response () {

			global $woocommerce;
			$order_id = $_REQUEST['order_id'];
			$order = wc_get_order( $order_id );
			$resultIndicator = $_REQUEST['resultIndicator'];
			$mpgs_successIndicator = get_post_meta( $order_id, "woo_mpgs_successIndicator", true );

			if( $resultIndicator == $mpgs_successIndicator ) {

				$request_url = $this->service_host . "api/rest/version/" . $this->api_version . "/merchant/" . $this->merchant_id . "/order/" . $order_id;

				// Request the order payment details
				$response_json = wp_remote_get( $request_url, array(
					'headers' => array(
						'Authorization' => 'Basic ' . base64_encode( "merchant." . $this->merchant_id . ":" . $this->auth_pass ),
					),
				) );

				$response = json_decode( utf8_decode( $response_json['body'] ), true );
				$transaction_index = count( $response['transaction'] ) - 1;
				$transaction_result = $response['transaction'][$transaction_index]['result'];
				$transaction_receipt = $response['transaction'][$transaction_index]['transaction']['receipt'];

				if( $transaction_result == "SUCCESS" && ! empty( $transaction_receipt ) ) {
					$woocommerce->cart->empty_cart();
					$order->add_order_note( sprintf( __( 'MPGS Payment completed with Transaction Receipt: %s.', 'woo-mpgs' ), $transaction_receipt ) );
					$order->payment_complete( $transaction_receipt );

					wp_redirect( $this->get_return_url( $order ) );
					exit;
				} else {
					$order->add_order_note( __('Payment error: Something went wrong.', 'woo-mpgs') );
					wc_add_notice( __('Payment error: Something went wrong.', 'woo-mpgs'), 'error' );
				}

			} else {
				$order->add_order_note( __('Payment error: Invalid transaction.', 'woo-mpgs') );
				wc_add_notice( __('Payment error: Invalid transaction.', 'woo-mpgs'), 'error' );
			}
			// reaching this line means there is an error, redirect back to checkout page
			wp_redirect( wc_get_checkout_url() );
			exit;
		}

		/**
		 * load checkout script
		 */
		public function add_checkout_script() {
			if ( ! empty( $_REQUEST['sessionId'] ) ) {
			    ?>
                <script
                        src="<?php echo $this->service_host; ?>checkout/version/<?php echo $this->api_version; ?>/checkout.js"
                        data-error="errorCallback"
                        data-cancel="<?php echo wc_get_checkout_url(); ?>"
                >
                </script>
			    <?php
			}
		}

		/**
		 * Converts the WooCommerce country codes to 3-letter ISO codes
		 * https://en.wikipedia.org/wiki/ISO_3166-1_alpha-3
		 * @param string WooCommerce's 2 letter country code
		 * @return string ISO 3-letter country code
		 */
		function kia_convert_country_code( $country ) {
			$countries = array(
				'AF' => 'AFG', //Afghanistan
				'AX' => 'ALA', //&#197;land Islands
				'AL' => 'ALB', //Albania
				'DZ' => 'DZA', //Algeria
				'AS' => 'ASM', //American Samoa
				'AD' => 'AND', //Andorra
				'AO' => 'AGO', //Angola
				'AI' => 'AIA', //Anguilla
				'AQ' => 'ATA', //Antarctica
				'AG' => 'ATG', //Antigua and Barbuda
				'AR' => 'ARG', //Argentina
				'AM' => 'ARM', //Armenia
				'AW' => 'ABW', //Aruba
				'AU' => 'AUS', //Australia
				'AT' => 'AUT', //Austria
				'AZ' => 'AZE', //Azerbaijan
				'BS' => 'BHS', //Bahamas
				'BH' => 'BHR', //Bahrain
				'BD' => 'BGD', //Bangladesh
				'BB' => 'BRB', //Barbados
				'BY' => 'BLR', //Belarus
				'BE' => 'BEL', //Belgium
				'BZ' => 'BLZ', //Belize
				'BJ' => 'BEN', //Benin
				'BM' => 'BMU', //Bermuda
				'BT' => 'BTN', //Bhutan
				'BO' => 'BOL', //Bolivia
				'BQ' => 'BES', //Bonaire, Saint Estatius and Saba
				'BA' => 'BIH', //Bosnia and Herzegovina
				'BW' => 'BWA', //Botswana
				'BV' => 'BVT', //Bouvet Islands
				'BR' => 'BRA', //Brazil
				'IO' => 'IOT', //British Indian Ocean Territory
				'BN' => 'BRN', //Brunei
				'BG' => 'BGR', //Bulgaria
				'BF' => 'BFA', //Burkina Faso
				'BI' => 'BDI', //Burundi
				'KH' => 'KHM', //Cambodia
				'CM' => 'CMR', //Cameroon
				'CA' => 'CAN', //Canada
				'CV' => 'CPV', //Cape Verde
				'KY' => 'CYM', //Cayman Islands
				'CF' => 'CAF', //Central African Republic
				'TD' => 'TCD', //Chad
				'CL' => 'CHL', //Chile
				'CN' => 'CHN', //China
				'CX' => 'CXR', //Christmas Island
				'CC' => 'CCK', //Cocos (Keeling) Islands
				'CO' => 'COL', //Colombia
				'KM' => 'COM', //Comoros
				'CG' => 'COG', //Congo
				'CD' => 'COD', //Congo, Democratic Republic of the
				'CK' => 'COK', //Cook Islands
				'CR' => 'CRI', //Costa Rica
				'CI' => 'CIV', //Côte d\'Ivoire
				'HR' => 'HRV', //Croatia
				'CU' => 'CUB', //Cuba
				'CW' => 'CUW', //Curaçao
				'CY' => 'CYP', //Cyprus
				'CZ' => 'CZE', //Czech Republic
				'DK' => 'DNK', //Denmark
				'DJ' => 'DJI', //Djibouti
				'DM' => 'DMA', //Dominica
				'DO' => 'DOM', //Dominican Republic
				'EC' => 'ECU', //Ecuador
				'EG' => 'EGY', //Egypt
				'SV' => 'SLV', //El Salvador
				'GQ' => 'GNQ', //Equatorial Guinea
				'ER' => 'ERI', //Eritrea
				'EE' => 'EST', //Estonia
				'ET' => 'ETH', //Ethiopia
				'FK' => 'FLK', //Falkland Islands
				'FO' => 'FRO', //Faroe Islands
				'FJ' => 'FIJ', //Fiji
				'FI' => 'FIN', //Finland
				'FR' => 'FRA', //France
				'GF' => 'GUF', //French Guiana
				'PF' => 'PYF', //French Polynesia
				'TF' => 'ATF', //French Southern Territories
				'GA' => 'GAB', //Gabon
				'GM' => 'GMB', //Gambia
				'GE' => 'GEO', //Georgia
				'DE' => 'DEU', //Germany
				'GH' => 'GHA', //Ghana
				'GI' => 'GIB', //Gibraltar
				'GR' => 'GRC', //Greece
				'GL' => 'GRL', //Greenland
				'GD' => 'GRD', //Grenada
				'GP' => 'GLP', //Guadeloupe
				'GU' => 'GUM', //Guam
				'GT' => 'GTM', //Guatemala
				'GG' => 'GGY', //Guernsey
				'GN' => 'GIN', //Guinea
				'GW' => 'GNB', //Guinea-Bissau
				'GY' => 'GUY', //Guyana
				'HT' => 'HTI', //Haiti
				'HM' => 'HMD', //Heard Island and McDonald Islands
				'VA' => 'VAT', //Holy See (Vatican City State)
				'HN' => 'HND', //Honduras
				'HK' => 'HKG', //Hong Kong
				'HU' => 'HUN', //Hungary
				'IS' => 'ISL', //Iceland
				'IN' => 'IND', //India
				'ID' => 'IDN', //Indonesia
				'IR' => 'IRN', //Iran
				'IQ' => 'IRQ', //Iraq
				'IE' => 'IRL', //Republic of Ireland
				'IM' => 'IMN', //Isle of Man
				'IL' => 'ISR', //Israel
				'IT' => 'ITA', //Italy
				'JM' => 'JAM', //Jamaica
				'JP' => 'JPN', //Japan
				'JE' => 'JEY', //Jersey
				'JO' => 'JOR', //Jordan
				'KZ' => 'KAZ', //Kazakhstan
				'KE' => 'KEN', //Kenya
				'KI' => 'KIR', //Kiribati
				'KP' => 'PRK', //Korea, Democratic People\'s Republic of
				'KR' => 'KOR', //Korea, Republic of (South)
				'KW' => 'KWT', //Kuwait
				'KG' => 'KGZ', //Kyrgyzstan
				'LA' => 'LAO', //Laos
				'LV' => 'LVA', //Latvia
				'LB' => 'LBN', //Lebanon
				'LS' => 'LSO', //Lesotho
				'LR' => 'LBR', //Liberia
				'LY' => 'LBY', //Libya
				'LI' => 'LIE', //Liechtenstein
				'LT' => 'LTU', //Lithuania
				'LU' => 'LUX', //Luxembourg
				'MO' => 'MAC', //Macao S.A.R., China
				'MK' => 'MKD', //Macedonia
				'MG' => 'MDG', //Madagascar
				'MW' => 'MWI', //Malawi
				'MY' => 'MYS', //Malaysia
				'MV' => 'MDV', //Maldives
				'ML' => 'MLI', //Mali
				'MT' => 'MLT', //Malta
				'MH' => 'MHL', //Marshall Islands
				'MQ' => 'MTQ', //Martinique
				'MR' => 'MRT', //Mauritania
				'MU' => 'MUS', //Mauritius
				'YT' => 'MYT', //Mayotte
				'MX' => 'MEX', //Mexico
				'FM' => 'FSM', //Micronesia
				'MD' => 'MDA', //Moldova
				'MC' => 'MCO', //Monaco
				'MN' => 'MNG', //Mongolia
				'ME' => 'MNE', //Montenegro
				'MS' => 'MSR', //Montserrat
				'MA' => 'MAR', //Morocco
				'MZ' => 'MOZ', //Mozambique
				'MM' => 'MMR', //Myanmar
				'NA' => 'NAM', //Namibia
				'NR' => 'NRU', //Nauru
				'NP' => 'NPL', //Nepal
				'NL' => 'NLD', //Netherlands
				'AN' => 'ANT', //Netherlands Antilles
				'NC' => 'NCL', //New Caledonia
				'NZ' => 'NZL', //New Zealand
				'NI' => 'NIC', //Nicaragua
				'NE' => 'NER', //Niger
				'NG' => 'NGA', //Nigeria
				'NU' => 'NIU', //Niue
				'NF' => 'NFK', //Norfolk Island
				'MP' => 'MNP', //Northern Mariana Islands
				'NO' => 'NOR', //Norway
				'OM' => 'OMN', //Oman
				'PK' => 'PAK', //Pakistan
				'PW' => 'PLW', //Palau
				'PS' => 'PSE', //Palestinian Territory
				'PA' => 'PAN', //Panama
				'PG' => 'PNG', //Papua New Guinea
				'PY' => 'PRY', //Paraguay
				'PE' => 'PER', //Peru
				'PH' => 'PHL', //Philippines
				'PN' => 'PCN', //Pitcairn
				'PL' => 'POL', //Poland
				'PT' => 'PRT', //Portugal
				'PR' => 'PRI', //Puerto Rico
				'QA' => 'QAT', //Qatar
				'RE' => 'REU', //Reunion
				'RO' => 'ROU', //Romania
				'RU' => 'RUS', //Russia
				'RW' => 'RWA', //Rwanda
				'BL' => 'BLM', //Saint Barth&eacute;lemy
				'SH' => 'SHN', //Saint Helena
				'KN' => 'KNA', //Saint Kitts and Nevis
				'LC' => 'LCA', //Saint Lucia
				'MF' => 'MAF', //Saint Martin (French part)
				'SX' => 'SXM', //Sint Maarten / Saint Matin (Dutch part)
				'PM' => 'SPM', //Saint Pierre and Miquelon
				'VC' => 'VCT', //Saint Vincent and the Grenadines
				'WS' => 'WSM', //Samoa
				'SM' => 'SMR', //San Marino
				'ST' => 'STP', //S&atilde;o Tom&eacute; and Pr&iacute;ncipe
				'SA' => 'SAU', //Saudi Arabia
				'SN' => 'SEN', //Senegal
				'RS' => 'SRB', //Serbia
				'SC' => 'SYC', //Seychelles
				'SL' => 'SLE', //Sierra Leone
				'SG' => 'SGP', //Singapore
				'SK' => 'SVK', //Slovakia
				'SI' => 'SVN', //Slovenia
				'SB' => 'SLB', //Solomon Islands
				'SO' => 'SOM', //Somalia
				'ZA' => 'ZAF', //South Africa
				'GS' => 'SGS', //South Georgia/Sandwich Islands
				'SS' => 'SSD', //South Sudan
				'ES' => 'ESP', //Spain
				'LK' => 'LKA', //Sri Lanka
				'SD' => 'SDN', //Sudan
				'SR' => 'SUR', //Suriname
				'SJ' => 'SJM', //Svalbard and Jan Mayen
				'SZ' => 'SWZ', //Swaziland
				'SE' => 'SWE', //Sweden
				'CH' => 'CHE', //Switzerland
				'SY' => 'SYR', //Syria
				'TW' => 'TWN', //Taiwan
				'TJ' => 'TJK', //Tajikistan
				'TZ' => 'TZA', //Tanzania
				'TH' => 'THA', //Thailand
				'TL' => 'TLS', //Timor-Leste
				'TG' => 'TGO', //Togo
				'TK' => 'TKL', //Tokelau
				'TO' => 'TON', //Tonga
				'TT' => 'TTO', //Trinidad and Tobago
				'TN' => 'TUN', //Tunisia
				'TR' => 'TUR', //Turkey
				'TM' => 'TKM', //Turkmenistan
				'TC' => 'TCA', //Turks and Caicos Islands
				'TV' => 'TUV', //Tuvalu
				'UG' => 'UGA', //Uganda
				'UA' => 'UKR', //Ukraine
				'AE' => 'ARE', //United Arab Emirates
				'GB' => 'GBR', //United Kingdom
				'US' => 'USA', //United States
				'UM' => 'UMI', //United States Minor Outlying Islands
				'UY' => 'URY', //Uruguay
				'UZ' => 'UZB', //Uzbekistan
				'VU' => 'VUT', //Vanuatu
				'VE' => 'VEN', //Venezuela
				'VN' => 'VNM', //Vietnam
				'VG' => 'VGB', //Virgin Islands, British
				'VI' => 'VIR', //Virgin Island, U.S.
				'WF' => 'WLF', //Wallis and Futuna
				'EH' => 'ESH', //Western Sahara
				'YE' => 'YEM', //Yemen
				'ZM' => 'ZMB', //Zambia
				'ZW' => 'ZWE', //Zimbabwe

			);

			$iso_code = isset( $countries[$country] ) ? $countries[$country] : $country;
			return $iso_code;

		}
	}
}
add_action( 'plugins_loaded', 'woo_mpgs_init' );
