<?php

declare(strict_types=1);

namespace MSM\DeliveryTable\Shipping\FreeShipping;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The order value at which a shipping method becomes free.
 */
final class FreeShippingThreshold
{
    /**
     * @param float $amount         Order value from which shipping is free.
     * @param bool  $requiresCoupon Whether reaching the amount is necessary but not
     *                              sufficient, because Flexible Shipping is set to
     *                              "a valid free shipping coupon AND a minimum order
     *                              amount". The table must then present the offer as
     *                              conditional rather than promise free delivery.
     */
    public function __construct(
        public readonly float $amount,
        public readonly bool $requiresCoupon = false
    ) {
    }
}
