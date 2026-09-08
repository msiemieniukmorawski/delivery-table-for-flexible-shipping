<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Assets;

use MSM\DeliveryTable\Blocks\DeliveryTableBlock;
use MSM\DeliveryTable\Contracts\Registrable;
use MSM\DeliveryTable\Shortcodes\DeliveryTableShortcode;
use WP_Post;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Owns the plugin's single stylesheet.
 *
 * The style is *registered* on every request but only *enqueued* when
 * something on the page actually needs it. Detecting the need during
 * `wp_enqueue_scripts` lets it go out in `<head>`; the shortcode and the block
 * still enqueue defensively when they render, which lands it in the footer at
 * worst.
 */
final class AssetManager implements Registrable
{
    public const STYLE_HANDLE = 'dtfs-delivery-table';

    private const STYLESHEET = 'assets/css/delivery-table.css';

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $basePath,
        private readonly string $version
    ) {
    }

    public function register(): void
    {
        add_action('wp_enqueue_scripts', $this->registerFrontend(...));
        add_action('admin_enqueue_scripts', $this->registerAdmin(...));
    }

    public function registerFrontend(): void
    {
        $this->registerStyle();

        if ($this->currentPageNeedsStyle()) {
            $this->enqueueStyle();
        }
    }

    public function registerAdmin(): void
    {
        $this->registerStyle();
    }

    public function enqueueStyle(): void
    {
        wp_enqueue_style(self::STYLE_HANDLE);
    }

    private function registerStyle(): void
    {
        if (wp_style_is(self::STYLE_HANDLE, 'registered')) {
            return;
        }

        wp_register_style(
            self::STYLE_HANDLE,
            $this->baseUrl . self::STYLESHEET,
            [],
            $this->assetVersion()
        );
    }

    /**
     * Whether the queried post embeds the table.
     */
    private function currentPageNeedsStyle(): bool
    {
        $post = get_post();

        if (!$post instanceof WP_Post) {
            return false;
        }

        return has_shortcode((string) $post->post_content, DeliveryTableShortcode::TAG)
            || has_block(DeliveryTableBlock::NAME, $post);
    }

    /**
     * File modification time in debug mode, so a hard refresh is enough while
     * developing; the plugin version everywhere else, for long-lived caching.
     */
    private function assetVersion(): string
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return $this->version;
        }

        $file = $this->basePath . self::STYLESHEET;

        return is_readable($file) ? (string) filemtime($file) : $this->version;
    }
}
