/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'jquery',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'Bitpay_Core/js/action/set-payment-method',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function (ko,Component, quote,$,placeOrderAction,selectPaymentMethodAction,customer, checkoutData, additionalValidators, url,setPaymentMethodAction,fullScreenLoader) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Bitpay_Core/payment/bitpay',
                timeoutMessage: 'Sorry, but something went wrong. Please contact the seller.'
            },
            getCode: function() {
                return 'bitpay';
            },

            isActive: function() {
                return true;
            },

            // isPlaceOrderActionAllowed: function() {
            //     return true;
            // },

            getRedirectionText: function () {
                
                var iframeHtml;
                jQuery.ajax( {
                    url: url.build('bitpay/iframe/index/'),
                    async: false ,
                    dataType: "json",
                    success: function(a) { 
                        iframeHtml = a.html;
                    } 
                   
                });
                return iframeHtml;   //'You will be transferred to <a href="https://bitpay.com" target="_blank">BitPay</a> to complete your purchase when using this payment method.';
            },

            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }
                var self = this,
                    placeOrder,
                    emailValidationResult = customer.isLoggedIn(),
                    loginFormSelector = 'form[data-role=email-with-possible-login]';
                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                }
                if (emailValidationResult && this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    placeOrder = placeOrderAction(this.getData(), false, this.messageContainer);

                    $.when(placeOrder).fail(function () {
                        self.isPlaceOrderActionAllowed(true);
                    }).done(this.afterPlaceOrder.bind(this));
                    return true;
                }
                return false;
            },

            selectPaymentMethod: function() {
                selectPaymentMethodAction(this.getData());
                checkoutData.setSelectedPaymentMethod(this.item.method);
                return true;
            },

            afterPlaceOrder: function () {
                window.location.replace(url.build('bitpay/invoice/index/'));
            },
            /** Returns send check to info */
            getMailingAddress: function() {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            }
        });
    }
);
