<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Support;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Guards the plugin against running without WooCommerce or Flexible Shipping.
 *
 * Both checks are intentionally class based rather than `is_plugin_active()`:
 * that helper is admin-only and lies when a dependency is loaded as a must-use
 * plugin or bundled by a theme.
 */
final class Requirements
{
    private const WOOCOMMERCE_CLASS      = 'WooCommerce';
    private const FLEXIBLE_SHIPPING_CLASS = 'WPDesk\FS\TableRate\ShippingMethodSingle';

    public function hasWooCommerce(): bool
    {
        return class_exists(self::WOOCOMMERCE_CLASS);
    }

    public function hasFlexibleShipping(): bool
    {
        return class_exists(self::FLEXIBLE_SHIPPING_CLASS);
    }

    public function areMet(): bool
    {
        return $this->hasWooCommerce() && $this->hasFlexibleShipping();
    }

    /**
     * Names of the missing dependencies, ready to be listed to the shop manager.
     *
     * @return list<string>
     */
    public function missing(): array
    {
        $missing = [];

        if (!$this->hasWooCommerce()) {
            $missing[] = 'WooCommerce';
        }

        if (!$this->hasFlexibleShipping()) {
            $missing[] = 'Flexible Shipping by WP Desk';
        }

        return $missing;
    }

    public function renderAdminNotice(): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        $missing = $this->missing();

        if ($missing === []) {
            return;
        }

        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            esc_html(
                sprintf(
                    /* translators: %s: comma separated list of missing plugin names. */
                    _n(
                        'Delivery Table for Flexible Shipping needs %s to be installed and active.',
                        'Delivery Table for Flexible Shipping needs these plugins to be installed and active: %s.',
                        count($missing),
                        'delivery-table-for-flexible-shipping'
                    ),
                    implode(', ', $missing)
                )
            )
        );
    }
}
