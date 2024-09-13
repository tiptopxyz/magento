define(
    [
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/url-builder',
        'mage/url',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'jquery',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/storage',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/action/redirect-on-success'
    ],
    function (
        ko,
        Component,
        additionalValidators,
        urlBuilder,
        url,
        quote,
        customer,
        $,
        fullScreenLoader,
        storage,
        setPaymentInformationAction,
        redirectOnSuccessAction
    ) {
        'use strict';
        return Component.extend({
            reservedOrderId: null,
            token: ko.observable(null),

            defaults: {
                template: 'Tiptop_PaymentGateway/payment/tiptop'
            },

            initialize: function () {
                this._super();
            },

            logEvent: function (event, data) {
                console.log(`${event}:`, data);
            },

            initTiptopCheckout: function (checkoutObject) {
                var self = this;

                tiptop.checkout(checkoutObject).open({
                    onFail: function (error) {
                        self.logEvent('onFail', error);
                        self.isPlaceOrderActionAllowed(true);
                    },
                    onSuccess: function (checkout) {
                        self.logEvent('onSuccess', checkout);
                        self.token(checkout);
                        self.handleSuccess();
                    },
                    onCancel: function () {
                        self.isPlaceOrderActionAllowed(true);
                    },
                    onOpen: function (token) {
                        self.logEvent('onOpen', token);
                        fullScreenLoader.stopLoader();
                    },
                    onValidationError: function (error) {
                        self.logEvent('onValidationError', error);
                        self.isPlaceOrderActionAllowed(true);
                    }
                });
            },

            handleFailRequest: function (response, message = '') {
                console.error(message, response);
                fullScreenLoader.stopLoader();
                this.isPlaceOrderActionAllowed(true);
            },

            proceedWithCheckout: function () {
                var self = this;

                const checkoutObject = {
                    orderId: this.getOrderId(),
                    currency: this.getCurrency(),
                    totalCost: this.getTotalCost(),
                    taxCost: this.getTaxCost(),
                    shippingCost: this.getShippingCost(),
                    items: this.getItems(),
                    addresses: this.getAddresses(),
                    discounts: this.getDiscounts()
                };

                this.logEvent("Checkout initiated with object:", checkoutObject);

                storage.post(
                    url.build('tiptop/gateway/logger'),
                    JSON.stringify(checkoutObject),
                    false
                ).done(function (response) {
                    self.initTiptopCheckout(checkoutObject);
                }).fail(function (response) {
                    self.handleFailRequest(response, 'Failed to log checkout object.');
                });
            },

            /**
             * Handle the success response
             */
            handleSuccess: function () {
                var self = this;

                self.getPlaceOrderDeferredObject()
                    .done(
                        function () {
                            self.afterPlaceOrder();

                            if (self.redirectAfterPlaceOrder) {
                                redirectOnSuccessAction.execute();
                            }
                        }
                    ).always(
                    function () {
                        self.isPlaceOrderActionAllowed(true);
                    }
                );
            },

            afterPlaceOrder: function () {
                // Send the data to backend for processing
                var serviceUrl = url.build('tiptop/gateway/success');

                storage.post(
                    serviceUrl,
                    JSON.stringify(this.token()),
                    false
                ).done(function (response) {
                    if (response.success) {
                        if (self.redirectAfterPlaceOrder) {
                            redirectOnSuccessAction.execute();
                        }
                    } else {
                        self.handleFailRequest(response, 'Failed to create order and invoice');
                    }
                }).fail(function (response) {
                    self.handleFailRequest(response, 'Failed to communicate with the server');
                });
            },

            /**
             * Place order.
             */
            placeOrder: function (data, event) {
                var self = this;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() &&
                    additionalValidators.validate() &&
                    this.isPlaceOrderActionAllowed() === true
                ) {
                    this.isPlaceOrderActionAllowed(false);
                    fullScreenLoader.startLoader();

                    if (this.reservedOrderId === null) {
                        var serviceUrl = !customer.isLoggedIn() ?
                            urlBuilder.createUrl('/guest-carts/:cartId/reserve-order-id', {
                                cartId: quote.getQuoteId()
                            }) :
                            urlBuilder.createUrl('/carts/mine/reserve-order-id', {});

                        storage.post(serviceUrl, JSON.stringify({}))
                            .done(function (response) {
                                if (response) {
                                    self.reservedOrderId = response;
                                    this.proceedWithCheckout();
                                }
                            }.bind(this))
                            .fail(function (response) {
                                console.error('Failed to reserve order ID:', response);
                                fullScreenLoader.stopLoader();
                            });
                    } else {
                        this.proceedWithCheckout();
                    }
                }

                return false;
            },

            getOrderId: function () {
                return this.reservedOrderId;
            },

            getTotalCost: function () {
                return quote.totals().grand_total * 100;
            },

            getTaxCost: function () {
                return quote.totals().tax_amount * 100;
            },

            getShippingCost: function () {
                return quote.totals().shipping_amount * 100;
            },

            getCurrency: function () {
                return quote.totals().quote_currency_code;
            },

            getItems: function () {
                var items = [];
                quote.getItems().forEach(function (item) {
                    items.push({
                        sku: item.sku,
                        imageUrl: item.thumbnail,
                        name: item.name,
                        quantity: item.qty,
                        amount: item.price * 100
                    });
                });

                return items;
            },

            getAddresses: function () {
                var addresses = [];
                var customerData = customer.customerData;

                // Billing address
                var billingAddress = quote.billingAddress();
                if (billingAddress) {
                    addresses.push({
                        firstName: billingAddress.firstname,
                        lastName: billingAddress.lastname,
                        street1: billingAddress.street[0],
                        street2: billingAddress.street[1] || '',
                        city: billingAddress.city,
                        state: billingAddress.regionCode,
                        zip: billingAddress.postcode,
                        email: customerData.email,
                        phoneNumber: billingAddress.telephone,
                        addressType: 'billing'
                    });
                }

                // Shipping address
                var shippingAddress = quote.shippingAddress();
                if (shippingAddress) {
                    addresses.push({
                        firstName: shippingAddress.firstname,
                        lastName: shippingAddress.lastname,
                        street1: shippingAddress.street[0],
                        street2: shippingAddress.street[1] || '',
                        city: shippingAddress.city,
                        state: shippingAddress.regionCode,
                        zip: shippingAddress.postcode,
                        email: customerData.email,
                        phoneNumber: shippingAddress.telephone,
                        addressType: 'shipping'
                    });
                }

                return addresses;
            },

            getDiscounts: function () {
                var discounts = [];
                var totals = quote.totals();

                if (totals && totals.total_segments) {
                    totals.total_segments
                        .filter(segment => segment.code === 'discount')
                        .forEach(segment => {
                            discounts.push({
                                name: segment.title,
                                code: totals.coupon_code,
                                discountAmount: Math.abs(segment.value) * 100
                            });
                        });
                }

                return discounts;
            },
        });
    }
);