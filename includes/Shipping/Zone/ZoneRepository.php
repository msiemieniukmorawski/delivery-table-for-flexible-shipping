<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Shipping\Zone;

use WC_Shipping_Zone;
use WC_Shipping_Zones;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Every way the plugin needs to get hold of a shipping zone.
 *
 * Zone lookups go through `WC_Shipping_Zones` rather than `new WC_Shipping_Zone($id)`
 * because the latter throws for ids that no longer exist.
 */
final class ZoneRepository
{
    /** Id of WooCommerce's implicit "Locations not covered by your other zones" zone. */
    public const REST_OF_WORLD_ID = 0;

    /**
     * Zone referenced by a shortcode/block attribute: numeric id first, then a
     * case-insensitive name match. Returns null when nothing matches, so the
     * caller can decide whether to fall back or to show an error.
     */
    public function find(?string $identifier): ?WC_Shipping_Zone
    {
        $identifier = trim((string) $identifier);

        if ($identifier === '') {
            return null;
        }

        if (ctype_digit($identifier)) {
            return $this->byId((int) $identifier);
        }

        return $this->byName($identifier);
    }

    public function byId(int $zoneId): ?WC_Shipping_Zone
    {
        $zone = WC_Shipping_Zones::get_zone($zoneId);

        return $zone instanceof WC_Shipping_Zone ? $zone : null;
    }

    public function byName(string $name): ?WC_Shipping_Zone
    {
        foreach ($this->enumerate() as $zone) {
            if (strcasecmp((string) $zone->get_zone_name(), $name) === 0) {
                return $zone;
            }
        }

        return null;
    }

    /**
     * All zones in the order the shop manager arranged them.
     *
     * @return list<WC_Shipping_Zone>
     */
    public function all(bool $includeRestOfWorld = false): array
    {
        $zones = $this->enumerate();

        if ($includeRestOfWorld) {
            $zones[] = $this->restOfWorld();
        }

        return $zones;
    }

    /**
     * Every zone the shop has defined.
     *
     * Deliberately not `WC_Shipping_Zones::get_zones()`: that one defaults to
     * the "admin" context, which eagerly builds each method's *admin settings
     * HTML* - on the storefront, on every page view. Besides being wasted work,
     * it runs form-field code that has no business running outside wp-admin,
     * and a method whose settings screen touches a wp-admin-only function takes
     * the whole page down with it. Only zone objects are needed here.
     *
     * @return list<WC_Shipping_Zone>
     */
    private function enumerate(): array
    {
        return array_values(
            array_filter(
                WC_Shipping_Zones::get_shipping_zones(),
                static fn (mixed $zone): bool => $zone instanceof WC_Shipping_Zone
            )
        );
    }

    public function restOfWorld(): WC_Shipping_Zone
    {
        return new WC_Shipping_Zone(self::REST_OF_WORLD_ID);
    }

    /**
     * Zone matching the customer's current shipping package.
     */
    public function forCart(): ?WC_Shipping_Zone
    {
        if (!function_exists('WC') || WC()->cart === null) {
            return null;
        }

        $packages = WC()->cart->get_shipping_packages();

        if ($packages === []) {
            return null;
        }

        $zone = WC_Shipping_Zones::get_zone_matching_package(reset($packages));

        return $zone instanceof WC_Shipping_Zone ? $zone : null;
    }

    /**
     * Zone matching the shop's own address, the sanest guess when there is no
     * cart yet, e.g. on a product page or a static delivery information page.
     */
    public function forBaseCountry(): ?WC_Shipping_Zone
    {
        $base    = wc_get_base_location();
        $country = (string) ($base['country'] ?? '');

        if ($country === '') {
            return null;
        }

        $zone = WC_Shipping_Zones::get_zone_matching_package([
            'destination' => [
                'country'   => $country,
                'state'     => (string) ($base['state'] ?? ''),
                'postcode'  => '',
                'city'      => '',
                'address'   => '',
                'address_2' => '',
            ],
        ]);

        return $zone instanceof WC_Shipping_Zone ? $zone : null;
    }

    /**
     * The zone a storefront widget should use: explicit attribute, then the
     * customer's cart, then the shop's base country.
     */
    public function resolve(?string $identifier = null): ?WC_Shipping_Zone
    {
        return $this->find($identifier)
            ?? $this->forCart()
            ?? $this->forBaseCountry();
    }
}
