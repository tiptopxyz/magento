define(
    [
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'jquery',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/storage'
    ],
    function (
        ko,
        Component,
        additionalValidators,
        urlBuilder,
        quote,
        customer,
        $,
        fullScreenLoader,
        storage
    ) {
        'use strict';
        return Component.extend({
            reservedOrderId: null,

            defaults: {
                template: 'Tiptop_PaymentGateway/payment/tiptop'
            },

            initialize: function () {
                this._super();
                this.loadTiptopJs();
            },

            loadTiptopJs: function () {
                var _tiptop_config = {
                    public_api_key: window.checkoutConfig.payment.tiptop.publicApiKey,
                    script: window.checkoutConfig.payment.tiptop.scriptUrl
                };

                !(function (t, e) {
                    var n = t.tiptop || {},
                        c = document.createElement("script");
                    (c.async = !0), (c.src = e.script);
                    var i = document.getElementsByTagName("script")[0];
                    i.parentNode?.insertBefore(c, i);
                })(window, _tiptop_config);
            },

            proceedWithCheckout: function () {
                console.log(this.reservedOrderId);

                const checkoutObject = {
                    orderID: "ORDABCXYZ",
                    currency: "USD",
                    totalCost: 2999,
                    taxCost: 200,
                    shippingCost: 100,
                    items: [
                        {
                            sku: "ABC123",
                            imageUrl: "https://example.com/product_image.jpg",
                            name: "Awesome Product",
                            quantity: 2,
                            amount: 1999
                        }
                    ],
                    addresses: [
                        {
                            firstName: "John",
                            lastName: "Doe",
                            street1: "123 Main Street",
                            street2: "Apt. B",
                            city: "Anytown",
                            state: "CA",
                            zip: "12345",
                            email: "johndoe2@example.com",
                            phoneNumber: "15551234567",
                            addressType: "shipping"
                        }
                    ],
                    discounts: [
                        {
                            name: "Summer Sale Discount",
                            code: "SUMMER2024",
                            discountAmount: 1000
                        }
                    ]
                };

                console.log("Checkout initiated with object:", checkoutObject);
                tiptop.checkout(checkoutObject).open({
                    onFail: function(error) {
                        logEvent('onFail', error);
                    },
                    onSuccess: function(checkout) {
                        logEvent('onSuccess', checkout);
                        self.handleSuccess(checkout);
                    },
                    onOpen: function(token) {
                        logEvent('onOpen', token);
                    },
                    onValidationError: function(error) {
                        logEvent('onValidationError', error);
                    }
                });

                fullScreenLoader.stopLoader();
            },

            /**
             * Sample response:
             * {
             *     "merchantOrderID": "ce1afba4-9b67-44ec-bdfd-55ca51f6d417",
             *     "checkoutToken": "b803eedf-d8f6-4da7-adbc-d6ec3940294d",
             *     "publicAppKey": "41f67647-d05a-4bb4-b2cd-f32636e0b0bc"
             * }
             */
            handleSuccess: function (checkout) {
                // Save the merchantOrderID, checkoutToken, and publicAppKey with the order
                // Redirect to the success page
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
                    additionalValidators.validate()
                ) {
                    fullScreenLoader.startLoader();

                    // Prepare the checkout object
                    var checkoutObject = {
                        amount: this.getTotal(),
                        currency: this.getCurrency(),
                        merchantOrderId: this.getMerchantOrderId(),
                        items: this.getItems(),
                        billingAddress: this.getBillingAddress(),
                        shippingAddress: this.getShippingAddress(),
                        discounts: this.getDiscounts()
                    };

                    if (this.reservedOrderId === null) {
                        var serviceUrl = !customer.isLoggedIn() ?
                            urlBuilder.createUrl('/guest-carts/:cartId/reserve-order-id', {
                                cartId: quote.getQuoteId()
                            }) :
                            urlBuilder.createUrl('/carts/mine/reserve-order-id', {});

                        storage.post(serviceUrl, JSON.stringify({}))
                            .done(function (response) {
                                if (response) {
                                    console.log('Reserved order ID:', response);
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

            getTotal: function () {
                return quote.totals().grand_total * 100;
            },

            getCurrency: function () {
                return quote.totals().quote_currency_code;
            },

            getMerchantOrderId: function () {
                return quote.getQuoteId();
            },

            getItems: function () {
                var items = [];

                quote.getItems().forEach(function (item) {
                    items.push({
                        id: item.item_id,
                        name: item.name,
                        quantity: item.qty,
                        price: item.price * 100
                    });
                });

                return items;
            },

            getBillingAddress: function () {
                var address = quote.billingAddress();

                return {
                    firstName: address.firstname,
                    lastName: address.lastname,
                    addressLine1: address.street[0],
                    addressLine2: address.street[1] || '',
                    city: address.city,
                    state: address.region,
                    postalCode: address.postcode,
                    country: address.countryId,
                    phoneNumber: address.telephone
                };
            },

            getShippingAddress: function () {
                var address = quote.shippingAddress();

                return {
                    firstName: address.firstname,
                    lastName: address.lastname,
                    addressLine1: address.street[0],
                    addressLine2: address.street[1] || '',
                    city: address.city,
                    state: address.region,
                    postalCode: address.postcode,
                    country: address.countryId,
                    phoneNumber: address.telephone
                };
            },

            getDiscounts: function () {
                var discounts = [];

                if (quote.totals().discounts) {
                    quote.totals().discounts.forEach(function (discount) {
                        discounts.push({
                            code: discount.code,
                            amount: discount.amount * 100,
                            description: discount.description,
                        });
                    });
                }

                return discounts;
            },
        });
    }
);