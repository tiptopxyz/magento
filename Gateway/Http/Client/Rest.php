<?php
declare(strict_types=1);

namespace Tiptop\PaymentGateway\Gateway\Http\Client;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ClientFactory;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\ConverterInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Psr\Log\LoggerInterface;
use Tiptop\PaymentGateway\Gateway\Config\Config;

class Rest implements ClientInterface
{
    /**
     * @var ClientFactory
     */
    protected $clientFactory;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var ConverterInterface|null
     */
    private $converter;

    /**
     * @param ClientFactory $clientFactory
     * @param LoggerInterface $logger
     * @param Config $config
     * @param ConverterInterface|null $converter
     */
    public function __construct(
        ClientFactory      $clientFactory,
        LoggerInterface    $logger,
        Config             $config,
        ConverterInterface $converter = null
    ) {
        $this->clientFactory = $clientFactory;
        $this->converter = $converter;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Log data
     *
     * @param mixed $data
     * @param string $type
     */
    public function logData($data, $type = 'Request')
    {
        if ($this->config->getValue('debug')) {
            if (is_string($data)) {
                $data = json_decode($data, true);
            }

            if (is_array($data)) {
                $this->logger->info(__("$type: ") . json_encode($data, JSON_PRETTY_PRINT));
            } else {
                $this->logger->info(__("$type (raw): ") . $data);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $result = [];
        $client = $this->clientFactory->create();

        $client->setHeaders($transferObject->getHeaders());

        try {
            $this->logData($transferObject->getBody());

            $client->post(
                $transferObject->getUri(),
                is_array($transferObject->getBody())
                    ? json_encode($transferObject->getBody(), JSON_UNESCAPED_SLASHES)
                    : $transferObject->getBody()
            );

            $this->logData($client->getStatus(), 'Response Status');

            if ($client->getStatus() !== 201) {
                $responseCode = $client->getStatus();
                $result = $this->converter
                    ? $this->converter->convert($client->getBody())
                    : [$client->getBody()];
                switch ($responseCode) {
                    case 400:
                        throw new LocalizedException(
                            __('Request failed: Headers or request body were not provided correctly.')
                        );
                    case 401:
                        throw new LocalizedException(
                            __('Request failed: API Key not valid.')
                        );
                    case 403:
                        throw new LocalizedException(
                            __('Request failed: API Key cannot access this resource.')
                        );
                    case 422:
                        throw new LocalizedException(
                            __('Request failed: The order is in a state where the action cannot be requested.')
                        );
                    case 500:
                        throw new LocalizedException(
                            __('Request failed: Internal server error.')
                        );
                    default:
                        throw new LocalizedException(
                            __('Request failed with status code: %1 (%2)', $responseCode, $result['error'] ?? '')
                        );
                }
            }
        } catch (\Exception $e) {
            throw new ClientException(
                __($e->getMessage())
            );
        }

        return $result;
    }
}
