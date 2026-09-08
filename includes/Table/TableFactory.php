<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Table;

use MSM\DeliveryTable\Shipping\FreeShipping\FreeShippingThreshold;
use MSM\DeliveryTable\Shipping\FreeShipping\ThresholdDetector;
use MSM\DeliveryTable\Shipping\Price\PriceFormatter;
use MSM\DeliveryTable\Shipping\Rule\RuleParser;
use MSM\DeliveryTable\Shipping\Rule\RuleSet;
use MSM\DeliveryTable\Shipping\Tax\ShippingTaxCalculator;
use MSM\DeliveryTable\Shipping\Tax\TaxDisplay;
use WC_Shipping_Method;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Builds a {@see DeliveryTable} out of shipping methods.
 *
 * Column boundaries are the union of every price change and every free
 * shipping threshold across the methods in the table, which is what keeps rows
 * aligned when two carriers switch price at different amounts.
 */
final class TableFactory
{
    public function __construct(
        private readonly RuleParser $rules,
        private readonly ThresholdDetector $thresholds,
        private readonly IntervalBuilder $intervals,
        private readonly ShippingTaxCalculator $tax,
        private readonly PriceFormatter $prices
    ) {
    }

    /**
     * @param list<WC_Shipping_Method> $methods
     */
    public function create(string $heading, array $methods, TaxDisplay $taxDisplay): ?DeliveryTable
    {
        if ($methods === []) {
            return null;
        }

        /** @var list<array{method: WC_Shipping_Method, rules: RuleSet, threshold: FreeShippingThreshold|null, taxable: bool}> $parsed */
        $parsed      = [];
        $breakpoints = [];

        foreach ($methods as $method) {
            $ruleSet   = $this->rules->parse($method);
            $threshold = $this->thresholds->detect($method);

            $parsed[] = [
                'method'    => $method,
                'rules'     => $ruleSet,
                'threshold' => $threshold,
                'taxable'   => $this->tax->isTaxable($method),
            ];

            array_push($breakpoints, ...$ruleSet->breakpoints());

            foreach ($ruleSet->upperBounds() as $upperBound) {
                $breakpoints[] = $this->prices->round($upperBound + $this->prices->smallestUnit());
            }

            if ($threshold !== null) {
                $breakpoints[] = $threshold->amount;
            }
        }

        $columns = $this->intervals->build($breakpoints);

        $rows = array_map(
            fn (array $entry): TableRow => new TableRow(
                (string) $entry['method']->get_title(),
                array_map(
                    fn (ValueInterval $column): TableCell => $this->cell(
                        $entry['rules'],
                        $entry['threshold'],
                        $column,
                        $taxDisplay,
                        $entry['taxable']
                    ),
                    $columns
                )
            ),
            $parsed
        );

        return new DeliveryTable($heading, $columns, $rows);
    }

    /**
     * @param bool $taxable Whether this method's costs are taxed at all. A
     *                      method set to "None" is priced exactly as entered,
     *                      whatever the shop displays elsewhere.
     */
    private function cell(
        RuleSet $rules,
        ?FreeShippingThreshold $threshold,
        ValueInterval $column,
        TaxDisplay $taxDisplay,
        bool $taxable
    ): TableCell
    {
        $freeLabel = __('Free shipping', 'delivery-table-for-flexible-shipping');

        // The dedicated free shipping setting wins over whatever the rules say.
        if ($threshold !== null && $column->startsAtOrAbove($threshold->amount)) {
            $cell = TableCell::free($freeLabel);

            return $threshold->requiresCoupon
                ? $cell->asApproximate(
                    __(
                        'Reaching this order value is not enough on its own: this method also needs a free shipping coupon.',
                        'delivery-table-for-flexible-shipping'
                    )
                )
                : $cell;
        }

        $rule = $rules->cheapestRuleFor($column->probeValue());

        if ($rule === null) {
            return TableCell::unavailable(
                __('Not available for this order value', 'delivery-table-for-flexible-shipping')
            );
        }

        $cost = $this->prices->round(
            $taxable ? $taxDisplay->apply($rule->cost, $this->tax) : $rule->cost
        );

        $cell = $cost <= 0.0
            ? TableCell::free($freeLabel)
            : TableCell::price($this->prices->html($cost));

        return $rule->conditional
            ? $cell->asApproximate(
                __(
                    'This price also depends on a condition the table cannot show, such as weight or item count.',
                    'delivery-table-for-flexible-shipping'
                )
            )
            : $cell;
    }
}
