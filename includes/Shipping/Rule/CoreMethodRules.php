<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Shipping\Rule;

use MSM\DeliveryTable\Shipping\Method\MethodRepository;
use WC_Shipping_Method;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cost rules implied by WooCommerce's own shipping methods.
 *
 * Flexible Shipping is not the only thing that can price a zone: shops mix in
 * a plain flat rate, a local pickup or WooCommerce's free shipping. Those
 * methods are listed as rows either way, so the table has to price them rather
 * than dash them - but only where the cost really is a fixed amount. A flat
 * rate written as `10 + (2 * [qty])` depends on the basket rather than on the
 * order value, and stays unknown.
 */
final class CoreMethodRules
{
    private const FLAT_RATE     = 'flat_rate';
    private const LOCAL_PICKUP  = 'local_pickup';
    private const FREE_SHIPPING = 'free_shipping';

    /**
     * WooCommerce's own free shipping requirements that carry a minimum order
     * amount. "coupon" is absent for the same reason it is in Flexible
     * Shipping: it has no amount to put in a column.
     *
     * @var array<string, bool> Setting value => whether a coupon is needed as well.
     */
    private const AMOUNT_BEARING_REQUIREMENTS = [
        'min_amount' => false,
        'either'     => false,
        'both'       => true,
    ];

    public function __construct(private readonly MethodRepository $methods)
    {
    }

    /**
     * @return list<CostRule> Empty when the method prices itself in a way an
     *                        order-value table cannot state.
     */
    public function rulesFor(WC_Shipping_Method $method): array
    {
        return match ((string) $method->id) {
            self::FLAT_RATE, self::LOCAL_PICKUP => $this->fromCostSetting($method),
            self::FREE_SHIPPING                 => $this->fromFreeShipping($method),
            default                             => [],
        };
    }

    /** @return list<CostRule> */
    private function fromCostSetting(WC_Shipping_Method $method): array
    {
        $settings = $this->methods->settings($method);
        $cost     = trim((string) ($settings['cost'] ?? ''));

        if ($cost === '') {
            /*
             * Local pickup with no cost is free. A flat rate with no cost
             * offers no rate at all unless shipping classes price it, which
             * is a basket question rather than an order-value one.
             */
            return (string) $method->id === self::LOCAL_PICKUP ? [CostRule::flat(0.0)] : [];
        }

        if (!is_numeric($cost)) {
            // A sum, a `[qty]` placeholder or a `[fee]` shortcode: real, but
            // not a number this table can print against an order value.
            return [];
        }

        return [CostRule::flat((float) $cost, $this->hasShippingClassCosts($settings))];
    }

    /** @return list<CostRule> */
    private function fromFreeShipping(WC_Shipping_Method $method): array
    {
        $settings = $this->methods->settings($method);
        $requires = (string) ($settings['requires'] ?? '');

        // No requirement at all: free for every order value.
        if ($requires === '') {
            return [CostRule::flat(0.0)];
        }

        if (!array_key_exists($requires, self::AMOUNT_BEARING_REQUIREMENTS)) {
            // "coupon": free, but only to a customer who already holds one.
            return [CostRule::flat(0.0, true)];
        }

        $needsCoupon = self::AMOUNT_BEARING_REQUIREMENTS[$requires];
        $amount      = $settings['min_amount'] ?? null;

        // A minimum of zero or less is not a minimum, exactly as in Flexible
        // Shipping - the method is then simply free.
        if (!is_numeric($amount) || (float) $amount <= 0.0) {
            return [CostRule::flat(0.0, $needsCoupon)];
        }

        // Free from the minimum upwards, and unavailable below it.
        return [new CostRule((float) $amount, null, 0.0, $needsCoupon)];
    }

    /**
     * Whether the flat rate also charges per shipping class, which makes the
     * base cost indicative rather than final.
     *
     * @param array<string, mixed> $settings
     */
    private function hasShippingClassCosts(array $settings): bool
    {
        foreach ($settings as $key => $value) {
            if (!str_starts_with((string) $key, 'class_cost_') && $key !== 'no_class_cost') {
                continue;
            }

            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }
}
