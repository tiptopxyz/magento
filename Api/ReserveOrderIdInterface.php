<?php
namespace Tiptop\PaymentGateway\Api;

interface ReserveOrderIdInterface
{
    /**
     * Reserve Order ID for guest customers
     *
     * @param string $cartId
     * @return string
     */
    public function reserveOrderId($cartId);
}
