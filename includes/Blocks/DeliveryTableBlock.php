<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Blocks;

use MSM\DeliveryTable\Contracts\Registrable;
use MSM\DeliveryTable\Table\DeliveryTableRenderer;
use MSM\DeliveryTable\Table\TableRequest;
use WC_Shipping_Zones;
use WP_Block_Type;
use WP_Block_Type_Registry;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dynamic Gutenberg block wrapping {@see DeliveryTableRenderer}.
 *
 * The block and the shortcode share one renderer, so the two cannot drift.
 */
final class DeliveryTableBlock implements Registrable
{
    public const NAME = 'dtfs/shipping-table';

    private const BUILD_DIRECTORY = 'build/shipping-table';

    public function __construct(
        private readonly DeliveryTableRenderer $renderer,
        private readonly string $basePath
    ) {
    }

    public function register(): void
    {
        add_action('init', $this->registerBlockType(...));
        add_action('enqueue_block_editor_assets', $this->provideEditorData(...));
    }

    public function registerBlockType(): void
    {
        $metadataDirectory = $this->basePath . self::BUILD_DIRECTORY;

        if (!is_readable($metadataDirectory . '/block.json')) {
            return;
        }

        register_block_type($metadataDirectory, ['render_callback' => $this->render(...)]);

        $handle = $this->editorScriptHandle();

        if ($handle !== null) {
            wp_set_script_translations(
                $handle,
                'delivery-table-for-flexible-shipping',
                $this->basePath . 'languages'
            );
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function render(array $attributes): string
    {
        return $this->renderer->render(
            new TableRequest(
                zone: trim((string) ($attributes['zone'] ?? '')),
                includeDisabled: !empty($attributes['showDisabled']),
                cashOnDeliveryNeedle: trim((string) ($attributes['codPrefix'] ?? '')),
                zoneHeadings: TableRequest::normaliseHeadings((string) ($attributes['zoneHeadings'] ?? '')),
                includeRestOfWorld: !empty($attributes['includeRestOfWorld'])
            )
        );
    }

    /**
     * Feeds the zone dropdown in the block inspector.
     */
    public function provideEditorData(): void
    {
        $handle = $this->editorScriptHandle();

        if ($handle === null || !current_user_can('edit_posts') || !class_exists(WC_Shipping_Zones::class)) {
            return;
        }

        wp_add_inline_script(
            $handle,
            'window.dtfsBlockEditor = ' . wp_json_encode(['zones' => $this->zoneOptions()]) . ';',
            'before'
        );
    }

    /** @return list<array{value: string, label: string}> */
    private function zoneOptions(): array
    {
        $options = [];

        foreach (WC_Shipping_Zones::get_zones() as $zoneData) {
            if (!isset($zoneData['zone_id'])) {
                continue;
            }

            $options[] = [
                'value' => (string) $zoneData['zone_id'],
                'label' => (string) ($zoneData['zone_name'] ?? $zoneData['zone_id']),
            ];
        }

        return $options;
    }

    /**
     * The handle WordPress generated from `block.json`, read back rather than
     * reconstructed from the block name by hand.
     */
    private function editorScriptHandle(): ?string
    {
        $blockType = WP_Block_Type_Registry::get_instance()->get_registered(self::NAME);

        if (!$blockType instanceof WP_Block_Type) {
            return null;
        }

        return $blockType->editor_script_handles[0] ?? null;
    }
}
