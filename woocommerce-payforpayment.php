<?php
/*
Plugin Name: Pay for Payment for WooCommerce
Plugin URI: https://kybernaut.cz/pluginy/woocommerce-pay-for-payment/
Description: Setup individual charges for each payment method in WooCommerce.
Version: 2.1.9
Author: Karolína Vyskočilová
Author URI: https://kybernaut.cz
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: woocommerce-pay-for-payment
Domain Path: /languages
WC requires at least: 2.6
WC tested up to: 9.7.1
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

/**
 * Display beta testing notice
 */
function pay4payment_beta_notice() {
	// Check if notice has been dismissed
	$dismissed = get_transient('pay4payment_beta_notice_dismissed');
	if ($dismissed) {
		return;
	}

	// Check if user has capability
	if (!current_user_can('manage_options')) {
		return;
	}

	?>
	<div class="notice notice-info is-dismissible" data-dismissible="pay4payment-beta-notice">
		<p>
			<?php printf(__('Plugin %sPay for Payment for WooCommerce%s,  for beta (Q2 2025)! It comes with revamped settings and more features on the way. Want in? ', 'woocommerce-pay-for-payment'), '<strong>', '</strong>'); ?>
			<a href="https://kybernaut.cz/en/plugins/woocommerce-pay-for-payment/testing-v2/" target="_blank"><?php _e('Sign up here!', 'woocommerce-pay-for-payment'); ?></a>
		</p>
	</div>
	<script>
	jQuery(document).ready(function($) {
		$('.notice[data-dismissible]').on('click', '.notice-dismiss', function() {
			var $this = $(this);
			var dismissible = $this.closest('.notice').data('dismissible');

			$.post(ajaxurl, {
				action: 'dismiss_pay4payment_beta_notice',
				dismissible: dismissible,
				nonce: '<?php echo wp_create_nonce('dismiss_pay4payment_beta_notice'); ?>'
			});
		});
	});
	</script>
	<?php
}

/**
 * Handle notice dismissal
 */
function pay4payment_dismiss_beta_notice() {
	if (!isset($_POST['dismissible']) || !isset($_POST['nonce'])) {
		return;
	}

	if (!wp_verify_nonce($_POST['nonce'], 'dismiss_pay4payment_beta_notice')) {
		return;
	}

	if ($_POST['dismissible'] === 'pay4payment-beta-notice') {
		set_transient('pay4payment_beta_notice_dismissed', true, 60 * 60 * 24 * 60); // 60 days
	}

	wp_die();
}

add_action('admin_notices', 'pay4payment_beta_notice');
add_action('wp_ajax_dismiss_pay4payment_beta_notice', 'pay4payment_dismiss_beta_notice');

// Declare compatibility with HPOS.
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );
