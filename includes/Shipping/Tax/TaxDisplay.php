<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Shipping\Tax;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * How shipping costs should be presented in the table.
 *
 * Flexible Shipping stores costs net. A B2C shop wants them gross, a B2B shop
 * wants them net, and most shops have already answered that question once in
 * WooCommerce - hence {@see self::Auto} as the default.
 */
enum TaxDisplay: string
{
    /** Follow "Display prices during cart and checkout" from WooCommerce. */
    case Auto = 'auto';

    /** Always add shipping tax, whatever WooCommerce is set to. */
    case Including = 'incl';

    /** Always show the net cost, whatever WooCommerce is set to. */
    case Excluding = 'excl';

    /** Reads a shortcode or block attribute, falling back to {@see self::Auto}. */
    public static function fromAttribute(?string $value): self
    {
        return self::tryFrom(strtolower(trim((string) $value))) ?? self::Auto;
    }

    public function apply(float $netCost, ShippingTaxCalculator $tax): float
    {
        return match ($this) {
            self::Auto      => $tax->displayCost($netCost),
            self::Including => $tax->grossCost($netCost),
            self::Excluding => $netCost,
        };
    }
}
