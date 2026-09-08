<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Shipping\Rule;

use Countable;
use IteratorAggregate;
use Traversable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * An immutable, order-value-sorted collection of {@see CostRule}s.
 *
 * @implements IteratorAggregate<int, CostRule>
 */
final class RuleSet implements Countable, IteratorAggregate
{
    /** @var list<CostRule> */
    private readonly array $rules;

    /** @param iterable<CostRule> $rules */
    public function __construct(iterable $rules = [])
    {
        $sorted = is_array($rules) ? array_values($rules) : iterator_to_array($rules, false);

        usort($sorted, static fn (CostRule $a, CostRule $b): int => $a->from <=> $b->from);

        $this->rules = $sorted;
    }

    /**
     * Cheapest rule that applies to the given order value, or null when no rule
     * covers it.
     *
     * The whole rule is returned rather than its cost, because the caller also
     * has to know whether that cost is conditional on something the table
     * cannot show.
     */
    public function cheapestRuleFor(float $orderValue): ?CostRule
    {
        $cheapest = null;

        foreach ($this->rules as $rule) {
            if (!$rule->covers($orderValue)) {
                continue;
            }

            if ($cheapest === null || $rule->cost < $cheapest->cost) {
                $cheapest = $rule;
            }
        }

        return $cheapest;
    }

    /**
     * Order values at which the price changes - the column boundaries of the
     * rendered table.
     *
     * @return list<float>
     */
    public function breakpoints(): array
    {
        $breakpoints = [];

        foreach ($this->rules as $rule) {
            if ($rule->from > 0.0) {
                $breakpoints[] = $rule->from;
            }
        }

        return array_values(array_unique($breakpoints));
    }

    /**
     * Order values just past the end of a bounded rule.
     *
     * A rule that stops at 99.99 makes 100.00 a boundary just as much as a rule
     * that starts there: above it the price changes, or the method stops being
     * available at all. Without these the last band of a rule would be folded
     * into a wider column and priced as if the rule still applied.
     *
     * Returned raw; the caller adds the currency's smallest unit, because only
     * it knows the shop's precision.
     *
     * @return list<float>
     */
    public function upperBounds(): array
    {
        $bounds = [];

        foreach ($this->rules as $rule) {
            if ($rule->to !== null && $rule->to > 0.0) {
                $bounds[] = $rule->to;
            }
        }

        return array_values(array_unique($bounds));
    }

    public function isEmpty(): bool
    {
        return $this->rules === [];
    }

    public function count(): int
    {
        return count($this->rules);
    }

    /** @return Traversable<int, CostRule> */
    public function getIterator(): Traversable
    {
        yield from $this->rules;
    }
}
