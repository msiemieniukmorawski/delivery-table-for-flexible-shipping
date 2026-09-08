<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Shipping\Rule;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * One Flexible Shipping cost rule reduced to what a price table needs:
 * "between this order value and that one, shipping costs X (net)".
 */
final class CostRule
{
    /**
     * @param float      $from        Inclusive lower bound of the order value.
     * @param float|null $to          Inclusive upper bound, or null for "and above".
     * @param float      $cost        Cost per order, excluding tax.
     * @param bool       $conditional Whether the same Flexible Shipping rule also carries a
     *                                condition an order-value table cannot express - weight,
     *                                item count, shipping class. The cost then holds only when
     *                                that other condition is met too, so it must be presented
     *                                as indicative rather than final.
     */
    public function __construct(
        public readonly float $from,
        public readonly ?float $to,
        public readonly float $cost,
        public readonly bool $conditional = false
    ) {
    }

    /** A rule with no value condition at all - a flat price. */
    public static function flat(float $cost, bool $conditional = false): self
    {
        return new self(0.0, null, $cost, $conditional);
    }

    public function covers(float $orderValue): bool
    {
        if ($orderValue < $this->from) {
            return false;
        }

        return $this->to === null || $orderValue <= $this->to;
    }

    public function isFree(): bool
    {
        return $this->cost <= 0.0;
    }
}
