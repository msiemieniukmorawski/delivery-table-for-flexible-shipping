<?php

/**
 * Delivery tables, grouped by shipping zone.
 *
 * @var array<int, array{label: string, tables: array<int, string>}> $blocks       Zone label and rendered tables.
 * @var bool                                                        $showHeadings Whether to print zone headings.
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

?>
<div class="dtfs-zones">
    <?php foreach ($blocks as $block) : ?>
        <section class="dtfs-zone">
            <?php if ($showHeadings && $block['label'] !== '') : ?>
                <h2 class="dtfs-zone__title"><?php echo esc_html($block['label']); ?></h2>
            <?php endif; ?>

            <?php
            foreach ($block['tables'] as $tableHtml) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by table/table.php.
                echo $tableHtml;
            }
            ?>
        </section>
    <?php endforeach; ?>
</div>
