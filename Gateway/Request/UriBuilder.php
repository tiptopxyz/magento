<?php
declare(strict_types=1);

namespace Tiptop\PaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Config\Config;
use Magento\Payment\Gateway\Request\BuilderInterface;

class UriBuilder implements BuilderInterface
{
    public const API_CAPTURE = 'v1/direct/order/capture-request';
    public const API_VOID = 'v1/direct/order/void-request';
    public const API_REFUND = 'v1/direct/order/refund-request';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var string
     */
    private $api;

    /**
     * @param Config $config
     * @param string $api
     */
    public function __construct(
        Config $config,
        string $api
    ) {
        $this->config = $config;
        $this->api = $api;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {
        $baseUrl = $this->config->getValue('api_base_url');

        return [
            'uri' => $baseUrl . $this->api
        ];
    }
}
