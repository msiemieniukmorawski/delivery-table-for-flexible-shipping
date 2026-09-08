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

    /** Cheapest cost that applies to the given order value, or null when uncovered. */
    public function costFor(float $orderValue): ?float
    {
        $cheapest = null;

        foreach ($this->rules as $rule) {
            if (!$rule->covers($orderValue)) {
                continue;
            }

            if ($cheapest === null || $rule->cost < $cheapest) {
                $cheapest = $rule->cost;
            }
        }

        return $cheapest;
    }

    /**
     * Order values at which the price changes — the column boundaries of the
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
