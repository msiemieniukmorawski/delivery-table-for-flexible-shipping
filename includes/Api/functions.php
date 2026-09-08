<?php

/**
 * Public procedural API.
 *
 * These are the only global functions the plugin defines. They are thin
 * wrappers over the container, so themes and snippets have a stable surface to
 * call whatever happens to the class names behind it.
 *
 * @package MSM\DeliveryTable
 */

declare(strict_types=1);

use MSM\DeliveryTable\Container;
use MSM\DeliveryTable\Plugin;
use MSM\DeliveryTable\Table\TableRequest;

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('dtfs')) {
    /**
     * The plugin's service container.
     */
    function dtfs(): Container
    {
        return Plugin::container();
    }
}

if (!function_exists('dtfs_shipping_table')) {
    /**
     * Renders the delivery price table.
     *
     * @param array{
     *     zone?: string,
     *     show_disabled?: bool,
     *     cod_prefix?: string,
     *     zone_headings?: string,
     *     include_rest_of_world?: bool
     * } $args
     * @param bool $echo Print instead of returning.
     */
    function dtfs_shipping_table(array $args = [], bool $echo = true): string
    {
        if (!function_exists('WC')) {
            return '';
        }

        $html = dtfs()->tableRenderer()->render(
            new TableRequest(
                zone: trim((string) ($args['zone'] ?? '')),
                includeDisabled: (bool) ($args['show_disabled'] ?? false),
                cashOnDeliveryNeedle: trim((string) ($args['cod_prefix'] ?? '')),
                zoneHeadings: TableRequest::normaliseHeadings((string) ($args['zone_headings'] ?? '')),
                includeRestOfWorld: (bool) ($args['include_rest_of_world'] ?? false)
            )
        );

        if ($echo) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the renderer escapes its output.
            echo $html;
        }

        return $html;
    }
}
