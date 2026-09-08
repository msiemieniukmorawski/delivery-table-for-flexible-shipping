<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Shortcodes;

use MSM\DeliveryTable\Shipping\Tax\TaxDisplay;
use MSM\DeliveryTable\Table\DeliveryTableRenderer;
use MSM\DeliveryTable\Table\TableRequest;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * `[dtfs_shipping_table]` - the delivery price table.
 */
final class DeliveryTableShortcode extends Shortcode
{
    public const TAG = 'dtfs_shipping_table';

    public function __construct(private readonly DeliveryTableRenderer $renderer)
    {
    }

    public function tag(): string
    {
        return self::TAG;
    }

    protected function defaults(): array
    {
        return [
            'zone'                  => '',
            'show_disabled'         => '0',
            // Empty means one table; splitting off cash on delivery is opt-in.
            'cod_prefix'            => '',
            'zone_headings'         => TableRequest::HEADINGS_AUTO,
            'tax'                   => TaxDisplay::Auto->value,
            'include_rest_of_world' => 'no',
        ];
    }

    protected function output(array $attributes, ?string $content): string
    {
        return $this->renderer->render(
            new TableRequest(
                zone: self::toText($attributes['zone']),
                includeDisabled: self::toBool($attributes['show_disabled']),
                cashOnDeliveryNeedle: self::toText($attributes['cod_prefix']),
                zoneHeadings: TableRequest::normaliseHeadings((string) $attributes['zone_headings']),
                includeRestOfWorld: self::toBool($attributes['include_rest_of_world']),
                taxDisplay: TaxDisplay::fromAttribute((string) $attributes['tax'])
            )
        );
    }
}
