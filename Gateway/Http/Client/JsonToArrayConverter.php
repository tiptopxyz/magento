<?php
declare(strict_types=1);

namespace Tiptop\PaymentGateway\Gateway\Http\Client;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Payment\Gateway\Http\ConverterInterface;

class JsonToArrayConverter implements ConverterInterface
{
    /**
     * @var Json
     */
    private $json;

    /**
     * @param Json $json
     */
    public function __construct(
        Json $json
    ) {
        $this->json = $json;
    }

    /**
     * @inheritDoc
     */
    public function convert($response)
    {
        if (!is_string($response) || empty($response)) {
            throw new ConverterException(__('Invalid response type'));
        }

        return $this->json->unserialize($response);
    }
}
