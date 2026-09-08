<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Table;

use MSM\DeliveryTable\Shipping\Price\PriceFormatter;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Turns the order values at which prices change into table columns.
 *
 * The upper bound of a column is the next breakpoint minus one smallest
 * currency unit, so "up to 148.99 / from 149.00" reads correctly in a
 * two-decimal currency and as "up to 148 / from 149" in a zero-decimal one.
 */
final class IntervalBuilder
{
    public function __construct(private readonly PriceFormatter $prices)
    {
    }

    /**
     * @param  list<float> $breakpoints Unsorted, possibly duplicated.
     * @return list<ValueInterval>
     */
    public function build(array $breakpoints): array
    {
        $breakpoints = $this->normalise($breakpoints);

        if ($breakpoints === []) {
            return [
                new ValueInterval(
                    null,
                    null,
                    __('Order value', 'delivery-table-for-flexible-shipping')
                ),
            ];
        }

        $unit      = $this->prices->smallestUnit();
        $intervals = [];
        $previous  = null;

        foreach ($breakpoints as $breakpoint) {
            $upperBound = $this->prices->round($breakpoint - $unit);

            $intervals[] = new ValueInterval(
                $previous,
                $upperBound,
                $previous === null
                    ? sprintf(
                        /* translators: %s: formatted order value, e.g. "148.99 zł". */
                        __('Up to %s', 'delivery-table-for-flexible-shipping'),
                        $this->prices->plain($upperBound)
                    )
                    : sprintf(
                        /* translators: 1: lower order value, 2: upper order value. */
                        __('%1$s – %2$s', 'delivery-table-for-flexible-shipping'),
                        $this->prices->plain($previous),
                        $this->prices->plain($upperBound)
                    )
            );

            $previous = $breakpoint;
        }

        $intervals[] = new ValueInterval(
            $previous,
            null,
            sprintf(
                /* translators: %s: formatted order value, e.g. "149.00 zł". */
                __('%s and up', 'delivery-table-for-flexible-shipping'),
                $this->prices->plain((float) $previous)
            )
        );

        return $intervals;
    }

    /**
     * @param  list<float> $breakpoints
     * @return list<float> Sorted ascending, de-duplicated at currency precision.
     */
    private function normalise(array $breakpoints): array
    {
        $rounded = [];

        foreach ($breakpoints as $breakpoint) {
            $breakpoint = $this->prices->round((float) $breakpoint);

            if ($breakpoint > 0.0) {
                // String keys de-duplicate 149.0 and 149.000000001 reliably.
                $rounded[(string) $breakpoint] = $breakpoint;
            }
        }

        $values = array_values($rounded);
        sort($values);

        return $values;
    }
}
