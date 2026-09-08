<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Table;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * A single price cell, already rendered and already escaped.
 */
final class TableCell
{
    private function __construct(
        public readonly string $html,
        public readonly string $modifier
    ) {
    }

    public static function free(string $label): self
    {
        return new self('<span class="dtfs-cost dtfs-cost--free">' . esc_html($label) . '</span>', 'free');
    }

    /** @param string $priceHtml Output of `wc_price()`, already safe markup. */
    public static function price(string $priceHtml): self
    {
        return new self('<span class="dtfs-cost dtfs-cost--price">' . wp_kses_post($priceHtml) . '</span>', 'price');
    }

    /**
     * No rule covers this order value for this method — saying so beats
     * silently printing "free", which is what 1.x did.
     */
    public static function unavailable(string $label): self
    {
        return new self(
            sprintf(
                '<span class="dtfs-cost dtfs-cost--unavailable" title="%s" aria-label="%s">&mdash;</span>',
                esc_attr($label),
                esc_attr($label)
            ),
            'unavailable'
        );
    }
}
