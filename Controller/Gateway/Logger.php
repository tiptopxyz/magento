<?php
declare(strict_types=1);

namespace Tiptop\PaymentGateway\Controller\Gateway;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;
use Tiptop\PaymentGateway\Gateway\Config\Config;

class Logger extends Action
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param Config $config
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        Config $config
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $result = ['success' => false];
        try {
            if ($this->config->getValue('debug')) {
                $data = $this->getRequest()->getContent();
                $decodedData = json_decode($data, true);

                if (is_array($decodedData)) {
                    $this->logger->info(__('Request: ') . json_encode($decodedData, JSON_PRETTY_PRINT));
                } else {
                    $this->logger->info(__('Request (raw): ') . $data);
                }
            }

            $result['success'] = true;
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
