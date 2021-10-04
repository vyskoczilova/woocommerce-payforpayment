<?php
/**
 * Pay4Pay Admin
 *
 * @package	Pay4Pay
 * @since	1.2.0
 */

if ( ! class_exists( 'Pay4Pay_Admin' ) ) :

class Pay4Pay_Admin {

	private static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	private function __construct() {
		// handle options
		add_action( 'wp_loaded', array( $this, 'add_payment_options' ), 99 );
		add_action( 'woocommerce_update_options_checkout', array( $this, 'add_payment_options' ) );
		add_action( 'admin_init', array( $this, 'check_wc_version' ) );

		// payment gateways table
		add_filter( 'woocommerce_payment_gateways_setting_columns', array( $this, 'add_extra_fee_column' ) );
		add_action( 'woocommerce_payment_gateways_setting_column_pay4pay_extra', array( $this, 'extra_fee_column_content' ) );

		// add save actions for every single method - related to: https://github.com/woocommerce/woocommerce/pull/23091
		if ( version_compare( WC_VERSION, '3.7', '>=' )) {
			add_action( 'wp_loaded', array( $this, 'save_actions_for_every_method' ) );
		}

		// settings script
		add_action( 'load-woocommerce_page_wc-settings', array( $this, 'enqueue_checkout_settings_js' ) );
	}

	public function save_actions_for_every_method() {
		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway_id => $gateway ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $gateway->id, array( $this,'update_payment_options' ), 20 );
		}
	}

	public function check_wc_version() {
		if ( ! function_exists( 'WC' ) || version_compare( WC()->version, Pay4Pay::$required_wc_version ) < 0 ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			add_action( 'admin_notices', array( __CLASS__, 'wc_version_notice' ) );
		}
	}

	public static function wc_version_notice() {
		?><div class="error"><p><?php
			printf( __( 'Pay for Payment for WooCommerce requires at least WooCommerce %s. Please update!', 'woocommerce-pay-for-payment' ), Pay4Pay::$required_wc_version );
		?></p></div><?php
	}

	public function enqueue_checkout_settings_js(){
		if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'checkout' ) {
			wp_enqueue_script( 'pay4pay_settings_checkout', plugins_url( '/js/pay4pay-settings-checkout.js', dirname( __FILE__ ) ), array( 'woocommerce_admin' ) );
			wp_enqueue_style( 'pay4pay_settings_checkout', plugins_url( '/css/pay4pay-settings-checkout.css', dirname( __FILE__ ) ), array() );
		}
	}

	public function add_payment_options( ) {
		$defaults = Pay4Pay::get_default_settings();
		$tax_class_options = Pay4Pay::instance()->get_woocommerce_tax_classes();

		// general
		$form_fields = array(
			'pay4pay_title' => array(
				'title' => __( 'Extra Charge', 'woocommerce-pay-for-payment' ),
				'type' => 'title',
				'class' => 'pay4pay-title',
				'description' => '',
			),
			'pay4pay_item_title' => array(
				'title' => __( 'Item Title', 'woocommerce-pay-for-payment' ),
				'type' => 'text',
				'description' => __( 'This will show up in the shopping basket.', 'woocommerce-pay-for-payment' ),
				'desc_tip' => true,
			),
			'pay4pay_charges_fixed' => array(
				'title' => __( 'Fixed charge', 'woocommerce-pay-for-payment' ),
				'type' => 'number',
				'description' => __( 'Extra charge to be added to cart when this payment method is selected.', 'woocommerce-pay-for-payment' ),
				'desc_tip' => true,
				'custom_attributes' => array(
					'step' => 'any',
				),
			),
			'pay4pay_charges_percentage' => array(
				'title' => __( 'Percent charge', 'woocommerce-pay-for-payment' ),
				'type' => 'number',
				'description' => __( 'Percentage of cart total to be added to payment.', 'woocommerce-pay-for-payment' ),
				'desc_tip' => true,
				'custom_attributes' => array(
					'step' => 'any',
					'data-setchangehandler' => '1',
					'data-reference-name' => 'woocommerce-pay4pay-percentage',

				),
				'id' => 'woocommerce-pay4pay-percentage',
			),
			'pay4pay_charges_minimum' => array(
				'title' => __( 'Charge at least', 'woocommerce-pay-for-payment' ),
				'type' => 'number',
				'description' => __( 'Minimum extra charge to be added to cart when this payment method is selected.', 'woocommerce-pay-for-payment' ),
				'desc_tip' => true,
				'custom_attributes' => array(
					'step' => 'any',
					'data-dependency-notzero' => 'woocommerce-pay4pay-percentage',
				),
			),
			'pay4pay_charges_maximum' => array(
				'title' => __( 'Charge at most', 'woocommerce-pay-for-payment' ),
				'type' => 'number',
				'description' => __( 'Maximum extra charge to be added to cart when this payment method is selected. Enter zero to disable.', 'woocommerce-pay-for-payment' ),
				'desc_tip' => true,
				'custom_attributes' => array(
					'step' => 'any',
					'data-dependency-notzero' => 'woocommerce-pay4pay-percentage',
				),
			),
			'pay4pay_disable_on_free_shipping' => array(
				'title' => __( 'Disable on Free Shipping', 'woocommerce-pay-for-payment' ),
				'label' => __( 'Don’t charge this fee when free shipping is available.', 'woocommerce-pay-for-payment' ),
				'type' => 'checkbox',
				'desc_tip' => true,
			),
			'pay4pay_disable_on_zero_shipping' => array(
				'title' => __( 'Disable on Zero Shipping', 'woocommerce-pay-for-payment' ),
				'label' => __( 'Don’t charge this fee when zero shipping is available.', 'woocommerce-pay-for-payment' ),
				'type' => 'checkbox',
				'desc_tip' => true,
			),
		);

		// taxes
		if ( 'yes' == get_option( 'woocommerce_calc_taxes' ) ) {
			$form_fields += array(
				'pay4pay_title_taxes' => array(
					'title' => __( 'Extra Charge Taxes', 'woocommerce-pay-for-payment' ),
					'type' => 'title',
					'class' => 'pay4pay-title',
				),
				'pay4pay_taxes' => array(
					'title' => __( 'Taxable','woocommerce-pay-for-payment' ),
					'type' => 'checkbox',
					'label' => __( 'Payment fee is taxable', 'woocommerce-pay-for-payment' ),
					'custom_attributes' => array(
						'data-setchangehandler' => '1' ,
						'data-reference-name' => 'woocommerce-pay4pay-taxes',
					),
				),
				'pay4pay_includes_taxes' => array(
					'title' => __( 'Inclusive Taxes','woocommerce-pay-for-payment' ),
					'type' => 'checkbox',
					'label' => __( 'The payment fee is inclusive of taxes.', 'woocommerce-pay-for-payment' ),
					'description' => __( 'If you leave this unchecked taxes will be calculated on top of the payment fee.', 'woocommerce-pay-for-payment' ),
					'desc_tip' => true,
					'class' => 'pay4pay_taxes',
					'custom_attributes' => array( 'data-dependency-notzero' => 'woocommerce-pay4pay-taxes' ),
				),
				'pay4pay_tax_class' => array(
					'title' => __( 'Tax class','woocommerce-pay-for-payment' ),
					'type' => 'select',
					'description' => __( 'Select a the tax class applied to the extra charge.', 'woocommerce-pay-for-payment' ),
					'options' => $tax_class_options,
					'desc_tip' => true,
					'class' => 'pay4pay_taxes', // display when pay4pay_taxes != 0
					'custom_attributes' => array( 'data-dependency-notzero' => 'woocommerce-pay4pay-taxes' ),
				),
			);
		}

		// include in calculation
		$form_fields += array(
			// which cart items to include in calculation
			'pay4pay_title_include' => array(
				'title' => __( 'Include in percental payment fee calculation:', 'woocommerce-pay-for-payment' ),
				'type' => 'title',
				'class' => 'pay4pay-title dependency-notzero-woocommerce-pay4pay-percentage',
				'custom_attributes' => array( 'data-dependency-notzero' => 'woocommerce-pay4pay-percentage' ),
			),
			'pay4pay_enable_extra_fees' => array(
				'title' => __( 'Fees','woocommerce-pay-for-payment' ),
				'type' => 'checkbox',
				'label' => __( 'Include fees in calculation.', 'woocommerce-pay-for-payment' ),
				'desc_tip' => true,
				'class' => 'pay4pay_charges_percentage',
				'custom_attributes' => array( 'data-dependency-notzero' => 'woocommerce-pay4pay-percentage' ),
			),
			'pay4pay_include_coupons' => array(
				'title' => __( 'Coupons','woocommerce-pay-for-payment' ),
				'type' => 'checkbox',
				'label' => __( 'Include Coupons in calculation.', 'woocommerce-pay-for-payment' ),
				'desc_tip' => true,
				'class' => 'pay4pay_charges_percentage',
				'custom_attributes' => array( 'data-dependency-notzero' => 'woocommerce-pay4pay-percentage' ),
			),
			'pay4pay_include_shipping' => array(
				'title' => __( 'Shipping','woocommerce-pay-for-payment' ),
				'type' => 'checkbox',
				'label' => __( 'Include shipping cost in calculation.', 'woocommerce-pay-for-payment' ),
				'desc_tip' => true,
				'class' => 'pay4pay_charges_percentage',
				'custom_attributes' => array( 'data-dependency-notzero' => 'woocommerce-pay4pay-percentage' ),
			),
		);
		if ( 'yes' == get_option( 'woocommerce_calc_taxes' ) ) {
			$form_fields += array(
				'pay4pay_include_cart_taxes' => array(
					'title' => __( 'Taxes','woocommerce-pay-for-payment' ),
					'type' => 'checkbox',
					'label' => __( 'Include taxes in calculation.', 'woocommerce-pay-for-payment' ),
					'desc_tip' => true,
					'class' => 'pay4pay_charges_percentage',
					'custom_attributes' => array( 'data-dependency-notzero' => 'woocommerce-pay4pay-percentage' ),
				),
			);
		}

		foreach ( $defaults as $option_key => $default_value ) {
			if ( array_key_exists( $option_key, $form_fields ) ) {
				$form_fields[$option_key]['default'] = $default_value;
			}
		}

		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway_id => $gateway ) {
			$form_fields['pay4pay_item_title']['default'] = $gateway->title;
			$gateway->form_fields = array_merge(is_array($gateway->form_fields) ? $gateway->form_fields : [], $form_fields);
			if ( version_compare( WC_VERSION, '3.7', '<' )) {
				add_action( 'woocommerce_update_options_payment_gateways_' . $gateway->id, array( $this,'update_payment_options' ), 20 );
			}
		}
	}

	public function update_payment_options() {
		global $current_section;

		$class_id = $current_section;

		// Fix for Eurobank WooCommerce Payment Gateway https://el.wordpress.org/plugins/woo-payment-gateway-for-eurobank/
		// TODO add filter and move this to filter.
		if ( $class_id === 'wc_eurobank_gateway') {
			$class_id = "eurobank_gateway";
		}

		$prefix   = 'woocommerce_' . $class_id;
		$postfix  = '_settings';

		// Default WooCommerce gateways use this option name format
		$opt_name = $prefix . $postfix;

		// Try to get the WooCommerce Gateway settings with default format
		$options  = get_option( $opt_name );

		// Try to get the WooComm erce Gateway settings with fallback format
		if ( $options === false ) {
			$opt_name = $class_id . $postfix;
			$options  = get_option( $opt_name );
			$prefix = $class_id;
		}

		// Check if $options is false, and if it is, show an admin notice?
		if ( $options === false ) {
			add_action( 'admin_notices', array( $this, 'update_error_notice') );
			return;
		}

		$tax_class_sanitize = ( isset( $_POST[$prefix . '_pay4pay_tax_class'] )? $_POST[$prefix . '_pay4pay_tax_class'] : '' );

		$item_title = sanitize_text_field( $_POST[$prefix . '_pay4pay_item_title'] );
		$charges_fixed = floatval( $_POST[$prefix . '_pay4pay_charges_fixed'] );

		// validate!
		$extra = array(
			'pay4pay_item_title' 				=> $item_title,
			'pay4pay_charges_fixed' 			=> $charges_fixed,
			'pay4pay_charges_percentage' 		=> floatval( $_POST[$prefix . '_pay4pay_charges_percentage'] ),
			'pay4pay_charges_minimum'			=> floatval( $_POST[$prefix . '_pay4pay_charges_minimum'] ),
			'pay4pay_charges_maximum'			=> floatval( $_POST[$prefix . '_pay4pay_charges_maximum'] ),
			'pay4pay_disable_on_free_shipping'	=> $this->_get_bool( $prefix . '_pay4pay_disable_on_free_shipping' ),
			'pay4pay_disable_on_zero_shipping'	=> $this->_get_bool( $prefix . '_pay4pay_disable_on_zero_shipping' ),

			'pay4pay_taxes' 					=> $this->_get_bool( $prefix . '_pay4pay_taxes' ),
			'pay4pay_includes_taxes'			=> $this->_get_bool( $prefix . '_pay4pay_includes_taxes' ),
			'pay4pay_tax_class' 				=> $this->_sanitize_tax_class( $tax_class_sanitize ), // 0, incl, excl

			'pay4pay_enable_extra_fees'			=> $this->_get_bool( $prefix . '_pay4pay_enable_extra_fees' ),
			'pay4pay_include_shipping'			=> $this->_get_bool( $prefix . '_pay4pay_include_shipping' ),
			'pay4pay_include_coupons'			=> $this->_get_bool( $prefix . '_pay4pay_include_coupons' ),
			'pay4pay_include_cart_taxes'		=> $this->_get_bool( $prefix . '_pay4pay_include_cart_taxes' ),
		);
		$options = array_merge( $options, $extra );

		// WMPL
		// https://wpml.org/wpml-hook/wpml_register_single_string/
		/**
		* register strings for translation
		*/
		do_action( 'wpml_register_single_string', 'woocommerce-pay-for-payment', $class_id.' - item title', $item_title );
		do_action( 'wpml_register_single_string', 'woocommerce-pay-for-payment', $class_id.' - charges fixed', $charges_fixed );
		//WMPL

		update_option( $opt_name, $options );

	}

	public function update_error_notice() {
		global $current_section;
		?>
		<div class="error notice">
			<p><?php _e( 'There has been an error within the Pay for Payment for WooCommerce plugin and settings can\'t be saved.', 'woocommerce-pay-for-payment' ); ?> <b><?php printf( __( 'To fix the issue, contact the plugin author either on %sGithub%s or %sWordPress.org%s and provide following information:', 'woocommerce-pay-for-payment' ), '<a href="https://github.com/vyskoczilova/woocommerce-payforpayment/issues/new" target="_blank">', '</a>', '<a href="https://wordpress.org/support/plugin/woocommerce-pay-for-payment" target="_blank">', '</a>' ); ?></b></p>
			<pre>
	1. <?php _e( 'Name of the payment method you are trying to save and the URL where it can be downloaded for testing.', 'woocommerce-pay-for-payment' ); ?><br />
	2. <?php echo __( 'Current section ID:', 'woocommerce-pay-for-payment' ) . ' '. $current_section; ?><br />
	3. <?php _e( 'Anything else might be helpful (recently added/updated plugins, etc.).', 'woocommerce-pay-for-payment' ); ?>
			</pre>
		</div>
		<?php
	}

	private function _sanitize_tax_option( $tax_option, $default = 'incl' ) {
		if ( in_array( $tax_option, array( 0, 'incl', 'excl' ) ) )
			return $tax_option;
		return $default;
	}

	private function _sanitize_tax_class( $tax_option, $default = 'incl' ) {
		if ( in_array( $tax_option, array( 0, 'incl', 'excl' ) ) )
			return $tax_option;
		return $default;
	}

	private function _get_bool( $key ) {
		return isset( $_POST[ $key ] ) && $_POST[ $key ] === '1' ? 'yes' : 'no';
	}

	private function _get_float( $key ) {
		return isset( $_POST[ $key ] ) && $_POST[ $key ] === '1' ? 'yes' : 'no';
	}

	/*
	Handline columns in Woocommerce > settings > checkout
	*/
	public function add_extra_fee_column( $columns ) {
		$return = array_slice( $columns, 0, -1, true )
			+ array( 'pay4pay_extra' => __( 'Extra Charge', 'woocommerce-pay-for-payment' ) )
			+ array_slice( $columns, -1, 1, true );
		return $return;
	}

	public function extra_fee_column_content( $gateway ) {
		?><td><?php
			if ( isset( $gateway->settings['pay4pay_charges_fixed'] ) ) {
				$items = array();
//				$items[] = sprintf( '<strong>%s</strong>',$gateway->settings['pay4pay_item_title']);
				if ( $gateway->settings['pay4pay_charges_fixed'] )
					$items[] = wc_price( $gateway->settings['pay4pay_charges_fixed'] );
				if ( $gateway->settings['pay4pay_charges_percentage'] ) {
					$items[] = sprintf( _x( '%s %% of cart totals', 'Gateway list column', 'pay4pay' ), $gateway->settings['pay4pay_charges_percentage'] );

					if ( isset( $gateway->settings['pay4pay_charges_minimum'] ) && $gateway->settings['pay4pay_charges_minimum'] )
						$items[] = wc_price($gateway->settings['pay4pay_charges_minimum'] );
					if ( isset($gateway->settings['pay4pay_charges_maximum']) && $gateway->settings['pay4pay_charges_maximum'] )
						$items[] = wc_price($gateway->settings['pay4pay_charges_maximum'] );
				}
				echo implode( '<br />', $items );
			}
		?></td><?php
	}
}

Pay4Pay_Admin::instance();

endif;
