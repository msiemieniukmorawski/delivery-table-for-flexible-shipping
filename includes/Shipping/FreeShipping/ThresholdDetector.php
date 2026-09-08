<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Shipping\FreeShipping;

use MSM\DeliveryTable\Shipping\Method\MethodRepository;
use MSM\DeliveryTable\Shipping\Rule\CostRule;
use MSM\DeliveryTable\Shipping\Rule\RuleParser;
use WC_Shipping_Method;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Finds the order value at which a shipping method starts to be free.
 *
 * Flexible Shipping expresses this in two unrelated ways, and a method may use
 * either: a dedicated "free shipping requires" setting, or a plain cost rule
 * priced at zero above some order value.
 */
final class ThresholdDetector
{
    /**
     * Flexible Shipping stores a deliberately unreachable amount to mean
     * "never free"; treating it as a real threshold would advertise an offer
     * that can never be claimed.
     */
    private const UNREACHABLE_THRESHOLD = 999999.0;

    private const REQUIRES_ORDER_AMOUNT = 'order_amount';
    private const REQUIRES_MIN_AMOUNT   = 'min_amount';
    private const REQUIRES_EITHER       = 'either';
    private const REQUIRES_BOTH         = 'both';

    /**
     * Settings that carry a minimum order amount. "coupon" is deliberately
     * absent: it has no amount, so there is nothing to put in the table.
     *
     * Flexible Shipping calls the amount-only case `order_amount`; WooCommerce's
     * own free shipping method calls it `min_amount`. They mean the same thing.
     *
     * @var array<string, bool> Setting value => whether a coupon is also required.
     */
    private const AMOUNT_BEARING_REQUIREMENTS = [
        self::REQUIRES_ORDER_AMOUNT => false,
        self::REQUIRES_MIN_AMOUNT   => false,
        self::REQUIRES_EITHER       => false,
        self::REQUIRES_BOTH         => true,
    ];

    /**
     * Where a threshold lives, as "which setting names the requirement" =>
     * "which setting holds the amount".
     */
    private const THRESHOLD_SETTINGS = [
        'method_free_shipping_requires' => 'method_free_shipping',
        'requires'                      => 'min_amount',
    ];

    public function __construct(
        private readonly MethodRepository $methods,
        private readonly RuleParser $rules
    ) {
    }

    public function detect(WC_Shipping_Method $method): ?FreeShippingThreshold
    {
        return $this->fromFreeShippingSetting($method)
            ?? $this->fromCostRules($method);
    }

    private function fromFreeShippingSetting(WC_Shipping_Method $method): ?FreeShippingThreshold
    {
        $settings = $this->methods->settings($method);

        foreach (self::THRESHOLD_SETTINGS as $requiresKey => $amountKey) {
            $threshold = $this->threshold(
                (string) ($settings[$requiresKey] ?? ''),
                $settings[$amountKey] ?? null
            );

            if ($threshold !== null) {
                return $threshold;
            }
        }

        return null;
    }

    private function threshold(string $requires, mixed $amount): ?FreeShippingThreshold
    {
        if (!array_key_exists($requires, self::AMOUNT_BEARING_REQUIREMENTS) || !is_numeric($amount)) {
            return null;
        }

        $amount = (float) $amount;

        if ($amount <= 0.0 || $amount >= self::UNREACHABLE_THRESHOLD) {
            return null;
        }

        return new FreeShippingThreshold($amount, self::AMOUNT_BEARING_REQUIREMENTS[$requires]);
    }

    private function fromCostRules(WC_Shipping_Method $method): ?FreeShippingThreshold
    {
        $lowest = null;

        foreach ($this->rules->parse($method) as $rule) {
            /** @var CostRule $rule */
            if (!$rule->isFree() || $rule->from <= 0.0) {
                continue;
            }

            if ($lowest === null || $rule->from < $lowest) {
                $lowest = $rule->from;
            }
        }

        return $lowest === null ? null : new FreeShippingThreshold($lowest);
    }
}
