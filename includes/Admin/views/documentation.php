<?php

/**
 * The plugin's only admin screen.
 *
 * Required directly by {@see \MSM\DeliveryTable\Admin\DocumentationPage} — this
 * is wp-admin markup and is deliberately not theme-overridable.
 */

declare(strict_types=1);

use MSM\DeliveryTable\Blocks\DeliveryTableBlock;
use MSM\DeliveryTable\Shortcodes\DeliveryTableShortcode;

defined('ABSPATH') || exit;

$dtfs_tag = DeliveryTableShortcode::TAG;

/**
 * Renders a table of shortcode attributes.
 *
 * @param array<string, array{0: string, 1: string}> $attributes name => [description, example]
 */
$dtfs_attributes_table = static function (array $attributes): void {
    echo '<table class="widefat striped dtfs-doc-table"><thead><tr>';
    echo '<th scope="col">' . esc_html__('Attribute', 'delivery-table-for-flexible-shipping') . '</th>';
    echo '<th scope="col">' . esc_html__('What it does', 'delivery-table-for-flexible-shipping') . '</th>';
    echo '<th scope="col">' . esc_html__('Example', 'delivery-table-for-flexible-shipping') . '</th>';
    echo '</tr></thead><tbody>';

    foreach ($attributes as $name => [$description, $example]) {
        printf(
            '<tr><td><code>%s</code></td><td>%s</td><td><code>%s</code></td></tr>',
            esc_html($name),
            esc_html($description),
            esc_html($example)
        );
    }

    echo '</tbody></table>';
};

?>
<div class="wrap dtfs-admin">
	<h1><?php esc_html_e('Delivery Table for Flexible Shipping', 'delivery-table-for-flexible-shipping'); ?></h1>

	<p class="dtfs-doc-intro">
		<?php
		esc_html_e(
			'This plugin reads your Flexible Shipping zones and cost rules and turns them into a delivery price table. There is nothing to configure here — change a rule in Flexible Shipping and the table follows.',
			'delivery-table-for-flexible-shipping'
		);
		?>
	</p>

	<h2><?php esc_html_e('How to place the table', 'delivery-table-for-flexible-shipping'); ?></h2>

	<p>
		<?php
		printf(
			/* translators: 1: shortcode, 2: block name. */
			esc_html__('Use the shortcode %1$s, or add the "Delivery Table" block (%2$s) in the editor. Both render the same table through the same code.', 'delivery-table-for-flexible-shipping'),
			'<code>[' . esc_html($dtfs_tag) . ']</code>',
			'<code>' . esc_html(DeliveryTableBlock::NAME) . '</code>'
		);
		?>
	</p>

	<p>
		<?php
		esc_html_e(
			'Columns are order value ranges, built from every price change and every free shipping threshold across the methods in the table. Rows are your shipping methods.',
			'delivery-table-for-flexible-shipping'
		);
		?>
	</p>

	<?php
	$dtfs_attributes_table([
		'zone' => [
			__('Shipping zone name or id. Leave empty to render a table for every zone.', 'delivery-table-for-flexible-shipping'),
			'[' . $dtfs_tag . ' zone="Europe"]',
		],
		'zone_headings' => [
			__('Zone headings: auto (only when several zones are shown), show, or hide.', 'delivery-table-for-flexible-shipping'),
			'[' . $dtfs_tag . ' zone_headings="show"]',
		],
		'tax' => [
			__('How to show tax: auto (follow the WooCommerce cart setting), incl (always add shipping VAT), excl (always net).', 'delivery-table-for-flexible-shipping'),
			'[' . $dtfs_tag . ' tax="incl"]',
		],
		'cod_prefix' => [
			__('Split the output into a prepaid table and a cash-on-delivery table by matching this text in the method title. Empty means one table.', 'delivery-table-for-flexible-shipping'),
			'[' . $dtfs_tag . ' cod_prefix="Cash on delivery"]',
		],
		'show_disabled' => [
			__('Include shipping methods that are switched off. Default: no.', 'delivery-table-for-flexible-shipping'),
			'[' . $dtfs_tag . ' show_disabled="1"]',
		],
		'include_rest_of_world' => [
			__('Also render the implicit "Locations not covered by your other zones" zone.', 'delivery-table-for-flexible-shipping'),
			'[' . $dtfs_tag . ' include_rest_of_world="yes"]',
		],
	]);
	?>

	<h2><?php esc_html_e('How to read the table', 'delivery-table-for-flexible-shipping'); ?></h2>

	<table class="widefat striped dtfs-doc-table">
		<tbody>
			<tr>
				<td><strong><?php esc_html_e('Zone headings', 'delivery-table-for-flexible-shipping'); ?></strong></td>
				<td>
					<?php
					esc_html_e(
						'Headings show the zone regions translated into the storefront language — "Germany" in an English shop, "Deutschland" in a German one — rather than the zone name you typed in wp-admin. Postcodes limit a zone rather than name it, so they are left out.',
						'delivery-table-for-flexible-shipping'
					);
					?>
				</td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e('A dash instead of a price', 'delivery-table-for-flexible-shipping'); ?></strong></td>
				<td>
					<?php
					esc_html_e(
						'No cost rule in that method covers that order value — for example when the method is priced by weight rather than by order value. The table says so instead of guessing.',
						'delivery-table-for-flexible-shipping'
					);
					?>
				</td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e('An asterisk after a price', 'delivery-table-for-flexible-shipping'); ?></strong></td>
				<td>
					<?php
					esc_html_e(
						'The amount is right for that order value, but the same Flexible Shipping rule also depends on something the table cannot show - weight, item count, or a free shipping coupon. Hover the asterisk for the detail.',
						'delivery-table-for-flexible-shipping'
					);
					?>
				</td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e('Tax', 'delivery-table-for-flexible-shipping'); ?></strong></td>
				<td>
					<?php
					esc_html_e(
						'By default the table follows the WooCommerce setting "Display prices during cart and checkout", so a gross-priced shop gets gross prices and a net-priced shop gets net ones. Override it per table with the tax attribute.',
						'delivery-table-for-flexible-shipping'
					);
					?>
				</td>
			</tr>
		</tbody>
	</table>

	<h2><?php esc_html_e('For developers', 'delivery-table-for-flexible-shipping'); ?></h2>

	<h3><?php esc_html_e('Template function', 'delivery-table-for-flexible-shipping'); ?></h3>

	<pre class="dtfs-doc-code"><?php
	echo esc_html(
		'// Anywhere in a theme template.' . "\n"
		. 'dtfs_shipping_table( [ "zone" => "Europe", "zone_headings" => "hide" ] );' . "\n\n"
		. '// Return the markup instead of printing it.' . "\n"
		. '$html = dtfs_shipping_table( [], false );' . "\n\n"
		. '// The service container, for anything the helper does not cover.' . "\n"
		. '$zones = dtfs()->zones()->all();'
	);
	?></pre>

	<h3><?php esc_html_e('Overriding the markup', 'delivery-table-for-flexible-shipping'); ?></h3>

	<p>
		<?php
		printf(
			/* translators: 1: plugin folder name, 2: theme folder name. */
			esc_html__('Copy a file from the plugin\'s %1$s folder into %2$s in your theme and edit it there. Child theme wins over parent theme, exactly like WooCommerce templates.', 'delivery-table-for-flexible-shipping'),
			'<code>templates/</code>',
			'<code>delivery-table-for-flexible-shipping/</code>'
		);
		?>
	</p>

	<h3><?php esc_html_e('Hooks', 'delivery-table-for-flexible-shipping'); ?></h3>

	<table class="widefat striped dtfs-doc-table">
		<tbody>
			<tr>
				<td><code>dtfs_booted</code></td>
				<td><?php esc_html_e('Action. Fires once every service is registered; receives the service container.', 'delivery-table-for-flexible-shipping'); ?></td>
			</tr>
		</tbody>
	</table>
</div>
