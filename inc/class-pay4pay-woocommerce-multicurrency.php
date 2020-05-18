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

if ( ! class_exists( 'Pay4Pay_WooCommerce_MultiCurrency' ) ) :

	/**
	 * * Pay4Pay_WooCommerce_MultiCurrency Class
	 */
	class Pay4Pay_WooCommerce_MultiCurrency {

		/**
		 * Hook actions and filters
		 */
		public static function init() {
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
			$currency_detector = new WOOMC\Currency\Detector();
			$rate_storage = new WOOMC\Rate\Storage();
			$price_rounder = new WOOMC\Price\Rounder();
			$price_calculator = new WOOMC\Price\Calculator( $rate_storage, $price_rounder );

			$price_controller = new WOOMC\Price\Controller( $price_calculator, $currency_detector );
			return $price_controller->convert( $fee );
		}
	}

		// Check PBoC version and init the integration.
	if ( defined('WOOCOMMERCE_MULTICURRENCY_VERSION') && version_compare( WOOCOMMERCE_MULTICURRENCY_VERSION, '2.5.0', '>=' ) ) {
		Pay4Pay_WooCommerce_MultiCurrency::init();
	}

endif;
