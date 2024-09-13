<?php
declare(strict_types=1);

namespace Tiptop\PaymentGateway\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Tiptop\PaymentGateway\Gateway\Config\Config;

class LoaderScript extends Template
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Context $context
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context              $context,
        Config $config,
        array                $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
    }

    /**
     * Get the public key
     *
     * @return string
     */
    public function getPublicApiKey()
    {
        return $this->config->getPublicApiKey();
    }

    /**
     * Get the script URL
     *
     * @return string
     */
    public function getScriptUrl()
    {
        return $this->config->getScriptUrl();
    }
}
