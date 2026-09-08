<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Shipping\Zone;

use WC_Shipping_Zone;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * One region of the delivery table: every shipping zone that serves the same
 * place, gathered under a single heading and a single anchor.
 *
 * A shop may split one destination across several zones - a courier zone and a
 * pickup zone both covering Poland, say. To a customer that is one question,
 * so the table answers it in one place: the zones are rendered one under the
 * other regardless of the order they happen to sit in wp-admin.
 */
final class ZoneGroup
{
    /**
     * @param string                   $id        Anchor, e.g. `dtfs-region-pl`. Unique within a render.
     * @param string                   $label     Heading, e.g. "Poland" - already in the storefront language.
     * @param list<string>             $countries ISO 3166-1 alpha-2 codes this region covers, for flag lists.
     * @param list<WC_Shipping_Zone>   $zones     In the order the shop arranged them.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly array $countries,
        public readonly array $zones
    ) {
    }
}
