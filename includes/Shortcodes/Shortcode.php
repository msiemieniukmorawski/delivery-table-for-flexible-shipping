<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Shortcodes;

use MSM\DeliveryTable\Contracts\Registrable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shared plumbing for the plugin's shortcodes.
 *
 * Note the deliberately untyped `$rawAttributes`: WordPress hands a shortcode
 * callback an *empty string* rather than an empty array when the tag is used
 * without attributes, so an `array` type hint here turns
 * `[dtfs_shipping_table]` into a fatal error.
 */
abstract class Shortcode implements Registrable
{
    private const TRUTHY = ['1', 'yes', 'true', 'on'];

    public function register(): void
    {
        add_shortcode($this->tag(), $this->handle(...));
    }

    /**
     * @param array<string, string>|string $rawAttributes
     */
    final public function handle(mixed $rawAttributes = [], ?string $content = null): string
    {
        return $this->output(
            shortcode_atts(
                $this->defaults(),
                is_array($rawAttributes) ? $rawAttributes : [],
                $this->tag()
            ),
            $content
        );
    }

    /** The registered tag, also the `shortcode_atts_{$tag}` filter suffix. */
    abstract public function tag(): string;

    /** @return array<string, string> */
    abstract protected function defaults(): array;

    /** @param array<string, string> $attributes */
    abstract protected function output(array $attributes, ?string $content): string;

    final protected static function toBool(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), self::TRUTHY, true);
    }

    final protected static function toText(mixed $value): string
    {
        return trim((string) $value);
    }
}
