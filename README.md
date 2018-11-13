WooCommerce Pay for Payment
===========================

[![plugin version](https://img.shields.io/wordpress/plugin/v/woocommerce-pay-for-payment.svg)](https://wordpress.org/plugins/woocommerce-pay-for-payment)

About
-----
Add individual charges for each payment method as a flat rate and/or as a percentage of the cart total.
The plugin first calculates the percentage rate and then adds the fixed rate on top.
Coupons are not supported. (Sorry guys. I tried, but no way.)

You will find a stable version in [WordPress plugin directory](http://wordpress.org/plugins/woocommerce-pay-for-payment/).

Previous versions have been created by [Jörn Lund](https://github.com/mcguffin), who abandoned this project due to a pile of other projects in 2016. See the previous [GitHub](https://github.com/vyskoczilova/woocommerce-payforpayment-old) for details. The new version is maintained by Karolína Vyskočilová since 2017.

Unreleased updates
------------------


Plugin API
----------

##### Filter `woocommerce_pay4pay_{$current_gateway_id}_amount`: #####
Applied to the payment gateway fee before it is added to woocomerce' cart. If you work with subtotal, [check how to get it](https://github.com/vyskoczilova/woocommerce-payforpayment#how-to-get-subtotal).

*Example:*

	function my_pay4pay_amount( $amount , $calculation_base , $current_payment_gateway , $taxable , $include_taxes , $tax_class ) {
		if ( my_customer_complained_too_much() )
			return $amount * 10;
		else
			return $amount;
	}
	$current_gateway_id = 'cod';
	add_filter( "woocommerce_pay4pay_{$current_gateway_id}_amount", 'my_pay4pay_amount' , 10 , 6 );


##### Filter `woocommerce_pay4pay_apply`: #####
Handle if a payment fee is applied. If you work with subtotal, [check how to get it](https://github.com/vyskoczilova/woocommerce-payforpayment#how-to-get-subtotal).

*Example:*

	function my_pay4pay_handle_christmas( $do_apply , $amount , $calculation_base , $current_payment_gateway ) {
		if ( today_is_christmas() )
			return false;
		else
			return $do_apply;
	}
	add_filter( "woocommerce_pay4pay_apply", 'my_pay4pay_handle_christmas' , 10 , 4 );



##### Filter `woocommerce_pay4pay_applyfor_{$current_gateway_id}`: #####
Handle if a payment fee on a specific payment method should be applied. If you work with subtotal, [check how to get it](https://github.com/vyskoczilova/woocommerce-payforpayment#how-to-get-subtotal).

*Example:*

	function my_pay4pay_apply( $do_apply , $amount , $calculation_base , $current_payment_gateway ) {
		if ( my_customer_is_a_nice_guy() )
			return false;
		else
			return $do_apply;
	}
	$current_gateway_id = 'cod';
	add_filter( "woocommerce_pay4pay_applyfor_{$current_gateway_id}", 'my_pay4pay_apply' , 10 , 4 );

#### FAQ ####

##### How to get subtotal? #####
Within your filter use following lines:

	$cart = WC()->cart;	
	if ( wc_prices_include_tax() ) {
		$subtotal = intval( $cart->subtotal );
	} else {
		$subtotal = intval( $cart->subtotal_ex_tax );
	}