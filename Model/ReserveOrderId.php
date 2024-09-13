<?php
declare(strict_types=1);

namespace Tiptop\PaymentGateway\Model;

use Tiptop\PaymentGateway\Api\ReserveOrderIdInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Customer\Model\Session;

class ReserveOrderId implements ReserveOrderIdInterface
{
    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param Session $customerSession
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        Session $customerSession
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->customerSession = $customerSession;
    }

    /**
     * @inheritDoc
     */
    public function reserveOrderId($cartId)
    {
        $quote = $this->quoteRepository->getActive($cartId);

        if (!$quote->getReservedOrderId()) {
            $quote->reserveOrderId();
            $this->quoteRepository->save($quote);
        }

        return $quote->getReservedOrderId();
    }
}
