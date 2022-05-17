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

if ( ! class_exists( 'Pay4Pay_WOOCS' ) ) :

	/**
	 * * Pay4Pay_WOOCS Class
	 */
	class Pay4Pay_WOOCS {

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
				global $WOOCS;
				return $WOOCS->woocs_exchange_value($fee);
		}
	}

	// Check if integration exists.
	if (class_exists('WOOCS')) {
		Pay4Pay_WOOCS::init();
	}

endif;
