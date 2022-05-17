<?php
/*
Plugin Name: Pay for Payment for WooCommerce
Plugin URI: https://kybernaut.cz/pluginy/woocommerce-pay-for-payment/
Description: Setup individual charges for each payment method in WooCommerce.
Version: 2.1.7
Author: Karolína Vyskočilová
Author URI: https://kybernaut.cz
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: woocommerce-pay-for-payment
Domain Path: /languages
WC requires at least: 2.6
WC tested up to: 6.5.1
*/

/**
 * Check if WooCommerce is active.
 *
 * @return void
 */
add_action( 'plugins_loaded', 'pay4payment_plugin_init' );
function pay4payment_plugin_init() {

	if ( current_user_can( 'activate_plugins' ) && ! class_exists( 'woocommerce' ) ) {

		add_action( 'admin_notices', 'pay4payment_admin_notice' );

	} else {

		require_once plugin_dir_path( __FILE__ ) . '/inc/class-pay4pay.php';
		Pay4Pay::instance();

		// Integrations.
		include_once plugin_dir_path( __FILE__ ) . '/inc/class-pay4pay-price-based-country.php';
		include_once plugin_dir_path( __FILE__ ) . '/inc/class-pay4pay-woocommerce-multicurrency.php';
		include_once plugin_dir_path( __FILE__ ) . '/inc/class-pay4pay-woo-multi-currency.php';
		include_once plugin_dir_path( __FILE__ ) . '/inc/class-pay4pay-wcml.php';
		include_once plugin_dir_path( __FILE__ ) . '/inc/class-pay4pay-woocs.php';

		if ( is_admin() )
			require_once plugin_dir_path( __FILE__ ) . '/inc/class-pay4pay-admin.php';
		}
}

/**
 * Display an alert to inform the admin why the plugin didn't activate
 *
 * @return void
 */
function pay4payment_admin_notice() {

	$pay4payment_plugin = __( 'Pay for Payment for WooCommerce', 'woocommerce-pay-for-payment' );
	$woocommerce_plugin = __( 'WooCommerce', 'woocommerce-pay-for-payment' );

	echo '<div class="error"><p>'
		. sprintf( __( '%1$s requires %2$s. Please activate %2$s before activation of %1$s. This plugin has been deactivated.', 'woocommerce-pay-for-payment' ), '<strong>' . esc_html( $pay4payment_plugin ) . '</strong>', '<strong>' . esc_html( $woocommerce_plugin ) . '</strong>' )
		. '</p></div>';

	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
}
