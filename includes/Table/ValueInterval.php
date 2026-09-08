<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Table;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * One column of the delivery table: a range of order values that all share
 * the same shipping price.
 */
final class ValueInterval
{
    /**
     * @param float|null $from  Inclusive lower bound, null for "anything below".
     * @param float|null $to    Inclusive upper bound, null for "and above".
     * @param string     $label Human readable heading for the column.
     */
    public function __construct(
        public readonly ?float $from,
        public readonly ?float $to,
        public readonly string $label
    ) {
    }

    /**
     * Order value to price this column at. Any value inside the interval gives
     * the same answer, so the inclusive lower bound is the natural probe.
     */
    public function probeValue(): float
    {
        return $this->from ?? 0.0;
    }

    public function startsAtOrAbove(float $amount): bool
    {
        return $this->from !== null && $this->from >= $amount;
    }
}
