<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Table;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * One shipping method and its price in every column.
 */
final class TableRow
{
    /** @param list<TableCell> $cells */
    public function __construct(
        public readonly string $label,
        public readonly array $cells
    ) {
    }
}
