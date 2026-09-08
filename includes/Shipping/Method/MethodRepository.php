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
        $methods = array_filter(
            $this->readMethods($zone),
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
     * A zone's method instances, without the wp-admin baggage.
     *
     * `WC_Shipping_Zone::get_shipping_methods()` is written for the settings
     * screen: for every method that offers a settings modal it renders the
     * whole *admin options form* and hangs it off the object. On a storefront
     * page that is pure waste, and worse than waste - the form fields of a
     * third-party method may call functions that only exist inside wp-admin,
     * which takes the page down with a fatal rather than a missing table.
     *
     * Saying "we do not want the settings modal" through WooCommerce's own
     * filter skips that rendering entirely. The filter is removed immediately,
     * so nothing outside this call sees it.
     *
     * The first argument is `$enabled_only`; passing true would drop disabled
     * methods in the data store and leave {@see self::inZone()} nothing to
     * include when asked for them.
     *
     * @return array<int, mixed>
     */
    private function readMethods(WC_Shipping_Zone $zone): array
    {
        $withoutSettingsModal = static fn (mixed $supports, string $feature): bool
            => $feature === 'instance-settings-modal' ? false : (bool) $supports;

        add_filter('woocommerce_shipping_method_supports', $withoutSettingsModal, 10, 2);

        try {
            return $zone->get_shipping_methods(false);
        } finally {
            remove_filter('woocommerce_shipping_method_supports', $withoutSettingsModal, 10);
        }
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
