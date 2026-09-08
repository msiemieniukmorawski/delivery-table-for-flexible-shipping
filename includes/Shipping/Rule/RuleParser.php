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
 * Only value-based conditions can become a column in an order-value table.
 * A rule that carries other conditions as well still produces a cost, but is
 * marked conditional so the table can say the price is indicative; a rule that
 * carries *only* other conditions produces nothing at all, which is what makes
 * a weight-priced method render as "not available for this order value"
 * instead of silently as free.
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

            array_push($rules, ...$this->rulesFrom($rawRule, (float) $rawRule['cost_per_order']));
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed> $rawRule
     * @return list<CostRule>
     */
    private function rulesFrom(array $rawRule, float $cost): array
    {
        $conditions = $rawRule['conditions'] ?? [];

        // No conditions at all: the rule always applies.
        if (!is_array($conditions) || $conditions === []) {
            return [CostRule::flat($cost)];
        }

        $ranges          = [];
        $alwaysApplies   = false;
        $otherConditions = false;

        foreach ($conditions as $condition) {
            if (!is_array($condition)) {
                continue;
            }

            switch ((string) ($condition['condition_id'] ?? self::CONDITION_NONE)) {
                case self::CONDITION_NONE:
                    $alwaysApplies = true;
                    break;

                case self::CONDITION_VALUE:
                    $ranges[] = [
                        $this->toFloat($condition['min'] ?? null) ?? 0.0,
                        $this->toFloat($condition['max'] ?? null),
                    ];
                    break;

                default:
                    // Weight, item count, shipping class, product - none of
                    // which an order-value table can turn into a column.
                    $otherConditions = true;
            }
        }

        if ($ranges !== []) {
            return array_map(
                static fn (array $range): CostRule => new CostRule($range[0], $range[1], $cost, $otherConditions),
                $ranges
            );
        }

        if ($alwaysApplies) {
            return [CostRule::flat($cost, $otherConditions)];
        }

        // Only conditions this table cannot express: no order-value rule at all.
        return [];
    }

    private function toFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
