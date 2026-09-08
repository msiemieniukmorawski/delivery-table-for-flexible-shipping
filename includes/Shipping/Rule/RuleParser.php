<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Shipping\Rule;

use MSM\DeliveryTable\Shipping\Method\MethodRepository;
use WC_Shipping_Method;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Turns whatever prices a shipping method into {@see CostRule} objects.
 *
 * Flexible Shipping stores its rules in two places: as an array on its own
 * method, and as a JSON string bolted onto a core WooCommerce method, where
 * they only count while its calculation is switched on. A method that
 * Flexible Shipping does not price at all is handed to {@see CoreMethodRules},
 * which reads WooCommerce's own cost settings.
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

    /** Flexible Shipping's own method keeps its rules here, as an array. */
    private const RULES = 'method_rules';

    /**
     * Bolted onto a core WooCommerce method, Flexible Shipping keeps the same
     * rules as a JSON string, and only applies them when its own calculation
     * is switched on for that method.
     */
    private const CORE_RULES   = 'fs_method_rules';
    private const CORE_ENABLED = 'fs_calculation_enabled';

    /** @var array<int, RuleSet> Per-request memo, keyed by method instance id. */
    private array $cache = [];

    public function __construct(
        private readonly MethodRepository $methods,
        private readonly CoreMethodRules $coreMethods
    ) {
    }

    public function parse(WC_Shipping_Method $method): RuleSet
    {
        $instanceId = (int) $method->get_instance_id();

        if ($instanceId > 0 && isset($this->cache[$instanceId])) {
            return $this->cache[$instanceId];
        }

        $rawRules = $this->rawRules($method);

        /*
         * No Flexible Shipping rules drive this method at all, so it prices
         * itself - a plain flat rate, a local pickup, WooCommerce's own free
         * shipping. Rules that exist but yield nothing usable are a different
         * matter and must not fall through here, or a weight-priced method
         * would be repriced from its base cost.
         */
        $ruleSet = new RuleSet(
            $rawRules === null
                ? $this->coreMethods->rulesFor($method)
                : $this->extractRules($rawRules)
        );

        if ($instanceId > 0) {
            $this->cache[$instanceId] = $ruleSet;
        }

        return $ruleSet;
    }

    /**
     * The raw Flexible Shipping rules attached to a method, or null when
     * Flexible Shipping does not price it at all.
     *
     * @return array<int|string, mixed>|null
     */
    private function rawRules(WC_Shipping_Method $method): ?array
    {
        $settings = $this->methods->settings($method);

        if (is_array($settings[self::RULES] ?? null)) {
            return $settings[self::RULES];
        }

        if ((string) ($settings[self::CORE_ENABLED] ?? 'no') !== 'yes') {
            return null;
        }

        $decoded = json_decode((string) ($settings[self::CORE_RULES] ?? ''), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<int|string, mixed> $rawRules
     * @return list<CostRule>
     */
    private function extractRules(array $rawRules): array
    {
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
