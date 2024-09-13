<?php
declare(strict_types=1);

namespace Tiptop\PaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

class RefundBodyBuilder implements BuilderInterface
{
    /**
     * Build request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $buildSubject['payment'];
        $payment = $paymentDO->getPayment();

        return [
            'body' => [
                'orderID' => $payment->getLastTransId(),
                'amount' => (int)($payment->getAmountPaid() * 100)
            ]
        ];
    }
}
