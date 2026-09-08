<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Shipping\Zone;

use WC_Shipping_Zone;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Builds the heading printed above a zone's table.
 *
 * Zone *names* are whatever the shop manager typed in wp-admin and are never
 * translated. Zone *regions* come from WooCommerce's own country/state lists,
 * so they follow the storefront language — "Germany" in an English shop,
 * "Deutschland" in a German one. Postcodes are location limits rather than
 * regions and are deliberately left out.
 */
final class ZoneLabeller
{
    private const REGION_SEPARATOR = ', ';

    public function label(WC_Shipping_Zone $zone): string
    {
        $regions = $this->regions($zone);

        if ($regions === []) {
            return (string) $zone->get_zone_name();
        }

        return implode(self::REGION_SEPARATOR, $regions);
    }

    /** @return list<string> */
    public function regions(WC_Shipping_Zone $zone): array
    {
        $countries = function_exists('WC') ? WC()->countries : null;

        if ($countries === null) {
            return [];
        }

        $continents = $countries->get_continents();
        $countryMap = $countries->get_countries();
        $stateMap   = $countries->get_states();

        $labels = [];

        foreach ($zone->get_zone_locations() as $location) {
            $code = (string) $location->code;

            $labels[] = match ((string) $location->type) {
                'continent' => (string) ($continents[$code]['name'] ?? $code),
                'country'   => (string) ($countryMap[$code] ?? $code),
                'state'     => $this->stateLabel($stateMap, $code),
                default     => '', // Postcodes narrow a zone down; they do not name it.
            };
        }

        $labels = array_map(
            static fn (string $label): string => html_entity_decode($label, ENT_QUOTES, 'UTF-8'),
            array_filter($labels)
        );

        return array_values(array_unique($labels));
    }

    /** @param array<string, array<string, string>> $stateMap */
    private function stateLabel(array $stateMap, string $code): string
    {
        [$country, $state] = array_pad(explode(':', $code, 2), 2, '');

        return (string) ($stateMap[$country][$state] ?? $code);
    }
}
