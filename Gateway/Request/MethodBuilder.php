<?php
declare(strict_types=1);

namespace Tiptop\PaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;

class MethodBuilder implements BuilderInterface
{
    /**
     * @var string
     */
    private $method;

    /**
     * @param string $method
     */
    public function __construct(
        string $method = 'POST'
    ) {
        $this->method = $method;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {
        return [
            'method' => $this->method
        ];
    }
}
