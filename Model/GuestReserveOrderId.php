<?php
declare(strict_types=1);

namespace Tiptop\PaymentGateway\Model;

use Tiptop\PaymentGateway\Api\GuestReserveOrderIdInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;

class GuestReserveOrderId implements GuestReserveOrderIdInterface
{
    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }

    /**
     * @inheritDoc
     */
    public function reserveOrderId($cartId)
    {
        $quoteId = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id')->getQuoteId();
        $quote = $this->quoteRepository->get($quoteId);

        if (!$quote->getReservedOrderId()) {
            $quote->reserveOrderId();
            $this->quoteRepository->save($quote);
        }

        return $quote->getReservedOrderId();
    }
}
