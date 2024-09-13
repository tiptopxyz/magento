<?php
declare(strict_types=1);

namespace Tiptop\PaymentGateway\Model\System\Config;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Returning the values for the api mode
 *
 * @internal
 */
class Mode implements OptionSourceInterface
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return[
            [
                'value' => '1',
                'label' => __('Test')
            ],
            [
                'value' => '0',
                'label' => __('Production')
            ]
        ];
    }
}
