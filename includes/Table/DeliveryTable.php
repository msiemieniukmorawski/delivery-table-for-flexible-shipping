<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Table;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * A fully computed table, ready to be handed to a view.
 */
final class DeliveryTable
{
    /**
     * @param list<ValueInterval> $columns
     * @param list<TableRow>      $rows
     */
    public function __construct(
        public readonly string $heading,
        public readonly array $columns,
        public readonly array $rows
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->rows === [];
    }
}
