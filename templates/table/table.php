<?php

/**
 * One delivery price table.
 *
 * @var \MSM\DeliveryTable\Table\DeliveryTable $table
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

?>
<div class="dtfs-table-wrap">
    <h3 class="dtfs-table__title"><?php echo esc_html($table->heading); ?></h3>

    <div class="dtfs-table-scroll">
        <table class="dtfs-table">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e('Delivery method', 'delivery-table-for-flexible-shipping'); ?></th>
                    <?php foreach ($table->columns as $column) : ?>
                        <th scope="col"><?php echo esc_html($column->label); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($table->rows as $row) : ?>
                    <tr>
                        <th scope="row" class="dtfs-table__method"><?php echo esc_html($row->label); ?></th>

                        <?php foreach ($row->cells as $index => $cell) : ?>
                            <td data-label="<?php echo esc_attr($table->columns[$index]->label ?? ''); ?>">
                                <?php
                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- TableCell escapes on construction.
                                echo $cell->html;
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($table->hasApproximateCosts()) : ?>
        <p class="dtfs-table__footnote">
            <?php
            esc_html_e(
                '* This price also depends on something this table cannot show, such as weight, item count or a coupon. The final cost is calculated at checkout.',
                'delivery-table-for-flexible-shipping'
            );
            ?>
        </p>
    <?php endif; ?>
</div>
