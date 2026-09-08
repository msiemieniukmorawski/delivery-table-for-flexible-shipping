<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Shipping\Tax;

use WC_Shipping_Method;
use WC_Tax;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Turns the net shipping costs stored by Flexible Shipping into the amount
 * the customer is supposed to see, following the shop's cart tax display
 * setting.
 */
final class ShippingTaxCalculator
{
    /** The one `tax_status` value that means "add tax to this method's costs". */
    private const TAXABLE = 'taxable';

    /** Tax rates are per request and per tax class; caching them saves repeated DB hits. */
    private array $rateCache = [];

    /**
     * Whether a method's costs are taxed at all.
     *
     * A method set to "None" is priced exactly as entered, so grossing it up
     * would overstate what the customer pays. WooCommerce itself compares the
     * setting to "taxable" and treats every other value as untaxed; this
     * follows that reading rather than inventing a looser one.
     */
    public function isTaxable(WC_Shipping_Method $method): bool
    {
        return (string) ($method->tax_status ?? self::TAXABLE) === self::TAXABLE;
    }

    /**
     * Cost as it should be printed: gross when the shop displays cart prices
     * including tax, net otherwise.
     */
    public function displayCost(float $netCost): float
    {
        return $this->displaysTaxInclusive()
            ? $this->grossCost($netCost)
            : $netCost;
    }

    public function grossCost(float $netCost): float
    {
        return $netCost + $this->tax($netCost);
    }

    /**
     * Applies an already known tax amount (e.g. from a `WC_Shipping_Rate`)
     * according to the display setting.
     */
    public function applyDisplaySetting(float $cost, float $tax): float
    {
        return $this->displaysTaxInclusive() ? $cost + $tax : $cost;
    }

    public function displaysTaxInclusive(): bool
    {
        return get_option('woocommerce_tax_display_cart', 'excl') === 'incl';
    }

    public function tax(float $netCost): float
    {
        if ($netCost <= 0.0 || !$this->taxesEnabled()) {
            return 0.0;
        }

        $rates = $this->shippingTaxRates();

        if ($rates === []) {
            return 0.0;
        }

        return (float) array_sum(WC_Tax::calc_tax($netCost, $rates, false));
    }

    private function taxesEnabled(): bool
    {
        return wc_tax_enabled()
            && wc_shipping_enabled()
            && get_option('woocommerce_calc_taxes') === 'yes';
    }

    /** @return array<int, array<string, mixed>> */
    private function shippingTaxRates(): array
    {
        $taxClass = (string) get_option('woocommerce_shipping_tax_class', '');

        // "inherit" means "use the standard rate" as far as shipping goes.
        if ($taxClass === 'inherit') {
            $taxClass = '';
        }

        return $this->rateCache[$taxClass] ??= (array) WC_Tax::get_shipping_tax_rates($taxClass);
    }
}
