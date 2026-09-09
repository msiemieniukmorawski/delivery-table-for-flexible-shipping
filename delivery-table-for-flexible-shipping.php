<?php
/**
 * Plugin Name:       Delivery Table for Flexible Shipping
 * Plugin URI:        https://github.com/msiemieniukmorawski/delivery-table-for-flexible-shipping
 * Description:       Auto-generated delivery price table for WooCommerce, built from your Flexible Shipping zones and cost rules. No more hand-maintained shipping tables.
 * Version:           1.0.1
 * Requires at least: 6.2
 * Requires PHP:      8.1
 * Author:            Marcin Siemieniuk-Morawski
 * Author URI:        https://ms-m.pl/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       delivery-table-for-flexible-shipping
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce, flexible-shipping
 * WC requires at least: 7.0
 * WC tested up to:   10.4.3
 *
 * @package MSM\DeliveryTable
 */

declare(strict_types=1);

namespace MSM\DeliveryTable;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if (!defined('ABSPATH')) {
    exit;
}

const VERSION     = '1.0.1';
const TEXT_DOMAIN = 'delivery-table-for-flexible-shipping';
const MIN_PHP     = '8.1';

define(__NAMESPACE__ . '\FILE', __FILE__);
define(__NAMESPACE__ . '\PATH', plugin_dir_path(__FILE__));
define(__NAMESPACE__ . '\URL', plugin_dir_url(__FILE__));

/**
 * Bail out loudly but harmlessly on unsupported PHP, rather than fataling on
 * the first typed property the parser meets.
 */
if (version_compare(PHP_VERSION, MIN_PHP, '<')) {
    add_action('admin_notices', static function (): void {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html(
                sprintf(
                    /* translators: 1: required PHP version, 2: current PHP version. */
                    __(
                        'Delivery Table for Flexible Shipping requires PHP %1$s or newer. This server runs PHP %2$s.',
                        'delivery-table-for-flexible-shipping'
                    ),
                    MIN_PHP,
                    PHP_VERSION
                )
            )
        );
    });

    return;
}

/*
 * The plugin never touches orders, but WooCommerce warns about every plugin
 * that stays silent, so say so explicitly.
 */
add_action('before_woocommerce_init', static function (): void {
    if (class_exists(FeaturesUtil::class)) {
        FeaturesUtil::declare_compatibility('custom_order_tables', FILE, true);
        FeaturesUtil::declare_compatibility('cart_checkout_blocks', FILE, true);
    }
});

require_once PATH . 'includes/Autoloader.php';

Autoloader::forNamespace(__NAMESPACE__, PATH . 'includes')->register();

Plugin::instance()->boot();
