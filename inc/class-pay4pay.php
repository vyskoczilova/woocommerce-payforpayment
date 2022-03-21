<?php
/**
 * Pay4Pay
 *
 * @package	Pay4Pay
 */

class Pay4Pay {

	private static $_instance = null;
	private $_fee = null;
	public static $required_wc_version = '2.6.0';

	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	public static function get_default_settings() {
		return array(
			'pay4pay_item_title' => __( 'Extra Charge', 'woocommerce-pay-for-payment' ),
			'pay4pay_charges_fixed' => 0,
			'pay4pay_charges_percentage' => 0,
			'pay4pay_disable_on_free_shipping' => 'no',
			'pay4pay_disable_on_zero_shipping' => 'no',

			'pay4pay_taxes' => 'no',
			'pay4pay_includes_taxes' => 'yes',
			'pay4pay_tax_class' => '',

			'pay4pay_enable_extra_fees' => 'no',
			'pay4pay_include_shipping' => 'no',
			'pay4pay_include_coupons' => 'no',
			'pay4pay_include_cart_taxes' => 'yes',
		);
	}

	private function __construct() {
		if ( version_compare( WC_VERSION, '3.2', '<' )) {
			add_action( 'woocommerce_calculate_totals', array( $this, 'calculate_pay4payment' ), 99 );
		}
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_pay4payment' ), ( PHP_INT_MAX - 1 ), 1 );
		add_action( 'woocommerce_review_order_after_submit', array( $this, 'print_autoload_js' ) );
		add_action( 'admin_init', array( $this, 'check_wc_version' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'woocommerce-pay-for-payment', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public function check_wc_version() {
		if ( ! function_exists( 'WC' ) || version_compare( WC()->version, self::$required_wc_version ) < 0 ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			add_action( 'admin_notices', array( __CLASS__, 'wc_version_notice' ) );
		}
	}

	public static function wc_version_notice() {
		?><div class="error"><p><?php
			printf( __( 'Pay for Payment for WooCommerce requires at least WooCommerce %s. Please update!', 'woocommerce-pay-for-payment' ), self::$required_wc_version );
		?></p></div><?php
	}

	public function print_autoload_js(){
		?><script type="text/javascript">
jQuery(document).ready(function($){
	$(document.body).on('change', 'input[name="payment_method"]', function() {
		$('body').trigger('update_checkout');
		if (typeof $fragment_refresh !== 'undefined') {
			$.ajax($fragment_refresh);
		}
	});
});
		</script><?php
	}

	public function add_pay4payment( $cart ) {
		if ( version_compare( WC_VERSION, '3.2', '>' )) {
			$this->_fee = NULL;
			$this->calculate_pay4payment();
		}
		if ( ! is_null( $this->_fee ) ) {
			$cart->add_fee( $this->_fee->fee_title,
				$this->_fee->cost,
				$this->_fee->taxable,
				$this->_fee->tax_class
			);

		}
	}

	public function calculate_pay4payment() {

		if ( ! is_null( $this->_fee ) ) {
			return;
		}

		$cart = WC()->cart;

		/**
		 * Check the cart value & shipping value if the cost is 0 don't add the fee
		 * @since 2.0.11
		 * @version 2.0.12
		 */
		if ( $cart->subtotal == 0 && $cart->shipping_total == 0) {
			return;
		}

		if ( ( $current_gateway = $this->get_current_gateway() ) && ( $settings = $this->get_current_gateway_settings() ) ) {
			$settings = wp_parse_args( $settings, self::get_default_settings() );
			$chosen_methods =  WC()->session->get( 'chosen_shipping_methods' );

			/**
			 * Check if COD is enabled for current shipping method
			 * @since 2.0.9
			 * @version 2.0.10
			 */
			if ( $current_gateway->id == 'cod' && ! empty( $settings['enable_for_methods'] ) ) {
				$chosen_methods_type = explode( ':', $chosen_methods[0] ); // solves problem with "any" type of method selected
				if ( ! in_array( $chosen_methods[0], $settings['enable_for_methods'] ) && ! in_array( $chosen_methods_type[0], $settings['enable_for_methods'] ) ) {
					return;
				}
			}

			$disable_on_free_shipping	= 'yes' == $settings['pay4pay_disable_on_free_shipping'];
			$disable_on_zero_shipping	= 'yes' == $settings['pay4pay_disable_on_zero_shipping'];

			$include_shipping			= 'yes' == $settings['pay4pay_include_shipping'];
			$include_fees 				= 'yes' == $settings['pay4pay_enable_extra_fees'];
			$include_coupons			= 'yes' == $settings['pay4pay_include_coupons'];
			$include_cart_taxes 		= 'yes' == $settings['pay4pay_include_cart_taxes'];
			$taxable					= 'yes' == $settings['pay4pay_taxes'];
			// wc tax options
			$calc_taxes					= 'yes' == get_option( 'woocommerce_calc_taxes' );
			$include_taxes				= 'yes' == $settings['pay4pay_includes_taxes'];
			$tax_class					= $settings['pay4pay_tax_class'];

			/**
			 * Check if user is is_vat_exempt
			 * https://docs.woocommerce.com/document/class-reference/#section-5
			 * @version 2.0.8
			 */
			global $woocommerce;
			if ( $woocommerce->customer->is_vat_exempt() ) {
				$taxable = false;
			}

			if ( $settings['pay4pay_charges_fixed'] || $settings['pay4pay_charges_percentage'] ) {

				if ( is_null( $chosen_methods ) ) {
					$chosen_methods[]=null;
				}

				if ( ( ! $disable_on_free_shipping || ! preg_grep( '/^free_shipping.*/', $chosen_methods ) ) && ( ! $disable_on_zero_shipping || $cart->shipping_total > 0 ) ) {
					$cost = floatval( apply_filters( 'woocommerce_pay4pay_charges_fixed', $settings['pay4pay_charges_fixed'], $current_gateway ) );

					//  √ $this->cart_contents_total + √ $this->tax_total + √ $this->shipping_tax_total + $this->shipping_total + $this->fee_total,
					$calculation_base = 0;
					if ( $percent = floatval( $settings['pay4pay_charges_percentage'] ) ) {

						$calculation_base = $cart->subtotal_ex_tax;

						if ( $include_shipping )
							$calculation_base += $cart->shipping_total;

						if ( $include_fees )
							$calculation_base += $cart->fee_total;

						if ( $include_coupons ) {
							if ( version_compare( WC_VERSION, '3.2', '>' )) {
								$calculation_base -= (float) $cart->get_total_discount() + (float) $cart->discount_cart;
							} else {
								$calculation_base -= (float) $cart->discount_total + (float) $cart->discount_cart;
							}
						}

						if ( $include_cart_taxes ) {
							$calculation_base += $cart->tax_total;
							if ( $include_shipping )
								$calculation_base += $cart->shipping_tax_total;
						}

						$cost += $calculation_base * ( $percent / 100 );

					}

					$do_apply = $cost != 0;
					$do_apply = apply_filters( "woocommerce_pay4pay_apply", $do_apply, $cost, $calculation_base, $current_gateway );
					$do_apply = apply_filters( "woocommerce_pay4pay_applyfor_{$current_gateway->id}", $do_apply, $cost, $calculation_base, $current_gateway );

					if ( $do_apply ) {
						// make our fee being displayed in the order total
						$fee_title = $settings['pay4pay_item_title'] ? apply_filters('wpml_translate_single_string', $settings['pay4pay_item_title'], 'woocommerce-pay-for-payment', $current_gateway->id.' - item title' ) : $current_gateway->title;

						$fee_title = str_replace(
							array( '[FIXED_AMOUNT]', '[PERCENT_AMOUNT]', '[CART_TOTAL]' ),
							array(
								strip_tags( wc_price( apply_filters('wpml_translate_single_string', $settings['pay4pay_charges_fixed'], 'woocommerce-pay-for-payment', $current_gateway->id.' - charges fixed' ) ) ),
								floatval( $settings['pay4pay_charges_percentage'] ),
								strip_tags(wc_price($calculation_base)),
							),
							$fee_title );
						$fee_id = sanitize_title( $fee_title );

						// apply min + max before tax calculation
						// some people may like to use the plugin to apply a discount, so we need to handle negative values correctly
						if ( $settings['pay4pay_charges_percentage'] ) {
							$min_cost = !empty( $settings['pay4pay_charges_minimum'] ) ? apply_filters( 'woocommerce_pay4pay_charges_minimum', $settings['pay4pay_charges_minimum'], $current_gateway ) : -INF;
							$max_cost = !empty( $settings['pay4pay_charges_maximum'] ) && (bool) $settings['pay4pay_charges_maximum'] ? apply_filters( 'woocommerce_pay4pay_charges_maximum', $settings['pay4pay_charges_maximum'], $current_gateway ) : INF;
							$cost = max( $min_cost, $cost );
							$cost = min( $max_cost, $cost );

							// Allow placeholders in this case.
							$fee_title = str_replace(
								array('[MINIMUM_AMOUNT]', '[MAXIMUM_AMOUNT]'),
								array(
									strip_tags(wc_price($min_cost)),
									strip_tags(wc_price($max_cost)),
								),
								$fee_title
							);
						}

						// WooCommerce Fee is always ex taxes. We need to subtract taxes, WC will add them again later.
						if ( $taxable && $include_taxes ) {

							// Apply the highest tax in the cart (see #65)
							if ( $tax_class === 'inherit' ) {
								$highestTaxRate = 0;
								if ( $cart->get_cart() ) {
									foreach ( $cart->get_cart() as $item ) {
										if ( !$item['line_tax'] || !$item['line_total'] ) {
											$itemTaxRate = 0;
										} else {
											$itemTaxRate = $item['line_tax'] / $item['line_total'];
										}

										if ( $itemTaxRate >= $highestTaxRate ) {
											$highestTaxRate = $itemTaxRate;
											$tax_class = $item['data']->tax_class;
										}
									}
								}
							}

							$tax_rates = WC_Tax::get_rates( $tax_class );

							$factor = 1;
							foreach ( $tax_rates as $rate ) {
								$factor += $rate['rate']/100;
							}
							$cost /= $factor;
						}

						$cost = apply_filters( "woocommerce_pay4pay_{$current_gateway->id}_amount", $cost, $calculation_base, $current_gateway, $taxable, $include_taxes, $tax_class );
						$cost = round($cost, \wc_get_rounding_precision());

						$this->_fee = (object) array(
							'fee_title' => $fee_title,
							'cost'      => $cost,
							'taxable'   => $taxable,
							'tax_class' => $tax_class,
						);

						if ( version_compare( WC_VERSION, '3.2', '<' )) {
							$cart->calculate_totals();
						}

						return;
					}
				}
			}
		}
	}

	/**
	 * Get current gateway.
	 *
	 * The Stripe for woocommerce plugin considers itself unavailable if cart
	 * total is below 50 ct. At this point the cart total is not yet calculated
	 * and equals zero resulting in s4wc being unavaliable. We use
	 * `WC()->payment_gateways->payment_gateways()` in favor of
	 * `WC()->payment_gateways->get_available_payment_gateways()`
	 *
	 * @return mixed
	 */
	public function get_current_gateway() {
		$available_gateways = WC()->payment_gateways->payment_gateways();

		$current_gateway = null;
		$default_gateway = get_option( 'woocommerce_default_gateway' );
		if ( ! empty( $available_gateways ) ) {

		   // Chosen Method
			if ( isset( WC()->session->chosen_payment_method ) && isset( $available_gateways[ WC()->session->chosen_payment_method ] ) ) {
				$current_gateway = $available_gateways[ WC()->session->chosen_payment_method ];
			} elseif ( isset( $available_gateways[ $default_gateway ] ) ) {
				$current_gateway = $available_gateways[ $default_gateway ];
			} else {
				$current_gateway = current( $available_gateways );
			}
		}
		if ( ! is_null( $current_gateway ) )
			return $current_gateway;
		else
			return false;
	}

	public function get_current_gateway_settings( ) {
		if ( $current_gateway = $this->get_current_gateway() ) {
			$defaults = self::get_default_settings();
			$settings = $current_gateway->settings + $defaults;
			return apply_filters('(float) ', $settings, $current_gateway);
		}
		return false;
	}

	public function get_woocommerce_tax_classes() {

		$tax_class_options = array('inherit' => __('Payment fee tax class based on cart items', 'woocommerce-pay-for-payment')) +  \wc_get_product_tax_class_options(); // Since 3.0

		return $tax_class_options;
	}
}
