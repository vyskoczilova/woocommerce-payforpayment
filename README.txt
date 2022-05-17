=== Pay for Payment for WooCommerce ===
Contributors: vyskoczilova, podpirate
Donate link: https://paypal.me/KarolinaVyskocilova
Tags: ecommerce, woocommerce, payment gateway, fee
Requires at least: 4.6
Tested up to: 6.0
Stable tag: 2.1.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Setup individual charges for each payment method in WooCommerce.

== Description ==

Add individual charges for each payment method as a flat rate and/or as a percentage of the cart total. The plugin first calculates the percentage rate and then adds the fixed rate on top.

You can use **placeholders** in the payment item title:

- *[FIXED_AMOUNT]*: Will print money-formatted fixed amount you entered.
- *[PERCENT_AMOUNT]*: will print out percental amount you entered
- *[CART_TOTAL]*: will print out money-formatted cart totals.
- *[MINIMUM_AMOUNT]*: will print out money-formatted minimum amount you entered when calculating percentage fee.
- *[MAXIMUM_AMOUNT]*: will print out money-formatted maximum amount you entered when calculating percentage fee.
- Example: `Payment Fee ([FIXED_AMOUNT] + [PERCENT_AMOUNT]% of [CART_TOTAL])`

Requires at least WooCommerce 2.6, compatible with WooCommerce 3.2+ (recommended). The support for WC 2.6 will be dropped soon.

= Features =
- **Fixed charge and/or a percentage** of cart total
- Possibility to **disable for free/zero shipping**
- **Plugin API**. See [GitHub](https://github.com/vyskoczilova/woocommerce-payforpayment) for details.

= Compatibility =
- **Currently not compatible with [WooCommerce Stripe Payment Gateway](https://wordpress.org/plugins/woocommerce-gateway-stripe/)** since it's React powered and I can't hook in. Use [Payment Plugins for Stripe WooCommerce](https://wordpress.org/plugins/woo-stripe-payment/) instead for now, waiting for their team to resolve the problem.
- **WPML** (see [FAQ](https://wordpress.org/plugins/woocommerce-pay-for-payment#faq))
- [WooCommerce Price Based on Country for WooCommerce](https://wordpress.org/plugins/woocommerce-product-price-based-on-countries/) & PRO
- [Multi Currency for WooCommerce](https://wordpress.org/plugins/woo-multi-currency/)  & PRO
- [WooCommerce Multi-Currency](https://woocommerce.com/products/multi-currency/) when the store currency is USD
- [YayCurrency PRO](https://yaycommerce.com/yaycurrency-woocommerce-multi-currency-switcher/)
- [WOOCS](https://wordpress.org/plugins/woocommerce-currency-switcher/)

= Limitations =
- It seems that Mercadopago gateway is not handling WC_Fee correctly. Get in touch with Mercadopago support (and I'm happy to help them fix the issue)
- Better not use it with PayPal. (Legal issue, see FAQ as well.)
- Doesn't work on "Pay for order" pages (manually created orders or canceled payments), because of [WC limitations](https://github.com/woocommerce/woocommerce/issues/17794)

= Special Credits =
- to [Jörn Lund (@podpirate)](https://github.com/mcguffin/woocommerce-payforpayment) who have developed this plugin and abandoned it in 2016.

== Installation ==

Just follow the standard [WordPress plugin installation procedere](http://codex.wordpress.org/Managing_Plugins).

== Frequently asked questions ==

= Can I use it with PayPal? =

No. PayPal does not permit charging your customer for using PayPal. This is a legal issue rather than a technical one.
See [PayPal User Agreement](https://www.paypal.com/webapps/mpp/ua/useragreement-full?country.x=US&locale.x=en_US#4), > "4.6 No Surcharges" for details.
You have been warned.

= WPML - How to translate? =
If you need to localize Fee title and Fixed charge go to go to WPML > String translation and look for following type of text domain: `woocommerce-pay-for-payment` and than you should find a strings with name "{payment-method-slug} - charges fixed" or "{payment-method-slug} - item title". See the second screenshot.

= Can't to setup my payment requirements in the user interface. The option I need is missing. =

The plugin user interface only offers either a fixed amout or a percentage of the carts subtotal.
If you need to implement more complex calcuations like 'no charges for orders above 100 Bucks' or '2% of cart subtotal but at least 2 Bucks',
you'll have to use one of the filters. See [Plugin API](https://github.com/vyskoczilova/woocommerce-payforpayment#plugin-api) for details.

<code>woocommerce_pay4pay_apply</code> specifies if a charge will be applied.

<code>woocommerce_pay4pay_applyfor_{$payment_gateway_id}</code> specifies if a charge will be applied on a certain payment method.

<code>woocommerce_pay4pay_{$payment_gateway_id}_amount</code> allows you to alter the amount of the charge being added.


= I want to use the latest files. How can I do this? =

Use the GitHub Repo rather than the WordPress Plugin. Do as follows:

1. If you haven't already done: [Install git](https://help.github.com/articles/set-up-git)

2. In the console cd into Your 'wp-content/plugins´ directory

3. type `git clone https://github.com/vyskoczilova/woocommerce-payforpayment` or better type `git fork https://github.com/vyskoczilova/woocommerce-payforpayment`

4. If you want to update to the latest files (be careful, might be untested on Your WP-Version) type `git pull´.

= I found a bug. Where should I post it? =

I personally prefer GitHub, to keep things straight. The plugin code is here: [GitHub](https://github.com/vyskoczilova/woocommerce-payforpayment)
But you may use the WordPress Forum as well.

= I found a bug and fixed it. How can I contribute? =

Either post it on [GitHub](https://github.com/vyskoczilova/woocommerce-payforpayment) or—if you are working on a cloned repository—send me a pull request.


== Screenshots ==

1. User interface. You can find this in every payment gateway configuration.
1. How to set up of WPML in String translation module.


== Changelog ==

= 2.1.7 (2022-05-17) =

* Update: Add support for WOOCS converter.

= 2.1.6 (2022-05-13) =

* Fix: Don't deactivate the plugin even when the WooCommerce is not active (keep a notice only).

= 2.1.5 (2022-03-21) =

* Feature: Add `[MINIMUM_AMOUNT]` and `[MAXIMUM_AMOUNT]` placeholders to fee title

= 2.1.4 (2021-10-04) =

* Fix: Added compatibility with [Eurobank WooCommerce Payment Gateway](https://el.wordpress.org/plugins/woo-payment-gateway-for-eurobank/) plugin

= 2.1.3 (2021-09-16) =

* Fix - WPML exchange rates (apply filter `wcml_raw_price_amount`)
* Fix - tax class when taxes by cart items is on - applies the highest used tax. Thanks to [morvy](https://github.com/morvy)

= 2.1.2 (2021-08-19) =

* Fix - cost rounding (use `wc_get_rounding_precision()`) - thanks to [morvy](https://github.com/morvy)

= 2.1.1 (2021-08-17) =

* Fix - check if array passe in gateway settings (fixes [Mercadopago error](https://wordpress.org/support/topic/fatal-error-mercado-pago-compatibility/))

= 2.1.0 (2021-07-30) =

* New filter introduced `woocommerce_pay4pay_get_current_gateway_settings` ([#61](https://github.com/vyskoczilova/woocommerce-payforpayment/pull/61))
* PHP 8.0 - Unsupported operand types fix ([#63](https://github.com/vyskoczilova/woocommerce-payforpayment/issues/63))

= 2.0.19 (2021-06-02) =

* Change the plugin name from "WooCommerce Pay for Payment" to "Pay for Payment for WooCommerce" since the WC claims the trademark.

= 2.0.18 (2021-02-22) =
* Fix: add option for Payment tax based on cart items [issue](https://wordpress.org/support/topic/payment-fee-tax-class-based-on-product-tax-class/)
* Tweak: Reflect the number of decimals in WC currency settings instead of rounding to two digits.

= 2.0.17 (2020-10-15) =
* Added compatibility with [Multi Currency for WooCommerce](https://wordpress.org/plugins/woo-multi-currency/)

= 2.0.16 (2020-08-10) =
* FIX: `Unsupported operand types` when adding settings to a payment (fixes Mercadopago fatal error)
* FIX: Escape tax related settings.

= 2.0.15 (2020-05-23) =
* Slovak translation (thanks to [Roman Velocký](https://gandalf.sk/))
* Fix not loading tax classes in the payment settings
* Support for [WooCommerce Multi-Currency](https://woocommerce.com/products/multi-currency/) when store currency is USD

= 2.0.14 (2020-01-16) =
* Added a compatibility with [Price Based on Country for WooCommerce](https://wordpress.org/plugins/woocommerce-product-price-based-on-countries/) in [#51](https://github.com/vyskoczilova/woocommerce-payforpayment/pull/51) thanks to [Oscar Gare](https://github.com/oscargare).

= 2.0.13.3 (2019-09-04) =
* Fix: Compatibility with WC 3.7.0 - Move saving settings to `wp_loaded` as WooCommerce does in [PR #23091](https://github.com/woocommerce/woocommerce/pull/23091)

= 2.0.12 (2018-02-03) =
* Fix: don't add the fee when order and shipping amount is 0, see 2.0.11 for more details.

= 2.0.11 (2018-02-03) =
* Fix: don't add the fee when order amount is 0. More details [here](https://wordpress.org/support/topic/total-order-0-bug/).
* Updated links in readme and plugin settings.
* Github: implemented [Probot](https://probot.github.io/apps/stale/)

= 2.0.10 (2018-11-14) =
* Critical fix of 2.0.9 bug - COD could be anabled as well for "any" shipping method type (accidentaly slipped out from 2.0.9)

= 2.0.9 (2018-11-14) =
* Fix: Check if COD is enabled for current shipping method otherwise don't add the fee.
* Update: WC tested up to 3.5.1 and WP 5.0

= 2.0.8 (2018-10-17) =
- Fix: Check if logged in user is VAT exempt
- Update: Return back translations (if installed from FTP, the WP.org translations are loaded after an update/manual install)
- Update: WC tested up to

= 2.0.7 (2018-02-10) =
- Update: WC tested up to
- Fix: added upport for gateways without default option format ([#33](https://github.com/vyskoczilova/woocommerce-payforpayment/pull/33)) and fixed [#20](https://github.com/vyskoczilova/woocommerce-payforpayment/issues/20), thanks to [David de Boer](https://github.com/davdebcom)

= 2.0.6 (2017-10-29) =
- Fix: WC compatibility issue (solved in 2.0.4), compatible with both versions of WC
- Fix: problem with WooCommerce Multilingual [#24](https://github.com/vyskoczilova/woocommerce-payforpayment/issues/24), only in WC 3.2+

= 2.0.5 (2017-10-29) =
- Fix: doubled fee in total
- Fix: negative fee percentage

= 2.0.4 (2017-10-22) =
- Fix: *WC_Cart->discount_total argument is deprecated* error (by [@bolint](https://github.com/vyskoczilova/woocommerce-payforpayment/issues/25))
- Fix: backwards compatibility to discount_total
- Added banner & icon image to the WP repository (by [Dušan Konečný](http://abmanufaktura.cz))

= 2.0.3 (2017-10-19) =
- Fix: Compatibility issues with WC version 3.2 (thanks to [Peter J. Herrel](https://github.com/vyskoczilova/woocommerce-payforpayment/pull/23))

= 2.0.2 (2017-07-31) =
- Feature: Inner compatibility with WPML - fee title and fixed charges can be localized within "String translation" under "woocommerce-pay-for-payment" domain name. Removed wpml-config.xml.
- Added: Italian and Dutch localization
- Misc: Code tweaks and fixes ([#16](https://github.com/vyskoczilova/woocommerce-payforpayment/pull/16))
- Fix: $fragment_refresh is not defined ([#13](https://github.com/vyskoczilova/woocommerce-payforpayment/issues/13))
- Fix: Turn off plugin, if WC is not active
- Fix: Undefined index: woocommerce_cod_pay4pay_tax_class ([#12](https://github.com/vyskoczilova/woocommerce-payforpayment/issues/12))

= 2.0.1 (2017-05-22) =
- Feature: check WC version, minimum version 2.6 (by [@oerdnj](https://github.com/vyskoczilova/woocommerce-payforpayment/pull/8))
- Fix: translatable pay4pay_charges_fixed (WPML support)
- Fix: disable on free shipping (for WC 2.6+)
- Fix: disable on zero shipping - added missing settings field

= 2.0.0 (2017-05-15) =
- plugin overtaken by [@vyskoczilova](https://github.com/vyskoczilova/woocommerce-payforpayment)
- fully compatible with WC 3.0+
- Added: Czech localization
- Added: Disable on zero shipping (by [@panvagenas](https://github.com/mcguffin/woocommerce-payforpayment/pull/35))
- Fix: support for WC 2.6+ (by [@oerdnj](https://github.com/mcguffin/woocommerce-payforpayment/pull/42))
- Fix: tax_rates notice (by [@javierrguez](https://wordpress.org/support/topic/error-wc_cart-tax/))

= 1.3.7 =
- l10n: change textdomain to 'woocommerce-pay-for-payment' to make it work with translate.wordpress.org

= 1.3.6 =
- Fix: compatibility with Amazon Payments and also with Woocommerce 2.4
- Fix: PHP Warning on shopping basket

= 1.3.5 =
- Fix: make it work with stripe for woocommerce by Stephen Zuniga

= 1.3.4 =
- Code Refactoring: set plugin textdomain to plugin slug
- Translations: Minor correction in español and german translations

= 1.3.3 =
- Feature: Minimum and maximum charges.

= 1.3.2 =
- Feature: Deactivate if WooCommerce version is below requirement.
- Fix: Missing Taxes

= 1.3.1 =
- Fix Admin: Payment gateway Class not found (may occur with 3rd party gateways)
- Fix: textdomain loading
- Update turkish localisation

= 1.3.0 =
- Feature: Enhanced UI
- Feature: Select tax class to be applied to payment fee
- Feature: Select if cart taxes will be included on payment fee calculation
- Feature: Placeholders in fee title.
- Fixes: completely repeat all WooCommerce tax and fee calculation steps after payment fee has been added.

= 1.2.5 =
- Fix: incorrect fee calculation.

= 1.2.3 =
- Fix: Safely Restrict payment fee to 2 Decimals.

= 1.2.2 =
- Fix: [Calculate taxes](http://wordpress.org/support/topic/cant-use-the-plugin-generate-false-amount-in-adding)
- Fix: cart contents taxes and shipping taxes included into fee calculation
- Refactoring: Discard cart_has_fee() check, as it is already done by WooCommerce

= 1.2.1 =
- Feature: [Calculate custom fee](http://wordpress.org/support/topic/not-calculating-custom-fees)

= 1.2.0 (2014-08-09) =
- Feature: add option to disable payment fee when free shipping is selected
- Feature: add pay4pay column in WooCommerce checkout settings
- Plugin-API: add filter `woocommerce_pay4pay_apply`
- Code Refactoring: separated admin UI from frontend to keep things lean.
- Code Refactoring: use function <code>WC()</code> (available since WC 2.1) in favour of <code>global $woocommerce</code>.
- Compatibility: requires at least WC 2.1.x,

= 1.1.1 =
- Added wpml configuration file to keep compatibility with http://wordpress.org/plugins/woocommerce-multilingual/

= 1.1.0 =
- Added option to include shipping cost in fee calculation
- Fixed issue where malformed amounts where sent to external payment services in WC 2.1.6

= 1.0.2 =
- Fixed an issue where Pay4Pay options did not show up after saving checkout settings in WC 2.1.0
- Updated turkish translation ([Thanks a lot!](https://www.transifex.com/accounts/profile/TRFlavourart/))

= 1.0.1 =
- Fix plugin URL

= 1.0.0 =
- Initial release


== Upgrade Notice ==
Do you use WPML? Check "String translation" for localization of fee title and fixed charges, wpml-config.xml have been removed.
