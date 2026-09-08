<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Shipping\Zone;

use WC_Shipping_Zone;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gathers shipping zones into the regions a customer actually asks about.
 *
 * Two zones covering Poland are one answer to "what does delivery to Poland
 * cost?", so they are rendered together under one heading and one anchor, in
 * the position of whichever of them the shop put first.
 *
 * A zone naming a continent is shorthand for its member countries, so it joins
 * each of their regions. A zone naming several countries outright is a
 * deliberate pairing and keeps its own combined region instead of being split.
 */
final class ZoneGrouper
{
    /** Prefix for every anchor this class mints. */
    public const ID_PREFIX = 'dtfs-region-';

    /** Anchor for the zone that catches everything the others do not. */
    private const ELSEWHERE = 'elsewhere';

    /** Beyond this an id stops being readable and is hashed instead. */
    private const MAX_SLUG_LENGTH = 40;

    public function __construct(private readonly ZoneLabeller $labeller)
    {
    }

    /**
     * @param  list<WC_Shipping_Zone> $zones In the shop's own order.
     * @return list<ZoneGroup>        In the order each region first appears.
     */
    public function group(array $zones): array
    {
        /** @var array<string, array{label: string, countries: list<string>}> $regions */
        $regions = [];

        /** @var list<array{zone: WC_Shipping_Zone, keys: list<string>, coverage: list<string>, continental: bool}> $entries */
        $entries = [];

        foreach ($zones as $zone) {
            $named = $this->regionsOf($zone);

            foreach ($named as $key => $region) {
                $regions[$key] ??= $region;
            }

            $entries[] = [
                'zone'        => $zone,
                'keys'        => array_keys($named),
                'coverage'    => $this->coverageOf($zone),
                'continental' => $this->namesAContinent($zone),
            ];
        }

        $built = [];

        foreach ($regions as $key => $region) {
            $members = [];

            foreach ($entries as $entry) {
                if ($this->belongs($entry, $key, $region['countries'])) {
                    $members[] = $entry['zone'];
                }
            }

            $built[] = new ZoneGroup(
                self::ID_PREFIX . $key,
                $region['label'],
                $region['countries'],
                $members
            );
        }

        return $built;
    }

    /**
     * A zone belongs to a region either because it named it, or because it is
     * a continent zone wide enough to cover the whole of it - which is what
     * puts a "Europe" zone into a "Poland, Germany" region as well as into
     * Poland's and Germany's own.
     *
     * @param array{zone: WC_Shipping_Zone, keys: list<string>, coverage: list<string>, continental: bool} $entry
     * @param list<string>                                                                                $countries
     */
    private function belongs(array $entry, string $key, array $countries): bool
    {
        if (in_array($key, $entry['keys'], true)) {
            return true;
        }

        if (!$entry['continental'] || $countries === []) {
            return false;
        }

        return array_diff($countries, $entry['coverage']) === [];
    }

    private function namesAContinent(WC_Shipping_Zone $zone): bool
    {
        foreach ($zone->get_zone_locations() as $location) {
            if ((string) $location->type === 'continent') {
                return true;
            }
        }

        return false;
    }

    /**
     * Every country a zone reaches, with continents expanded.
     *
     * @return list<string>
     */
    private function coverageOf(WC_Shipping_Zone $zone): array
    {
        $countries = [];

        foreach ($zone->get_zone_locations() as $location) {
            $code = (string) $location->code;

            match ((string) $location->type) {
                'continent'       => array_push($countries, ...$this->countriesOfContinent($code)),
                'country', 'state' => $countries[] = strtoupper(explode(':', $code, 2)[0]),
                default           => null,
            };
        }

        return array_values(array_unique($countries));
    }

    /**
     * Every region a single zone belongs to, keyed by anchor slug.
     *
     * @return array<string, array{label: string, countries: list<string>}>
     */
    private function regionsOf(WC_Shipping_Zone $zone): array
    {
        $locations = $zone->get_zone_locations();

        if ($locations === []) {
            return [$this->keyForZoneWithoutLocations($zone) => [
                'label'     => (string) $zone->get_zone_name(),
                'countries' => [],
            ]];
        }

        $regions = [];
        $own     = [];

        foreach ($locations as $location) {
            $code = (string) $location->code;

            // Continents stand for their members, so the zone joins each one.
            if ((string) $location->type === 'continent') {
                foreach ($this->countriesOfContinent($code) as $country) {
                    $regions[$this->slug($country)] = [
                        'label'     => $this->countryLabel($country),
                        'countries' => [$country],
                    ];
                }

                continue;
            }

            // Postcodes narrow a zone down rather than naming it.
            if (in_array((string) $location->type, ['country', 'state'], true)) {
                $own[] = $code;
            }
        }

        if ($own !== []) {
            $regions[$this->compositeKey($own)] = [
                'label'     => $this->labeller->label($zone),
                'countries' => $this->countryCodes($own),
            ];
        }

        return $regions === []
            ? [$this->keyForZoneWithoutLocations($zone) => [
                'label'     => (string) $zone->get_zone_name(),
                'countries' => [],
            ]]
            : $regions;
    }

    /**
     * A zone with nothing but postcodes, or nothing at all, shares its region
     * with no one, so it keeps an anchor of its own rather than being merged
     * into a meaningless "everywhere" bucket.
     */
    private function keyForZoneWithoutLocations(WC_Shipping_Zone $zone): string
    {
        $id = (int) $zone->get_id();

        return $id === ZoneRepository::REST_OF_WORLD_ID ? self::ELSEWHERE : 'zone-' . $id;
    }

    /**
     * @param  list<string> $codes Country and state codes as WooCommerce stores them.
     */
    private function compositeKey(array $codes): string
    {
        $slugs = array_map($this->slug(...), $codes);

        sort($slugs);

        $slug = implode('-', array_unique($slugs));

        // A zone listing a dozen countries would otherwise mint an unusable id.
        return strlen($slug) <= self::MAX_SLUG_LENGTH
            ? $slug
            : $slugs[0] . '-' . substr(md5($slug), 0, 6);
    }

    /** @return list<string> Distinct ISO country codes behind country and state entries. */
    private function countryCodes(array $codes): array
    {
        $countries = array_map(
            static fn (string $code): string => strtoupper(explode(':', $code, 2)[0]),
            $codes
        );

        return array_values(array_unique($countries));
    }

    /** @return list<string> */
    private function countriesOfContinent(string $code): array
    {
        $continents = function_exists('WC') && WC()->countries !== null
            ? WC()->countries->get_continents()
            : [];

        return array_values((array) ($continents[$code]['countries'] ?? []));
    }

    private function countryLabel(string $code): string
    {
        $countries = function_exists('WC') && WC()->countries !== null
            ? WC()->countries->get_countries()
            : [];

        return html_entity_decode((string) ($countries[$code] ?? $code), ENT_QUOTES, 'UTF-8');
    }

    private function slug(string $code): string
    {
        return strtolower(str_replace(':', '-', $code));
    }
}
