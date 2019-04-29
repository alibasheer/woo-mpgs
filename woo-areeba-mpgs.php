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
			$this->title                = $this->get_option( 'title' );
			$this->description          = $this->get_option( 'description' );
			$this->merchant_id          = $this->get_option( 'merchant_id' );
			$this->auth_pass            = $this->get_option( 'authentication_password' );
			$this->service_host         = $this->get_option( 'service_host' );
			$this->merchant_name        = $this->get_option( 'merchant_name' );
			$this->merchant_address1    = $this->get_option( 'merchant_address1' );
			$this->merchant_address2    = $this->get_option( 'merchant_address2' );
			$this->checkout_interaction = $this->get_option( 'checkout_interaction' );
			$this->success_message      = $this->get_option( 'thank_you_msg' );
			$this->failed_message       = $this->get_option( 'transaction_failed_msg' );

			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_receipt_areeba_mpgs', array( $this, 'receipt_page' ) );
			add_action( 'woocommerce_api_wc_areeba_mpgs', array( $this, 'process_response' ) );
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
				'merchant_name' => array(
					'title'       => __( 'Name', 'areeba-mpgs' ),
					'type'        => 'text',
					'description' => __( 'Merchant name that will appear in the gateway page or popup', 'areeba-mpgs' ),
					'desc_tip'    => true
				),
				'merchant_address1' => array(
					'title'       => __( 'Merchant Address Line 1', 'areeba-mpgs' ),
					'type'        => 'text',
					'description' => __( 'Merchant Address Line 1 that will appear in the gateway page or popup', 'areeba-mpgs' ),
					'desc_tip'    => true
				),
				'merchant_address2' => array(
					'title'       => __( 'Merchant Address Line 2', 'areeba-mpgs' ),
					'type'        => 'text',
					'description' => __( 'Merchant Address Line 2 that will appear in the gateway page or popup', 'areeba-mpgs' ),
					'desc_tip'    => true
				),
				'checkout_interaction' => array(
					'title'       => __( 'Checkout Interaction', 'areeba-mpgs' ),
					'type'        => 'select',
					'description' => __( 'Choose checkout interaction type', 'areeba-mpgs' ),
					'options'     => array( 'lightbox' => 'Lightbox', 'paymentpage' => 'Payment Page' ),
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
				wc_add_notice( __( 'Payment error: ', 'areeba-mpgs' ) . $response['error']['explanation'], 'error' );
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
				<p><?php echo __( 'Thank you for your order, please click the button below to proceed with your payment.', 'areeba-mpgs' ); ?></p>
				<a class="button alt" id="mpgs_payment_button" onclick="<?php echo ( $this->checkout_interaction === 'paymentpage' ) ? 'Checkout.showPaymentPage();' : 'Checkout.showLightbox();'; ?>" ><?php echo __( 'Pay with ', 'areeba-mpgs' ) . $this->title; ?></a>
				<a class="button cancel" href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><?php echo __( 'Cancel order &amp; restore cart', 'areeba-mpgs' ); ?></a>
				<script
					src="<?php echo $this->service_host; ?>checkout/version/49/checkout.js"
					data-error="errorCallback"
					data-cancel="<?php echo wc_get_checkout_url(); ?>"
					data-complete="<?php echo add_query_arg( array( 'order_id' => $order_id, 'wc-api' => 'wc_areeba_mpgs' ), home_url('/') ) ?>">
				</script>
				<script type="text/javascript">

                    function errorCallback( error ) {
                        alert( "Error: " + JSON.stringify( error ) );
                        window.location.href = "<?php echo wc_get_checkout_url(); ?>";
                    }

                    Checkout.configure({
                        merchant: "<?php echo $this->merchant_id; ?>",
                        order: {
                            id: "<?php echo $order_id; ?>",
                            amount: '<?php echo $order->get_total(); ?>',
                            currency: "<?php echo get_woocommerce_currency(); ?>",
                            description: "<?php printf( __( 'Pay for order #%d via %s', 'areeba-mpgs' ), $order_id, $this->title ); ?>",
                            customerOrderDate:"<?php echo date('Y-m-d'); ?>",
                            customerReference:"<?php echo $order->user_id; ?>",
                            reference:"<?php echo $order_id; ?>"
                        },
                        session: {
                            id: "<?php echo esc_js( $_REQUEST['sessionId'] ); ?>"
                        },
                        billing:{
                            address: {
                                city:"<?php echo $order->billing_city; ?>",
                                country:"<?php echo $this->kia_convert_country_code( $order->billing_country ); ?>",
                                postcodeZip:"<?php echo $order->billing_postcode; ?>",
                                stateProvince:"<?php echo $order->billing_state; ?>",
                                street:"<?php echo $order->billing_address_1; ?>",
                                street2:"<?php echo $order->billing_address_2; ?>"
                            }
                        },
                        customer:{
                            email:"<?php echo $order->billing_email; ?>",
                            firstName:"<?php echo $order->billing_first_name; ?>",
                            lastName:"<?php echo $order->billing_last_name; ?>",
                            phone:"<?php echo $order->billing_phone; ?>"
                        },
                        interaction: {
                            merchant: {
                                name: "<?php echo $this->merchant_name; ?>",
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
				<?php
			} else {
				wc_add_notice( __( 'Payment error: Session not found.', 'areeba-mpgs' ), 'error' );
				wp_redirect( wc_get_checkout_url() );
				exit;
			}
		}

		/**
		 * Handle Areeba MPGS response
		 */
		public function process_response () {

			global $woocommerce;
			$order_id = $_REQUEST['order_id'];
			$order = wc_get_order( $order_id );
			$resultIndicator = $_REQUEST['resultIndicator'];
			$mpgs_successIndicator = get_post_meta( $order_id, "areeba_mpgs_successIndicator", true );

			if( $resultIndicator == $mpgs_successIndicator ) {
				$woocommerce->cart->empty_cart();

				$request_url = $this->service_host . "api/rest/version/49/merchant/" . $this->merchant_id . "/order/" . $order_id;

				// Request the order payment details
				$response_json = wp_remote_get( $request_url, array(
					'headers' => array(
						'Authorization' => 'Basic ' . base64_encode( "merchant." . $this->merchant_id . ":" . $this->auth_pass ),
					),
				) );

				$response = json_decode( $response_json['body'], true );
                $transaction_id = $response['transaction'][0]['authorizationResponse']['transactionIdentifier'];
                $transaction_receipt = $response['transaction'][0]['transaction']['receipt'];

                if( ! empty( $transaction_id ) && ! empty( $transaction_receipt ) ) {
	                $order->add_order_note( sprintf( __( 'MPGS Payment completed with Transaction ID: %s and Transaction Receipt: %s)', 'areeba-mpgs' ), $transaction_id, $transaction_receipt ) );
	                $order->payment_complete( $transaction_id );

	                wp_redirect( $this->get_return_url( $order ) );
                } else {
	                wc_add_notice( __('Payment error: Something went wrong.', 'areeba-mpgs'), 'error' );
                }

            } else {
				wc_add_notice( __('Payment error: Invalid transaction.', 'areeba-mpgs'), 'error' );
            }
            // reaching this line means there is an error, redirect back to checkout page
			wp_redirect( wc_get_checkout_url() );
			exit;
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
add_action( 'plugins_loaded', 'wc_areeba_mpgs_init' );