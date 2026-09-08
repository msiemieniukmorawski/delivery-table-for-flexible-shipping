<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Table;

use MSM\DeliveryTable\Shipping\Tax\TaxDisplay;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Everything the renderer needs to know, normalised once so that the
 * shortcode and the block cannot drift apart.
 */
final class TableRequest
{
    public const HEADINGS_AUTO   = 'auto';
    public const HEADINGS_ALWAYS = 'show';
    public const HEADINGS_NEVER  = 'hide';

    public function __construct(
        public readonly string $zone = '',
        public readonly bool $includeDisabled = false,
        public readonly string $cashOnDeliveryNeedle = '',
        public readonly string $zoneHeadings = self::HEADINGS_AUTO,
        public readonly bool $includeRestOfWorld = false,
        public readonly TaxDisplay $taxDisplay = TaxDisplay::Auto
    ) {
    }

    public function targetsSingleZone(): bool
    {
        return $this->zone !== '';
    }

    public function showsHeadings(int $renderedZoneCount): bool
    {
        return match ($this->zoneHeadings) {
            self::HEADINGS_ALWAYS => true,
            self::HEADINGS_NEVER  => false,
            default               => $renderedZoneCount > 1,
        };
    }

    public static function normaliseHeadings(string $value): string
    {
        $value = strtolower(trim($value));

        return in_array($value, [self::HEADINGS_ALWAYS, self::HEADINGS_NEVER], true)
            ? $value
            : self::HEADINGS_AUTO;
    }
}
