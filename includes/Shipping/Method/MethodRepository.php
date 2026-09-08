<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Shipping\Method;

use WC_Shipping_Method;
use WC_Shipping_Zone;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Reads shipping method instances out of a zone.
 */
final class MethodRepository
{
    /**
     * @return list<WC_Shipping_Method>
     */
    public function inZone(WC_Shipping_Zone $zone, bool $includeDisabled = false): array
    {
        /*
         * The first argument is WooCommerce's `$enabled_only`. Passing true
         * would drop disabled methods in the data store, leaving the
         * `$includeDisabled` branch below with nothing left to include.
         */
        $methods = array_filter(
            $zone->get_shipping_methods(false),
            static fn (mixed $method): bool => $method instanceof WC_Shipping_Method
        );

        if (!$includeDisabled) {
            $methods = array_filter(
                $methods,
                static fn (WC_Shipping_Method $method): bool => $method->enabled === 'yes'
            );
        }

        return array_values($methods);
    }

    /**
     * Instance settings of a method, normalised to an array.
     *
     * Flexible Shipping keeps its rules in there, and third-party code has
     * been known to leave the property unset.
     *
     * @return array<string, mixed>
     */
    public function settings(WC_Shipping_Method $method): array
    {
        $settings = $method->instance_settings ?? [];

        return is_array($settings) ? $settings : [];
    }

    /**
     * Splits methods into "cash on delivery" and "prepaid" buckets.
     *
     * Flexible Shipping has no flag for this, so the split relies on a naming
     * convention configured by the shop — an empty needle disables it.
     *
     * @param  list<WC_Shipping_Method> $methods
     * @return array{prepaid: list<WC_Shipping_Method>, cod: list<WC_Shipping_Method>}
     */
    public function partitionByCashOnDelivery(array $methods, string $needle): array
    {
        $needle = trim($needle);

        if ($needle === '') {
            return ['prepaid' => array_values($methods), 'cod' => []];
        }

        $prepaid = [];
        $cod     = [];

        foreach ($methods as $method) {
            if (stripos((string) $method->get_title(), $needle) !== false) {
                $cod[] = $method;
                continue;
            }

            $prepaid[] = $method;
        }

        return ['prepaid' => $prepaid, 'cod' => $cod];
    }
}
