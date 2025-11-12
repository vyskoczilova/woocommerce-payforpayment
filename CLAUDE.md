# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Pay for Payment for WooCommerce is a WordPress plugin that adds individual charges (fixed and/or percentage-based) for each payment method in WooCommerce. The plugin calculates percentage fees first, then adds fixed fees on top.

**Key Constraints:**
- Not currently compatible with WooCommerce Block Checkout (shortcode checkout required)
- Not compatible with WooCommerce Stripe Payment Gateway (React-based)
- Minimum WooCommerce version: 2.6 (WC 3.2+ recommended)
- HPOS (High-Performance Order Storage) compatible
- PHP 8.0+ supported

## Architecture

### Plugin Structure

The plugin follows a class-based singleton pattern with the following core components:

**Main Entry Point:** `woocommerce-payforpayment.php`
- Checks for WooCommerce dependency
- Initializes core class and integrations
- Declares HPOS compatibility

**Core Classes (in `/inc/`):**

1. **`Pay4Pay` (class-pay4pay.php)** - Main plugin logic
   - Singleton pattern (`Pay4Pay::instance()`)
   - Handles fee calculation in `calculate_pay4payment()` at line 94
   - Fee calculation base logic at lines 160-186
   - Tax handling including VAT exemption at lines 141-148
   - Adds fees to cart via `add_pay4payment()` at line 79
   - Auto-updates checkout on payment method change via JavaScript

2. **`Pay4Pay_Admin` (class-pay4pay-admin.php)** - Admin settings
   - Adds settings fields to payment gateway configuration pages
   - Handles saving payment gateway settings with `update_payment_options()` at line 240
   - Manages payment gateway table columns in WooCommerce settings
   - Enqueues admin assets for checkout settings page

**Currency Integration Classes:**
- `Pay4Pay_Price_Based_Country` - Price Based on Country plugin integration
- `Pay4Pay_WCML` - WPML Multi-Currency integration
- `Pay4Pay_Woocommerce_Multicurrency` - WooCommerce Multi-Currency integration
- `Pay4Pay_Woo_Multi_Currency` - Multi Currency for WooCommerce integration
- `Pay4Pay_WOOCS` - WOOCS currency switcher integration

All integration classes hook into `woocommerce_pay4pay_charges_*` filters to convert prices.

### Fee Calculation Flow

1. Triggered on `woocommerce_cart_calculate_fees` (WC 3.2+) or `woocommerce_calculate_totals` (WC < 3.2)
2. `calculate_pay4payment()` determines current payment gateway via `get_current_gateway()` (line 289)
3. Retrieves gateway settings via `get_current_gateway_settings()` (line 311)
4. Checks conditions: free shipping, zero shipping, COD shipping method restrictions
5. Calculates base amount from cart (can include shipping, fees, coupons, taxes)
6. Applies percentage fee to base, then adds fixed fee
7. Applies min/max limits if percentage fee is used
8. Handles tax calculation (inclusive vs exclusive) at lines 227-255
9. Applies filters for customization
10. Adds fee to cart via `WC()->cart->add_fee()`

### Filter API

The plugin provides extensive filters for customization (see README.md lines 34-93):

- `woocommerce_pay4pay_{$gateway_id}_amount` - Modify final fee amount before adding to cart
- `woocommerce_pay4pay_apply` - Control if any payment fee should be applied
- `woocommerce_pay4pay_applyfor_{$gateway_id}` - Control if specific gateway fee should be applied
- `woocommerce_pay4pay_get_current_gateway_settings` - Modify gateway settings before calculation
- `woocommerce_pay4pay_charges_fixed` - Modify fixed charge amount
- `woocommerce_pay4pay_charges_percentage` - Modify percentage charge
- `woocommerce_pay4pay_charges_minimum` - Modify minimum fee (also used by currency integrations)
- `woocommerce_pay4pay_charges_maximum` - Modify maximum fee (also used by currency integrations)

All filters receive: `$amount`, `$calculation_base`, `$current_payment_gateway`, and optionally `$taxable`, `$include_taxes`, `$tax_class`.

### Settings Storage

Payment fee settings are stored in each payment gateway's settings array with the `pay4pay_` prefix. Settings are retrieved via `get_option('woocommerce_{gateway_id}_settings')` or fallback format `{gateway_id}_settings`.

Default settings defined in `Pay4Pay::get_default_settings()` at line 20.

### Admin JavaScript

`js/pay4pay-settings-checkout.js` handles conditional field visibility in payment gateway settings based on:
- Whether percentage fee is set (shows/hides min/max fields)
- Whether taxes are enabled (shows/hides tax-related fields)

Uses `data-dependency-notzero` custom attributes for field dependencies.

## Development

### File Locations

- **PHP Classes:** `/inc/`
- **JavaScript:** `/js/`
- **CSS:** `/css/`
- **Translations:** `/languages/`

### Testing

Since this is a WordPress plugin that integrates with WooCommerce:

1. Test in a WordPress development environment with WooCommerce active
2. Test with multiple payment gateways enabled
3. Test fee calculations with various cart contents, shipping methods, and tax settings
4. Test with different WooCommerce versions (2.6+, 3.2+, latest)
5. Test currency integration plugins if making changes to currency handling
6. Test with and without tax calculations enabled
7. Test WPML string translation if modifying translatable strings

### WPML Integration

When modifying item titles or fixed charges, register strings for translation:
```php
do_action('wpml_register_single_string', 'woocommerce-pay-for-payment', $gateway_id.' - item title', $item_title);
do_action('wpml_register_single_string', 'woocommerce-pay-for-payment', $gateway_id.' - charges fixed', $charges_fixed);
```

Retrieve translations:
```php
apply_filters('wpml_translate_single_string', $value, 'woocommerce-pay-for-payment', $gateway_id.' - item title')
```

### Deployment

The plugin is deployed to WordPress.org using GitHub Actions (see `.github/workflows/`).

## Important Notes

- The plugin uses WooCommerce's fee API (`WC()->cart->add_fee()`) which is tax-exclusive by default
- Tax-inclusive fees are converted to tax-exclusive before adding (lines 227-255 in class-pay4pay.php)
- The `inherit` tax class applies the highest tax rate from cart items (lines 230-246)
- Percentage calculation can include/exclude shipping, fees, coupons, and taxes based on settings
- Gateway settings with special prefixes (e.g., Eurobank) have workarounds in `update_payment_options()` at line 247
- Checkout auto-updates on payment method change via inline JavaScript in `print_autoload_js()` at line 66
