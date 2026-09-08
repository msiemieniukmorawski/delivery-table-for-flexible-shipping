<?php

declare(strict_types=1);

namespace MSM\DeliveryTable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Boots the plugin.
 *
 * Nothing happens at include time beyond hook registration, so a shop can load
 * the file without paying for a single WooCommerce query.
 */
final class Plugin
{
    private static ?self $instance = null;

    private readonly Container $container;

    private bool $booted = false;

    private function __construct()
    {
        $this->container = new Container(PATH, URL, VERSION);
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Service locator for the one place that cannot receive injected
     * dependencies: the procedural helper functions.
     */
    public static function container(): Container
    {
        return self::instance()->services();
    }

    public function services(): Container
    {
        return $this->container;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        require_once PATH . 'includes/Api/functions.php';

        add_action('init', $this->loadTranslations(...), 0);
        add_action('plugins_loaded', $this->registerServices(...));
    }

    public function loadTranslations(): void
    {
        load_plugin_textdomain(
            TEXT_DOMAIN,
            false,
            dirname(plugin_basename(FILE)) . '/languages'
        );
    }

    public function registerServices(): void
    {
        $requirements = $this->container->requirements();

        if (!$requirements->areMet()) {
            add_action('admin_notices', $requirements->renderAdminNotice(...));

            return;
        }

        foreach ($this->container->services() as $service) {
            $service->register();
        }

        /**
         * Fires once every plugin service is hooked up.
         *
         * @param Container $container Service container, for add-ons and snippets.
         */
        do_action('dtfs_booted', $this->container);
    }
}
