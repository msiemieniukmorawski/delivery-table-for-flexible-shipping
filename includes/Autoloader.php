<?php

declare(strict_types=1);

namespace MSM\DeliveryTable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Minimal PSR-4 autoloader.
 *
 * The plugin ships without a Composer runtime dependency on purpose: a
 * WordPress plugin that drops its own `vendor/` directory into a shared
 * install is a well known source of class collisions.
 */
final class Autoloader
{
    private function __construct(
        private readonly string $prefix,
        private readonly string $baseDirectory
    ) {
    }

    /**
     * @param string $prefix        Namespace prefix, e.g. `MSM\DeliveryTable`.
     * @param string $baseDirectory Directory that maps to the prefix root.
     */
    public static function forNamespace(string $prefix, string $baseDirectory): self
    {
        $separator = '\\';

        return new self(
            rtrim($prefix, $separator) . $separator,
            rtrim(str_replace($separator, '/', $baseDirectory), '/') . '/'
        );
    }

    public function register(): void
    {
        spl_autoload_register($this->load(...));
    }

    public function load(string $class): void
    {
        if (!str_starts_with($class, $this->prefix)) {
            return;
        }

        $relative = substr($class, strlen($this->prefix));
        $file     = $this->baseDirectory . str_replace('\\', '/', $relative) . '.php';

        if (is_readable($file)) {
            require_once $file;
        }
    }
}
