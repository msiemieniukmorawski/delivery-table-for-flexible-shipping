=== Delivery Table for Flexible Shipping ===
Contributors: msiemieniukmorawski
Tags: woocommerce, shipping, delivery, flexible shipping, shipping table
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turns your Flexible Shipping zones and cost rules into a delivery price table. One shortcode, one block, nothing to configure twice.

== Description ==

Flexible Shipping already knows what every carrier costs at every order value. This plugin reads that
configuration and presents it to your customers, so the same numbers never have to be maintained in
two places.

Order value ranges are the columns, your shipping methods are the rows. Change a rule in Flexible
Shipping and the table follows on the next page load.

= What it does well =

* **Columns that line up.** Boundaries are the union of every price change and every free shipping
  threshold across the methods shown, so two carriers that change price at different amounts still
  produce one readable table.
* **Headings in your customers' language.** Zone headings show the zone's regions as WooCommerce
  names them, so an English shop reads "Germany" where a German one reads "Deutschland". That is the
  region, not the zone name you typed into wp-admin. Postcodes limit a zone rather than name it, so
  they are left out.
* **One region, one answer.** Shops often split a country across several zones: a courier zone, and
  a narrower zone for certain postcodes. "What does delivery to Germany cost?" is one question, so
  those zones are gathered under one heading, in the order you arranged them, however far apart they
  sit in wp-admin. Each region also gets an anchor such as `#dtfs-region-de`, so you can link
  straight to it from anywhere on your site.
* **Every method in the zone, Flexible Shipping or not.** A plain WooCommerce flat rate,
  local pickup or free shipping method is priced too, and so are Flexible Shipping's own rules when
  they are switched on for such a method. A flat rate written as a formula like `10 + (2 * [qty])`
  depends on the basket rather than the order value, so it stays honest and shows a dash.
* **Honest about what it cannot price.** A method priced by weight or item count has no order-value
  rule to read, so its cell shows a dash and says why, instead of quietly printing "Free shipping".
  A price that holds only when a weight condition or a coupon also applies is marked with an
  asterisk rather than presented as final.
* **Correct in any currency.** Range boundaries follow the shop's price precision, so zero-decimal
  currencies such as HUF and JPY read correctly.
* **Cash on delivery, optionally.** Give it a phrase to match in the method title and it splits the
  output into a prepaid table and a cash-on-delivery table.

= Shortcode =

`[dtfs_shipping_table]`

Attributes: `zone`, `zone_headings`, `tax`, `cod_prefix`, `show_disabled`, `include_rest_of_world`.
All of them are documented under **WooCommerce → Delivery Table**.

= Block =

The **Delivery Table** block renders the same table, with the zone, the heading mode and the
cash-on-delivery split in the block inspector.

= For developers =

* `dtfs_shipping_table( $args, $echo )` and `dtfs()` for the service container
* `dtfs_booted` action
* Every storefront view under `templates/` is overridable from your theme, WooCommerce-style

== Installation ==

1. Install and activate WooCommerce and Flexible Shipping by WP Desk.
2. Upload this plugin and activate it.
3. Add `[dtfs_shipping_table]` to a page, or insert the Delivery Table block.

== Frequently Asked Questions ==

= Do I have to configure shipping prices again? =

No, and you cannot: the plugin has no price settings. It only reads your existing Flexible Shipping
zones and cost rules.

= A cell shows a dash instead of a price. Why? =

No cost rule in that shipping method covers that order value range, usually because the method is
priced by weight rather than by order value. The dash says so honestly instead of guessing.

= Can I show only one zone? =

Yes: `[dtfs_shipping_table zone="Europe"]`, by zone name or by zone id.

= Are prices shown with tax? =

By default the table follows the WooCommerce setting "Display prices during cart and checkout", so it
matches the rest of your shop. Force it either way with `[dtfs_shipping_table tax="incl"]` or
`tax="excl"`.

= I have two zones covering the same country. Will the table show them twice? =

They are shown once, under one heading, one table after the other. That is what a customer asking
"how much is delivery to Germany?" needs. A zone that covers several countries at once keeps its own
combined heading instead of being split up.

= Can I link to one region from elsewhere on my site? =

Yes. Every region carries an anchor built from its country codes: `#dtfs-region-de` for Germany,
`#dtfs-region-de-fr` for a zone covering Germany and France. Link to
`/delivery/#dtfs-region-de` and the browser jumps to that table.

= Does it work with WooCommerce's own shipping methods? =

Yes. Flat rate, local pickup and free shipping are read from their own settings, and Flexible
Shipping's rules are used instead whenever they are enabled for one of those methods. A flat rate
whose cost is a formula rather than a fixed amount cannot be stated per order value, so it shows a
dash rather than a number that would be wrong.

= Can I change the markup? =

Copy a file from the plugin's `templates/` folder into
`wp-content/themes/your-theme/delivery-table-for-flexible-shipping/` and edit it there.

== Screenshots ==

1. Two regions on one storefront page: order value ranges as columns, shipping methods as rows, free
   shipping where it applies and an asterisk where the price depends on more than the order value.
2. The Delivery Table block: zone, headings, tax and the cash-on-delivery split in the inspector.
3. WooCommerce → Delivery Table: every shortcode attribute with an example.
4. WooCommerce → Delivery Table: how to read the table, and the PHP API for developers.

== Changelog ==

= 1.0.0 =
* First release.

== Upgrade Notice ==

= 1.0.0 =
First release.
