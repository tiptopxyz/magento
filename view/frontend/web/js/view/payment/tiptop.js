define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'tiptop',
                component: 'Tiptop_PaymentGateway/js/view/payment/method-renderer/tiptop-method'
            }
        );
        return Component.extend({});
    }
);