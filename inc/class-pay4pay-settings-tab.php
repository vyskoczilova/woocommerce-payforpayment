<?php
/**
 * Pay4Pay Settings Tab
 *
 * Creates a centralized WooCommerce settings tab for configuring payment fees
 * across all payment gateways, including React-based gateways that don't support
 * traditional settings field injection.
 *
 * @package Pay4Pay
 * @since   2.2.0
 */

// Ensure WC_Settings_Page class is loaded
if ( ! class_exists( 'WC_Settings_Page' ) ) {
	include_once WC()->plugin_path() . '/includes/admin/settings/class-wc-settings-page.php';
}

if ( ! class_exists( 'Pay4Pay_Settings_Tab' ) ) :

class Pay4Pay_Settings_Tab extends WC_Settings_Page {

	private static $_instance = null;

	/**
	 * Cached payment gateways to avoid repeated calls
	 *
	 * @var array|null
	 */
	private $payment_gateways = null;

	/**
	 * Cached taxes enabled status to avoid repeated get_option calls
	 *
	 * @var string|null
	 */
	private $taxes_enabled = null;

	/**
	 * Singleton instance
	 *
	 * @since 2.2.0
	 *
	 * @return Pay4Pay_Settings_Tab
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 *
	 * @since 2.2.0
	 */
	private function __construct() {
		$this->id    = 'pay4payment';
		$this->label = __( 'Pay for Payment', 'woocommerce-pay-for-payment' );

		parent::__construct();

		// Enqueue admin assets
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Get payment gateways with caching
	 *
	 * @since 2.2.0
	 *
	 * @return array
	 */
	private function get_payment_gateways() {
		if ( is_null( $this->payment_gateways ) ) {
			$this->payment_gateways = WC()->payment_gateways()->payment_gateways();
		}
		return $this->payment_gateways;
	}

	/**
	 * Check if taxes are enabled with caching
	 *
	 * @since 2.2.0
	 *
	 * @return bool
	 */
	private function are_taxes_enabled() {
		if ( is_null( $this->taxes_enabled ) ) {
			$this->taxes_enabled = get_option( 'woocommerce_calc_taxes' );
		}
		return 'yes' === $this->taxes_enabled;
	}

	/**
	 * Get sections (subtabs for each payment gateway)
	 *
	 * @since 2.2.0
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array();

		// Get all registered payment gateways (cached)
		$payment_gateways = $this->get_payment_gateways();

		foreach ( $payment_gateways as $gateway_id => $gateway ) {
			$sections[ $gateway->id ] = $gateway->get_title();
		}

		return $sections;
	}

	/**
	 * Output sections (subtabs)
	 *
	 * @since 2.2.0
	 */
	public function output_sections() {
		global $current_section;

		$sections = $this->get_sections();

		if ( empty( $sections ) || 1 === count( $sections ) ) {
			return;
		}

		echo '<ul class="subsubsub">';

		$array_keys = array_keys( $sections );

		foreach ( $sections as $id => $label ) {
			echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=' . $this->id . '&section=' . sanitize_title( $id ) ) ) . '" class="' . ( $current_section === $id ? 'current' : '' ) . '">' . esc_html( $label ) . '</a> ' . ( end( $array_keys ) === $id ? '' : '|' ) . ' </li>';
		}

		echo '</ul><br class="clear" />';
	}

	/**
	 * Get settings for current section (current gateway)
	 *
	 * @since 2.2.0
	 *
	 * @param string|null $section Optional. The section to get settings for. Defaults to global $current_section.
	 * @return array
	 */
	public function get_settings( $section = null ) {
		global $current_section;

		// Use provided section or fall back to global
		$section = $section ?? $current_section;

		// Get current gateway
		$gateway_id = $section;
		if ( empty( $gateway_id ) ) {
			// Default to first gateway
			$payment_gateways = $this->get_payment_gateways();
			$gateway_id = ! empty( $payment_gateways ) ? array_key_first( $payment_gateways ) : '';
		}

		if ( empty( $gateway_id ) ) {
			return array();
		}

		// Get gateway object (cached)
		$payment_gateways = $this->get_payment_gateways();
		$gateway = isset( $payment_gateways[ $gateway_id ] ) ? $payment_gateways[ $gateway_id ] : null;

		if ( ! $gateway ) {
			return array();
		}

		// Get defaults and merge with gateway settings
		$defaults = Pay4Pay::get_default_settings();
		$gateway_settings = isset( $gateway->settings ) ? $gateway->settings : array();
		$settings = wp_parse_args( $gateway_settings, $defaults );

		// Set default item title to gateway title if not set
		if ( empty( $settings['pay4pay_item_title'] ) || $settings['pay4pay_item_title'] === $defaults['pay4pay_item_title'] ) {
			$settings['pay4pay_item_title'] = $gateway->title;
		}

		// Get tax class options
		$tax_class_options = Pay4Pay::instance()->get_woocommerce_tax_classes();

		// Build form fields array
		$form_fields = array(
			array(
				'title' => sprintf( __( 'Extra Charge for %s', 'woocommerce-pay-for-payment' ), $gateway->get_title() ),
				'type'  => 'title',
				'desc'  => __( 'Configure payment fees for this gateway.', 'woocommerce-pay-for-payment' ),
				'id'    => 'pay4pay_title',
			),
			array(
				'title'    => __( 'Item Title', 'woocommerce-pay-for-payment' ),
				'type'     => 'text',
				'desc'     => __( 'This will show up in the shopping basket.', 'woocommerce-pay-for-payment' ),
				'desc_tip' => true,
				'id'       => 'woocommerce_' . $gateway_id . '_pay4pay_item_title',
				'default'  => $settings['pay4pay_item_title'],
			),
			array(
				'title'             => __( 'Fixed charge', 'woocommerce-pay-for-payment' ),
				'type'              => 'number',
				'desc'              => __( 'Extra charge to be added to cart when this payment method is selected.', 'woocommerce-pay-for-payment' ),
				'desc_tip'          => true,
				'id'                => 'woocommerce_' . $gateway_id . '_pay4pay_charges_fixed',
				'default'           => $settings['pay4pay_charges_fixed'],
				'custom_attributes' => array(
					'step' => 'any',
				),
			),
			array(
				'title'             => __( 'Percent charge', 'woocommerce-pay-for-payment' ),
				'type'              => 'number',
				'desc'              => __( 'Percentage of cart total to be added to payment.', 'woocommerce-pay-for-payment' ),
				'desc_tip'          => true,
				'id'                => 'woocommerce_' . $gateway_id . '_pay4pay_charges_percentage',
				'default'           => $settings['pay4pay_charges_percentage'],
				'custom_attributes' => array(
					'step'                  => 'any',
					'data-setchangehandler' => '1',
					'data-reference-name'   => 'woocommerce-pay4pay-percentage',
				),
				'class'             => 'woocommerce-pay4pay-percentage',
			),
			array(
				'title'             => __( 'Charge at least', 'woocommerce-pay-for-payment' ),
				'type'              => 'number',
				'desc'              => __( 'Minimum extra charge to be added to cart when this payment method is selected.', 'woocommerce-pay-for-payment' ),
				'desc_tip'          => true,
				'id'                => 'woocommerce_' . $gateway_id . '_pay4pay_charges_minimum',
				'default'           => $settings['pay4pay_charges_minimum'],
				'custom_attributes' => array(
					'step'                  => 'any',
					'data-dependency-notzero' => 'woocommerce-pay4pay-percentage',
				),
			),
			array(
				'title'             => __( 'Charge at most', 'woocommerce-pay-for-payment' ),
				'type'              => 'number',
				'desc'              => __( 'Maximum extra charge to be added to cart when this payment method is selected. Enter zero to disable.', 'woocommerce-pay-for-payment' ),
				'desc_tip'          => true,
				'id'                => 'woocommerce_' . $gateway_id . '_pay4pay_charges_maximum',
				'default'           => $settings['pay4pay_charges_maximum'],
				'custom_attributes' => array(
					'step'                  => 'any',
					'data-dependency-notzero' => 'woocommerce-pay4pay-percentage',
				),
			),
			array(
				'title'    => __( 'Disable on Free Shipping', 'woocommerce-pay-for-payment' ),
				'label'    => __( 'Don\'t charge this fee when free shipping is available.', 'woocommerce-pay-for-payment' ),
				'type'     => 'checkbox',
				'desc_tip' => true,
				'id'       => 'woocommerce_' . $gateway_id . '_pay4pay_disable_on_free_shipping',
				'default'  => $settings['pay4pay_disable_on_free_shipping'],
			),
			array(
				'title'    => __( 'Disable on Zero Shipping', 'woocommerce-pay-for-payment' ),
				'label'    => __( 'Don\'t charge this fee when zero shipping is available.', 'woocommerce-pay-for-payment' ),
				'type'     => 'checkbox',
				'desc_tip' => true,
				'id'       => 'woocommerce_' . $gateway_id . '_pay4pay_disable_on_zero_shipping',
				'default'  => $settings['pay4pay_disable_on_zero_shipping'],
			),
		);

		// Add tax fields if taxes are enabled
		if ( $this->are_taxes_enabled() ) {
			$form_fields = array_merge(
				$form_fields,
				array(
					array(
						'title' => __( 'Extra Charge Taxes', 'woocommerce-pay-for-payment' ),
						'type'  => 'title',
						'id'    => 'pay4pay_title_taxes',
					),
					array(
						'title'             => __( 'Taxable', 'woocommerce-pay-for-payment' ),
						'type'              => 'checkbox',
						'label'             => __( 'Payment fee is taxable', 'woocommerce-pay-for-payment' ),
						'id'                => 'woocommerce_' . $gateway_id . '_pay4pay_taxes',
						'default'           => $settings['pay4pay_taxes'],
						'custom_attributes' => array(
							'data-setchangehandler' => '1',
							'data-reference-name'   => 'woocommerce-pay4pay-taxes',
						),
						'class'             => 'woocommerce-pay4pay-taxes',
					),
					array(
						'title'             => __( 'Inclusive Taxes', 'woocommerce-pay-for-payment' ),
						'type'              => 'checkbox',
						'label'             => __( 'The payment fee is inclusive of taxes.', 'woocommerce-pay-for-payment' ),
						'desc'              => __( 'If you leave this unchecked taxes will be calculated on top of the payment fee.', 'woocommerce-pay-for-payment' ),
						'desc_tip'          => true,
						'id'                => 'woocommerce_' . $gateway_id . '_pay4pay_includes_taxes',
						'default'           => $settings['pay4pay_includes_taxes'],
						'class'             => 'pay4pay_taxes',
						'custom_attributes' => array(
							'data-dependency-notzero' => 'woocommerce-pay4pay-taxes',
						),
					),
					array(
						'title'             => __( 'Tax class', 'woocommerce-pay-for-payment' ),
						'type'              => 'select',
						'desc'              => __( 'Select the tax class applied to the extra charge.', 'woocommerce-pay-for-payment' ),
						'desc_tip'          => true,
						'id'                => 'woocommerce_' . $gateway_id . '_pay4pay_tax_class',
						'options'           => $tax_class_options,
						'default'           => $settings['pay4pay_tax_class'],
						'class'             => 'pay4pay_taxes',
						'custom_attributes' => array(
							'data-dependency-notzero' => 'woocommerce-pay4pay-taxes',
						),
					),
				)
			);
		}

		// Add "Include in calculation" fields
		$form_fields = array_merge(
			$form_fields,
			array(
				array(
					'title'             => __( 'Include in percental payment fee calculation:', 'woocommerce-pay-for-payment' ),
					'type'              => 'title',
					'id'                => 'pay4pay_title_include',
					'class'             => 'dependency-notzero-woocommerce-pay4pay-percentage',
					'custom_attributes' => array(
						'data-dependency-notzero' => 'woocommerce-pay4pay-percentage',
					),
				),
				array(
					'title'             => __( 'Fees', 'woocommerce-pay-for-payment' ),
					'type'              => 'checkbox',
					'label'             => __( 'Include fees in calculation.', 'woocommerce-pay-for-payment' ),
					'desc_tip'          => true,
					'id'                => 'woocommerce_' . $gateway_id . '_pay4pay_enable_extra_fees',
					'default'           => $settings['pay4pay_enable_extra_fees'],
					'class'             => 'pay4pay_charges_percentage',
					'custom_attributes' => array(
						'data-dependency-notzero' => 'woocommerce-pay4pay-percentage',
					),
				),
				array(
					'title'             => __( 'Coupons', 'woocommerce-pay-for-payment' ),
					'type'              => 'checkbox',
					'label'             => __( 'Include Coupons in calculation.', 'woocommerce-pay-for-payment' ),
					'desc_tip'          => true,
					'id'                => 'woocommerce_' . $gateway_id . '_pay4pay_include_coupons',
					'default'           => $settings['pay4pay_include_coupons'],
					'class'             => 'pay4pay_charges_percentage',
					'custom_attributes' => array(
						'data-dependency-notzero' => 'woocommerce-pay4pay-percentage',
					),
				),
				array(
					'title'             => __( 'Shipping', 'woocommerce-pay-for-payment' ),
					'type'              => 'checkbox',
					'label'             => __( 'Include shipping cost in calculation.', 'woocommerce-pay-for-payment' ),
					'desc_tip'          => true,
					'id'                => 'woocommerce_' . $gateway_id . '_pay4pay_include_shipping',
					'default'           => $settings['pay4pay_include_shipping'],
					'class'             => 'pay4pay_charges_percentage',
					'custom_attributes' => array(
						'data-dependency-notzero' => 'woocommerce-pay4pay-percentage',
					),
				),
			)
		);

		// Add taxes checkbox if taxes are enabled
		if ( $this->are_taxes_enabled() ) {
			$form_fields[] = array(
				'title'             => __( 'Taxes', 'woocommerce-pay-for-payment' ),
				'type'              => 'checkbox',
				'label'             => __( 'Include taxes in calculation.', 'woocommerce-pay-for-payment' ),
				'desc_tip'          => true,
				'id'                => 'woocommerce_' . $gateway_id . '_pay4pay_include_cart_taxes',
				'default'           => $settings['pay4pay_include_cart_taxes'],
				'class'             => 'pay4pay_charges_percentage',
				'custom_attributes' => array(
					'data-dependency-notzero' => 'woocommerce-pay4pay-percentage',
				),
			);
		}

		// Add section end
		$form_fields[] = array(
			'type' => 'sectionend',
			'id'   => 'pay4pay_end',
		);

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $form_fields, $current_section );
	}

	/**
	 * Output the settings
	 *
	 * @since 2.2.0
	 */
	public function output() {
		global $current_section;

		// Check if we have any payment gateways (cached)
		$payment_gateways = $this->get_payment_gateways();

		if ( empty( $payment_gateways ) ) {
			echo '<div class="notice notice-warning"><p>' . __( 'No payment gateways found. Please install and activate at least one payment gateway.', 'woocommerce-pay-for-payment' ) . '</p></div>';
			return;
		}

		// If no section selected, default to first gateway
		$section = ! empty( $current_section ) ? $current_section : array_key_first( $payment_gateways );

		// Get settings for the specific section without modifying global
		$settings = $this->get_settings( $section );

		WC_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Save settings
	 *
	 * @since 2.2.0
	 *
	 * Security Note: Both nonce verification and capability checks ('manage_woocommerce')
	 * are handled by WooCommerce's WC_Admin_Settings class at the settings page level
	 * before this method is called via the 'woocommerce_settings_save_{tab_id}' action hook.
	 * WooCommerce verifies these in the WC_Admin_Settings::save() method before triggering
	 * save actions for individual tabs.
	 */
	public function save() {
		global $current_section;

		$gateway_id = $current_section;

		// If no section selected, default to first gateway (same logic as output())
		if ( empty( $gateway_id ) ) {
			$payment_gateways = $this->get_payment_gateways();
			$gateway_id = ! empty( $payment_gateways ) ? array_key_first( $payment_gateways ) : '';
		}

		if ( empty( $gateway_id ) ) {
			WC_Admin_Settings::add_error( __( 'Could not save settings. No payment gateway found.', 'woocommerce-pay-for-payment' ) );
			return;
		}

		// Fix for Eurobank WooCommerce Payment Gateway
		$class_id = $gateway_id;
		if ( $class_id === 'wc_eurobank_gateway' ) {
			$class_id = 'eurobank_gateway';
		}

		$prefix  = 'woocommerce_' . $class_id;
		$postfix = '_settings';

		// Default WooCommerce gateways use this option name format
		$opt_name = $prefix . $postfix;

		// Try to get the WooCommerce Gateway settings with default format
		$options = get_option( $opt_name );

		// Try to get the WooCommerce Gateway settings with fallback format
		if ( $options === false ) {
			$opt_name = $class_id . $postfix;
			$options  = get_option( $opt_name );
			$prefix   = $class_id;
		}

		// Check if $options is false
		if ( $options === false ) {
			WC_Admin_Settings::add_error( __( 'Could not save settings. Payment gateway options not found.', 'woocommerce-pay-for-payment' ) );
			return;
		}

		// Get posted values with validation
		$item_title = isset( $_POST[ $prefix . '_pay4pay_item_title' ] ) ? sanitize_text_field( $_POST[ $prefix . '_pay4pay_item_title' ] ) : '';
		$charges_fixed = isset( $_POST[ $prefix . '_pay4pay_charges_fixed' ] ) ? floatval( $_POST[ $prefix . '_pay4pay_charges_fixed' ] ) : 0;

		// Validate and sanitize percentage
		$charges_percentage = isset( $_POST[ $prefix . '_pay4pay_charges_percentage' ] ) ? floatval( $_POST[ $prefix . '_pay4pay_charges_percentage' ] ) : 0;

		// Validate and sanitize minimum and maximum
		$charges_minimum = isset( $_POST[ $prefix . '_pay4pay_charges_minimum' ] ) ? floatval( $_POST[ $prefix . '_pay4pay_charges_minimum' ] ) : 0;
		$charges_maximum = isset( $_POST[ $prefix . '_pay4pay_charges_maximum' ] ) ? floatval( $_POST[ $prefix . '_pay4pay_charges_maximum' ] ) : 0;

		// Validate min/max relationship: minimum should not be greater than maximum (when both are positive)
		if ( $charges_minimum > 0 && $charges_maximum > 0 && $charges_minimum > $charges_maximum ) {
			WC_Admin_Settings::add_error( __( 'Minimum charge cannot be greater than maximum charge. Settings not saved.', 'woocommerce-pay-for-payment' ) );
			return;
		}

		// Build extra settings array
		$extra = array(
			'pay4pay_item_title'                => $item_title,
			'pay4pay_charges_fixed'             => $charges_fixed,
			'pay4pay_charges_percentage'        => $charges_percentage,
			'pay4pay_charges_minimum'           => $charges_minimum,
			'pay4pay_charges_maximum'           => $charges_maximum,
			'pay4pay_disable_on_free_shipping'  => isset( $_POST[ $prefix . '_pay4pay_disable_on_free_shipping' ] ) && $_POST[ $prefix . '_pay4pay_disable_on_free_shipping' ] === '1' ? 'yes' : 'no',
			'pay4pay_disable_on_zero_shipping'  => isset( $_POST[ $prefix . '_pay4pay_disable_on_zero_shipping' ] ) && $_POST[ $prefix . '_pay4pay_disable_on_zero_shipping' ] === '1' ? 'yes' : 'no',
			'pay4pay_taxes'                     => isset( $_POST[ $prefix . '_pay4pay_taxes' ] ) && $_POST[ $prefix . '_pay4pay_taxes' ] === '1' ? 'yes' : 'no',
			'pay4pay_includes_taxes'            => isset( $_POST[ $prefix . '_pay4pay_includes_taxes' ] ) && $_POST[ $prefix . '_pay4pay_includes_taxes' ] === '1' ? 'yes' : 'no',
			'pay4pay_tax_class'                 => isset( $_POST[ $prefix . '_pay4pay_tax_class' ] ) ? sanitize_title( $_POST[ $prefix . '_pay4pay_tax_class' ] ) : '',
			'pay4pay_enable_extra_fees'         => isset( $_POST[ $prefix . '_pay4pay_enable_extra_fees' ] ) && $_POST[ $prefix . '_pay4pay_enable_extra_fees' ] === '1' ? 'yes' : 'no',
			'pay4pay_include_shipping'          => isset( $_POST[ $prefix . '_pay4pay_include_shipping' ] ) && $_POST[ $prefix . '_pay4pay_include_shipping' ] === '1' ? 'yes' : 'no',
			'pay4pay_include_coupons'           => isset( $_POST[ $prefix . '_pay4pay_include_coupons' ] ) && $_POST[ $prefix . '_pay4pay_include_coupons' ] === '1' ? 'yes' : 'no',
			'pay4pay_include_cart_taxes'        => isset( $_POST[ $prefix . '_pay4pay_include_cart_taxes' ] ) && $_POST[ $prefix . '_pay4pay_include_cart_taxes' ] === '1' ? 'yes' : 'no',
		);

		// Merge with existing options
		$options = array_merge( $options, $extra );

		// WPML - Register strings for translation
		do_action( 'wpml_register_single_string', 'woocommerce-pay-for-payment', $class_id . ' - item title', $item_title );
		do_action( 'wpml_register_single_string', 'woocommerce-pay-for-payment', $class_id . ' - charges fixed', $charges_fixed );

		// Update option
		update_option( $opt_name, $options );

		// Success message is automatically shown by WooCommerce's settings framework
	}

	/**
	 * Enqueue admin assets
	 *
	 * @since 2.2.0
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on our settings page
		if ( 'woocommerce_page_wc-settings' !== $hook ) {
			return;
		}

		// Check capability
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Check if we're on our tab with proper sanitization
		if ( ! isset( $_GET['tab'] ) || sanitize_key( $_GET['tab'] ) !== $this->id ) {
			return;
		}

		// Enqueue the same JavaScript used for checkout settings
		wp_enqueue_script(
			'pay4pay_settings_checkout',
			plugins_url( '/js/pay4pay-settings-checkout.js', dirname( __FILE__ ) ),
			array( 'jquery', 'woocommerce_admin' ),
			PAY4PAYMENT_VERSION,
			true
		);

		// Enqueue CSS
		wp_enqueue_style(
			'pay4pay_settings_checkout',
			plugins_url( '/css/pay4pay-settings-checkout.css', dirname( __FILE__ ) ),
			array(),
			PAY4PAYMENT_VERSION
		);
	}
}

endif;
