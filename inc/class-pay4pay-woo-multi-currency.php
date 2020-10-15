<?php
/**
 * Handle integration with WooCommerce Multi-Currency
 *
 * @see https://woocommerce.com/products/multi-currency/
 * @package Pay4Pay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Pay4Pay_Woo_Multi_Currency' ) ) :

	/**
	 * * Pay4Pay_Woo_Multi_Currency Class
	 */
	class Pay4Pay_Woo_Multi_Currency {

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
			return wmc_get_price( $fee );
		}
	}

	// Check PBoC version and init the integration.
	if ( defined('WOOMULTI_CURRENCY_F_VERSION') && version_compare( WOOMULTI_CURRENCY_F_VERSION, '2.1.5', '>=' ) ||
	defined('WOOMULTI_CURRENCY_VERSION') && version_compare(WOOMULTI_CURRENCY_VERSION, '2.1.9', '>=') ) {
		Pay4Pay_Woo_Multi_Currency::init();
	}

endif;
