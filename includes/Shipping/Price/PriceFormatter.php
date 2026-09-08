<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Shipping\Price;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Formats amounts the way the shop is configured to, and answers the
 * "what is one cent here?" question that interval labels depend on.
 */
final class PriceFormatter
{
    /** WooCommerce price markup, e.g. `<span class="woocommerce-Price-amount">…</span>`. */
    public function html(float $amount): string
    {
        return (string) wc_price($amount);
    }

    /**
     * Plain text price with no tags and no HTML entities, e.g. `149.00 zł`.
     *
     * Needed wherever the value ends up in an attribute, a JSON payload or a
     * `sprintf()` template that is escaped afterwards.
     */
    public function plain(float $amount): string
    {
        $charset = get_bloginfo('charset') ?: 'UTF-8';

        return trim(
            html_entity_decode(
                wp_strip_all_tags($this->html($amount)),
                ENT_QUOTES,
                $charset
            )
        );
    }

    /** Price wrapped in the plugin's own span so themes can style just this number. */
    public function highlighted(float $amount): string
    {
        return '<span class="dtfs-price">' . esc_html($this->plain($amount)) . '</span>';
    }

    /**
     * Smallest amount the shop currency can express, e.g. 0.01 for two
     * decimals and 1.0 for a zero-decimal currency such as JPY.
     */
    public function smallestUnit(): float
    {
        return 10 ** -wc_get_price_decimals();
    }

    /** Rounds to the shop's price precision, killing float drift before comparisons. */
    public function round(float $amount): float
    {
        return round($amount, wc_get_price_decimals());
    }
}
