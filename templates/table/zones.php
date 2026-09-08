<?php

/**
 * Delivery tables, grouped by region.
 *
 * Each block is one region and carries the anchor a flag list links to.
 *
 * @var array<int, array{id: string, label: string, countries: array<int, string>, tables: array<int, string>}> $blocks
 * @var bool                                                                                                   $showHeadings Whether to print region headings.
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

?>
<div class="dtfs-zones">
    <?php foreach ($blocks as $block) : ?>
        <?php
        // Space separated ISO codes, so a flag list can find its region.
        $countries = $block['countries'] === []
            ? ''
            : sprintf(' data-dtfs-countries="%s"', esc_attr(implode(' ', $block['countries'])));
        ?>
        <section class="dtfs-zone" id="<?php echo esc_attr($block['id']); ?>"<?php echo $countries; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_attr above. ?>>
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
