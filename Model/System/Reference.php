<?php
declare(strict_types=1);

namespace Tiptop\PaymentGateway\Model\System;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field;

/**
 * @internal
 */
class Reference extends Field
{
    /**
     * Getting back the reference text
     *
     * @param AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function render(AbstractElement $element)
    {
        $docsUrl = 'https://help.tiptop.com/hc/en-us';
        $supportUrl = 'https://help.tiptop.com/hc/en-us/requests/new';

        $translatedString = __('here');

        return
            __('Documentation can be found') .
            " <p style='display:inline'><a href='$docsUrl' target='_blank'>$translatedString</a></p>, " .
            __('You can report an issue or ask a question') .
            " <p style='display:inline'><a href='$supportUrl' target='_blank'>$translatedString</a></p>.";
    }
}
