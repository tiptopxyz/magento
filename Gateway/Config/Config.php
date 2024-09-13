<?php
declare(strict_types=1);

namespace Tiptop\PaymentGateway\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Payment\Gateway\Config\Config as PaymentConfig;

class Config extends PaymentConfig
{
    public const METHOD_CODE = 'tiptop';

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     * @param string|null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor,
        $methodCode = self::METHOD_CODE,
        $pathPattern = PaymentConfig::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->encryptor = $encryptor;
    }

    /**
     * Get the mode
     *
     * @return string
     */
    public function isTestMode()
    {
        return $this->getValue('test_mode');
    }

    /**
     * Get the public key
     *
     * @return string
     */
    public function getPublicApiKey()
    {
        return $this->getValue(
            $this->isTestMode() ? 'test_api' : 'production_api'
        );
    }

    /**
     * Get the server key
     *
     * @return string
     */
    public function getServerApiKey()
    {
        return $this->encryptor->decrypt($this->getValue('server_api'));
    }

    /**
     * Get the script URL
     *
     * @return string
     */
    public function getScriptUrl()
    {
        return $this->getValue(
            $this->isTestMode() ? 'script_url_test' : 'script_url_production'
        );
    }
}
