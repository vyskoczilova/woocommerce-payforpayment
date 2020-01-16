<?php
/**
 * Handle integration with WooCommerce Price Based on Country.
 *
 * @see https://wordpress.org/plugins/woocommerce-product-price-based-on-countries/
 * @package Pay4Pay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Pay4Pay_Price_Based_Country' ) ) :

	/**
	 * * Pay4Pay_Price_Based_Country Class
	 */
	class Pay4Pay_Price_Based_Country {

		/**
		 * Hook actions and filters
		 */
		public static function init() {
			add_action( 'wc_price_based_country_frontend_princing_init', array( __CLASS__, 'frontend_pricing_init' ) );
		}

		/**
		 * Front-end init.
		 */
		public static function frontend_pricing_init() {
			add_filter( 'woocommerce_pay4pay_charges_fixed', array( __CLASS__, 'fee_by_exchange_rate' ) );
			add_filter( 'woocommerce_pay4pay_charges_minimum', array( __CLASS__, 'fee_by_exchange_rate' ) );
			add_filter( 'woocommerce_pay4pay_charges_maximum', array( __CLASS__, 'fee_by_exchange_rate' ) );
		}

		/**
		 * Return the gateway fee by the exchange rate.
		 *
		 * @param float $fee The gateway fee.
		 */
		public static function fee_by_exchange_rate( $fee ) {
			return wcpbc_the_zone()->get_exchange_rate_price( $fee );
		}
	}

	// Check PBoC version and init the integration.
	if ( function_exists( 'wcpbc' ) && version_compare( wcpbc()->version, '1.8', '>=' ) ) {
		Pay4Pay_Price_Based_Country::init();
	}

endif;
