<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Contracts;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * A service that binds itself to WordPress hooks.
 *
 * Every service is constructed first and hooked afterwards, so that
 * construction stays free of side effects and remains unit testable.
 */
interface Registrable
{
    public function register(): void;
}
