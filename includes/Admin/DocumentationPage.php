<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Admin;

use MSM\DeliveryTable\Assets\AssetManager;
use MSM\DeliveryTable\Contracts\Registrable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The plugin's only admin screen: how to use the shortcode and the block.
 *
 * There is nothing to configure — every option lives in Flexible Shipping —
 * so this registers no settings and saves nothing.
 */
final class DocumentationPage implements Registrable
{
    public const MENU_SLUG = 'delivery-table';
    public const CAPABILITY = 'manage_woocommerce';

    private const VIEW = 'includes/Admin/views/documentation.php';

    public function __construct(
        private readonly string $basePath,
        private readonly AssetManager $assets
    ) {
    }

    public function register(): void
    {
        add_action('admin_menu', $this->registerMenu(...));
        add_filter(
            'plugin_action_links_' . plugin_basename(\MSM\DeliveryTable\FILE),
            $this->addActionLink(...)
        );
    }

    public function registerMenu(): void
    {
        $hookSuffix = add_submenu_page(
            'woocommerce',
            __('Delivery Table', 'delivery-table-for-flexible-shipping'),
            __('Delivery Table', 'delivery-table-for-flexible-shipping'),
            self::CAPABILITY,
            self::MENU_SLUG,
            $this->render(...)
        );

        if (is_string($hookSuffix)) {
            add_action('load-' . $hookSuffix, $this->onLoad(...));
        }
    }

    public function onLoad(): void
    {
        add_action('admin_enqueue_scripts', $this->assets->enqueueStyle(...));
    }

    public function render(): void
    {
        if (!current_user_can(self::CAPABILITY)) {
            return;
        }

        $view = $this->basePath . self::VIEW;

        if (is_readable($view)) {
            require $view;
        }
    }

    public static function url(): string
    {
        return add_query_arg(['page' => self::MENU_SLUG], admin_url('admin.php'));
    }

    /**
     * @param  array<int|string, string> $links
     * @return array<int|string, string>
     */
    public function addActionLink(array $links): array
    {
        array_unshift(
            $links,
            sprintf(
                '<a href="%s">%s</a>',
                esc_url(self::url()),
                esc_html__('How to use it', 'delivery-table-for-flexible-shipping')
            )
        );

        return $links;
    }
}
