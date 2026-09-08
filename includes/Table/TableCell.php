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
    public const KIND_FREE        = 'free';
    public const KIND_PRICE       = 'price';
    public const KIND_UNAVAILABLE = 'unavailable';

    private function __construct(
        public readonly string $html,
        public readonly string $kind,
        public readonly bool $approximate = false
    ) {
    }

    public static function free(string $label): self
    {
        return new self(
            '<span class="dtfs-cost dtfs-cost--free">' . esc_html($label) . '</span>',
            self::KIND_FREE
        );
    }

    /** @param string $priceHtml Output of `wc_price()`, already safe markup. */
    public static function price(string $priceHtml): self
    {
        return new self(
            '<span class="dtfs-cost dtfs-cost--price">' . wp_kses_post($priceHtml) . '</span>',
            self::KIND_PRICE
        );
    }

    /**
     * No rule covers this order value for this method - saying so beats
     * silently printing "free".
     */
    public static function unavailable(string $label): self
    {
        return new self(
            sprintf(
                '<span class="dtfs-cost dtfs-cost--unavailable" title="%s">&mdash;<span class="screen-reader-text">%s</span></span>',
                esc_attr($label),
                esc_html($label)
            ),
            self::KIND_UNAVAILABLE
        );
    }

    /**
     * Marks the cell as indicative: the value is right as far as order value
     * goes, but the real cost also depends on something this table cannot show
     * - a weight rule, or a coupon the customer must still hold.
     */
    public function asApproximate(string $explanation): self
    {
        if ($this->approximate) {
            return $this;
        }

        $marker = sprintf(
            '<abbr class="dtfs-cost__marker" title="%s">*</abbr>',
            esc_attr($explanation)
        );

        return new self($this->html . $marker, $this->kind, true);
    }
}
