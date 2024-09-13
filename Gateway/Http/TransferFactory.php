<?php
declare(strict_types=1);

namespace Tiptop\PaymentGateway\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Tiptop\PaymentGateway\Gateway\Config\Config;

class TransferFactory implements TransferFactoryInterface
{
    /**
     * @var TransferBuilder
     */
    private $transferBuilder;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param TransferBuilder $transferBuilder
     * @param Config $config
     */
    public function __construct(
        TransferBuilder $transferBuilder,
        Config $config
    ) {
        $this->transferBuilder = $transferBuilder;
        $this->config = $config;
    }

    /**
     * Create transfer object
     *
     * @param array $request
     * @return TransferInterface
     */
    public function create(array $request)
    {
        $apiKey = $this->config->getServerApiKey();

        return $this->transferBuilder
            ->setBody(json_encode($request['body'] ?? [], JSON_UNESCAPED_SLASHES))
            ->setMethod($request['method'] ?? 'POST')
            ->setHeaders([
                'Content-Type' => 'application/json',
                'api-key' => $apiKey,
            ])
            ->setUri($request['uri'] ?? '')
            ->build();
    }
}
