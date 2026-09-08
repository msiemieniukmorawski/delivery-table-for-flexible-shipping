<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Table;

use MSM\DeliveryTable\Assets\AssetManager;
use MSM\DeliveryTable\Rendering\Template;
use MSM\DeliveryTable\Shipping\Method\MethodRepository;
use MSM\DeliveryTable\Shipping\Zone\ZoneLabeller;
use MSM\DeliveryTable\Shipping\Zone\ZoneRepository;
use WC_Shipping_Zone;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the delivery price table for one zone or for the whole shop.
 *
 * The shortcode and the Gutenberg block are thin wrappers around this class,
 * so both stay identical by construction.
 */
final class DeliveryTableRenderer
{
    public function __construct(
        private readonly ZoneRepository $zones,
        private readonly ZoneLabeller $labeller,
        private readonly MethodRepository $methods,
        private readonly TableFactory $tables,
        private readonly Template $template,
        private readonly AssetManager $assets
    ) {
    }

    public function render(TableRequest $request): string
    {
        $this->assets->enqueueStyle();

        $zones = $this->zonesFor($request);

        if ($zones === []) {
            return $this->notice(__('No shipping zone found.', 'delivery-table-for-flexible-shipping'));
        }

        $blocks = $this->buildBlocks($zones, $request);

        /*
         * Shops that keep every carrier in "Locations not covered by your
         * other zones" would otherwise get an empty page, so fall back to it
         * when the explicit zones turned out to have nothing to show.
         */
        if ($blocks === [] && !$request->targetsSingleZone() && !$request->includeRestOfWorld) {
            $blocks = $this->buildBlocks([$this->zones->restOfWorld()], $request);
        }

        if ($blocks === []) {
            return $this->notice(
                __('No shipping methods to display.', 'delivery-table-for-flexible-shipping')
            );
        }

        return $this->template->render('table/zones', [
            'blocks'       => $blocks,
            'showHeadings' => $request->showsHeadings(count($blocks)),
        ]);
    }

    /** @return list<WC_Shipping_Zone> */
    private function zonesFor(TableRequest $request): array
    {
        if (!$request->targetsSingleZone()) {
            return $this->zones->all($request->includeRestOfWorld);
        }

        $zone = $this->zones->find($request->zone);

        return $zone !== null ? [$zone] : [];
    }

    /**
     * Tables are rendered here rather than inside the zones view so that both
     * `table/zones` and `table/table` stay independently overridable by a theme.
     *
     * @param  list<WC_Shipping_Zone> $zones
     * @return list<array{label: string, tables: list<string>}>
     */
    private function buildBlocks(array $zones, TableRequest $request): array
    {
        $blocks = [];

        foreach ($zones as $zone) {
            $tables = array_map(
                fn (DeliveryTable $table): string => $this->template->render('table/table', ['table' => $table]),
                $this->tablesFor($zone, $request)
            );

            if ($tables === []) {
                continue;
            }

            $blocks[] = [
                'label'  => $this->labeller->label($zone),
                'tables' => $tables,
            ];
        }

        return $blocks;
    }

    /** @return list<DeliveryTable> */
    private function tablesFor(WC_Shipping_Zone $zone, TableRequest $request): array
    {
        $methods = $this->methods->inZone($zone, $request->includeDisabled);

        if ($methods === []) {
            return [];
        }

        $groups = $this->methods->partitionByCashOnDelivery(
            $methods,
            $request->cashOnDeliveryNeedle
        );

        $headings = [
            'prepaid' => $groups['cod'] === []
                // A single table needs no "Prepaid" qualifier.
                ? __('Delivery', 'delivery-table-for-flexible-shipping')
                : __('Prepaid', 'delivery-table-for-flexible-shipping'),
            'cod'     => __('Cash on delivery', 'delivery-table-for-flexible-shipping'),
        ];

        $tables = [];

        foreach ($groups as $key => $groupMethods) {
            $table = $this->tables->create($headings[$key], $groupMethods);

            if ($table !== null && !$table->isEmpty()) {
                $tables[] = $table;
            }
        }

        return $tables;
    }

    private function notice(string $message): string
    {
        return '<p class="dtfs-message">' . esc_html($message) . '</p>';
    }
}
