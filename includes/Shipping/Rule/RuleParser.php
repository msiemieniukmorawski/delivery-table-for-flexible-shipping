<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Shipping\Rule;

use MSM\DeliveryTable\Shipping\Method\MethodRepository;
use WC_Shipping_Method;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Translates the `method_rules` array Flexible Shipping stores in the method
 * instance settings into {@see CostRule} objects.
 *
 * Only value-based conditions are understood; weight, item count and product
 * conditions cannot be expressed as a column in an order-value table and are
 * skipped rather than guessed at.
 */
final class RuleParser
{
    private const CONDITION_NONE  = 'none';
    private const CONDITION_VALUE = 'value';

    /** @var array<int, RuleSet> Per-request memo, keyed by method instance id. */
    private array $cache = [];

    public function __construct(private readonly MethodRepository $methods)
    {
    }

    public function parse(WC_Shipping_Method $method): RuleSet
    {
        $instanceId = (int) $method->get_instance_id();

        if ($instanceId > 0 && isset($this->cache[$instanceId])) {
            return $this->cache[$instanceId];
        }

        $ruleSet = new RuleSet($this->extractRules($method));

        if ($instanceId > 0) {
            $this->cache[$instanceId] = $ruleSet;
        }

        return $ruleSet;
    }

    /** @return list<CostRule> */
    private function extractRules(WC_Shipping_Method $method): array
    {
        $rawRules = $this->methods->settings($method)['method_rules'] ?? null;

        if (!is_array($rawRules)) {
            return [];
        }

        $rules = [];

        foreach ($rawRules as $rawRule) {
            if (!is_array($rawRule) || !is_numeric($rawRule['cost_per_order'] ?? null)) {
                continue;
            }

            $cost       = (float) $rawRule['cost_per_order'];
            $conditions = $rawRule['conditions'] ?? [];

            // No conditions at all: the rule always applies.
            if (!is_array($conditions) || $conditions === []) {
                $rules[] = CostRule::flat($cost);
                continue;
            }

            foreach ($conditions as $condition) {
                $rule = $this->toRule($condition, $cost);

                if ($rule !== null) {
                    $rules[] = $rule;
                }
            }
        }

        return $rules;
    }

    private function toRule(mixed $condition, float $cost): ?CostRule
    {
        if (!is_array($condition)) {
            return null;
        }

        $conditionId = (string) ($condition['condition_id'] ?? self::CONDITION_NONE);

        if ($conditionId === self::CONDITION_NONE) {
            return CostRule::flat($cost);
        }

        if ($conditionId !== self::CONDITION_VALUE) {
            return null;
        }

        return new CostRule(
            $this->toFloat($condition['min'] ?? null) ?? 0.0,
            $this->toFloat($condition['max'] ?? null),
            $cost
        );
    }

    private function toFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
