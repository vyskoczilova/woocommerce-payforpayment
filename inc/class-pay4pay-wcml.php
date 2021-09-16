<?php

/**
 * Handle integration with WooCommerce Multilingual
 *
 * @see https://woocommerce.com/products/multi-currency/
 * @package Pay4Pay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Pay4Pay_WCML' ) ) :

	/**
	 * * Pay4Pay_WCML Class
	 */
	class Pay4Pay_WCML {

		/**
		 * Hook actions and filters
		 */
		public static function init() {
			add_filter( 'woocommerce_pay4pay_charges_fixed', array( __CLASS__, 'get_converted_price' ) );
			add_filter( 'woocommerce_pay4pay_charges_minimum', array( __CLASS__, 'get_converted_price' ) );
			add_filter( 'woocommerce_pay4pay_charges_maximum', array( __CLASS__, 'get_converted_price' ) );
		}

		/**
		 * Return the gateway fee by the exchange rate.
		 *
		 * @param float $fee The gateway fee.
		 */
		public static function get_converted_price( $fee ) {
			return apply_filters( 'wcml_raw_price_amount', $fee );
		}
	}

	// Check PBoC version and init the integration.
	if ( defined('WCML_VERSION') ) {
		Pay4Pay_WCML::init();
	}

endif;
