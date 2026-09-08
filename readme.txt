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
  names them — "Germany" in an English shop, "Deutschland" in a German one — not the zone name you
  typed into wp-admin. Postcodes limit a zone rather than name it, so they are left out.
* **Honest about what it cannot price.** A method priced by weight or item count has no order-value
  rule to read, so its cell shows a dash and says why, instead of quietly printing "Free shipping".
* **Correct in any currency.** Range boundaries follow the shop's price precision, so zero-decimal
  currencies such as HUF and JPY read correctly.
* **Cash on delivery, optionally.** Give it a phrase to match in the method title and it splits the
  output into a prepaid table and a cash-on-delivery table.

= Shortcode =

`[dtfs_shipping_table]`

Attributes: `zone`, `zone_headings`, `cod_prefix`, `show_disabled`, `include_rest_of_world`. All of
them are documented under **WooCommerce → Delivery Table**.

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

No cost rule in that shipping method covers that order value range — usually because the method is
priced by weight rather than by order value. The dash says so honestly instead of guessing.

= Can I show only one zone? =

Yes: `[dtfs_shipping_table zone="Europe"]`, by zone name or by zone id.

= Are prices shown with tax? =

Yes, gross, including shipping VAT.

= Can I change the markup? =

Copy a file from the plugin's `templates/` folder into
`wp-content/themes/your-theme/delivery-table-for-flexible-shipping/` and edit it there.

== Screenshots ==

1. The delivery price table on a storefront page.
2. The Delivery Table block and its inspector controls.
3. The documentation screen under WooCommerce.

== Changelog ==

= 1.0.0 =
* First release.

== Upgrade Notice ==

= 1.0.0 =
First release.
