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
 * Flexible Shipping expresses this in two unrelated ways, and a method may
 * use either: a dedicated "free shipping requires order amount" setting, or a
 * plain cost rule priced at zero above some order value.
 */
final class ThresholdDetector
{
    /**
     * Flexible Shipping stores a deliberately unreachable amount to mean
     * "never free"; treating it as a real threshold would advertise a free
     * shipping offer that can never be claimed.
     */
    private const UNREACHABLE_THRESHOLD = 999999.0;

    private const REQUIRES_ORDER_AMOUNT = 'order_amount';

    public function __construct(
        private readonly MethodRepository $methods,
        private readonly RuleParser $rules
    ) {
    }

    public function detect(WC_Shipping_Method $method): ?float
    {
        return $this->fromFreeShippingSetting($method)
            ?? $this->fromCostRules($method);
    }

    /**
     * Lowest threshold across a set of methods, together with the method that
     * offers it.
     *
     * @param  list<WC_Shipping_Method> $methods
     * @return array{threshold: float, method: WC_Shipping_Method}|null
     */
    public function cheapest(array $methods): ?array
    {
        $best = null;

        foreach ($methods as $method) {
            $threshold = $this->detect($method);

            if ($threshold === null || $threshold <= 0.0) {
                continue;
            }

            if ($best === null || $threshold < $best['threshold']) {
                $best = ['threshold' => $threshold, 'method' => $method];
            }
        }

        return $best;
    }

    private function fromFreeShippingSetting(WC_Shipping_Method $method): ?float
    {
        $settings = $this->methods->settings($method);

        if (($settings['method_free_shipping_requires'] ?? '') !== self::REQUIRES_ORDER_AMOUNT) {
            return null;
        }

        $threshold = $settings['method_free_shipping'] ?? null;

        if (!is_numeric($threshold)) {
            return null;
        }

        $threshold = (float) $threshold;

        if ($threshold <= 0.0 || $threshold >= self::UNREACHABLE_THRESHOLD) {
            return null;
        }

        return $threshold;
    }

    private function fromCostRules(WC_Shipping_Method $method): ?float
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

        return $lowest;
    }
}
