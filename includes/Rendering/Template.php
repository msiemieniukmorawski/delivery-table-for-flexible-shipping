<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Rendering;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Loads storefront view files and keeps output buffering out of the domain
 * classes.
 *
 * Every template under `templates/` can be overridden by copying it into
 * `wp-content/themes/<theme>/delivery-table-for-flexible-shipping/<template>.php`,
 * child theme first, exactly like WooCommerce templates behave. Only storefront
 * markup goes through here - wp-admin views live in `includes/Admin/views/` and
 * are required directly, because a theme has no business overriding them.
 */
final class Template
{
    private const THEME_DIRECTORY = 'delivery-table-for-flexible-shipping';

    /** @var array<string, string> */
    private array $resolved = [];

    public function __construct(private readonly string $basePath)
    {
    }

    /**
     * @param string               $template Path relative to `templates/`, without `.php`.
     * @param array<string, mixed> $data     Variables exposed to the view.
     */
    public function render(string $template, array $data = []): string
    {
        $file = $this->locate($template);

        if ($file === null) {
            return '';
        }

        ob_start();

        (static function (string $__file, array $__data): void {
            // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- views read better with named variables.
            extract($__data, EXTR_SKIP);

            require $__file;
        })($file, $data);

        return (string) ob_get_clean();
    }

    /** @param array<string, mixed> $data */
    public function output(string $template, array $data = []): void
    {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- templates escape their own output.
        echo $this->render($template, $data);
    }

    /**
     * Absolute path of a template, honouring theme overrides.
     */
    public function locate(string $template): ?string
    {
        if (array_key_exists($template, $this->resolved)) {
            return $this->resolved[$template] ?: null;
        }

        $relative = ltrim($template, '/') . '.php';

        $candidates = [
            trailingslashit(get_stylesheet_directory()) . self::THEME_DIRECTORY . '/' . $relative,
            trailingslashit(get_template_directory()) . self::THEME_DIRECTORY . '/' . $relative,
            $this->basePath . $relative,
        ];

        foreach ($candidates as $candidate) {
            if (is_readable($candidate)) {
                return $this->resolved[$template] = $candidate;
            }
        }

        $this->resolved[$template] = '';

        return null;
    }
}
