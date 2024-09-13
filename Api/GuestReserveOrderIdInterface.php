<?php
namespace Tiptop\PaymentGateway\Api;

interface GuestReserveOrderIdInterface
{
    /**
     * Reserve Order ID for guest customers
     *
     * @param string $cartId
     * @return string
     */
    public function reserveOrderId($cartId);
}
