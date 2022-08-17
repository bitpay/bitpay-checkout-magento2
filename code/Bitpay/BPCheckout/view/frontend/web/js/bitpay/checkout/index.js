define([
    'jquery',
    'mage/mage',
    'bitpay'
], function (jQuery) {
    'use strict';

    return function (config) {
        var invoiceID = getParams()['invoiceID']
        var orderId = getParams()['order_id']
        var env = config.env
        var baseUrl = config.baseUrl
        if(env == "test"){
            bitpay.enableTestMode()
        }

        var isPaid = false
        window.addEventListener("message", function (event) {
            var paymentStatus = event.data.status;
            if (paymentStatus == "paid") {
                isPaid = true
                //clear the cookies
                deleteCookie('env')
                deleteCookie('invoicedata')
                deleteCookie('modal')
                jQuery("#bitpay-header").text('Thank you for your purchase.')
                jQuery("#success-bitpay-page").show()

                return;
            }
        }, false);

        //show the order info
        bitpay.onModalWillLeave(function () {
            if (!isPaid) {
                jQuery("#bitpay-header").text('Redirecting to cart...')
                //clear the cookies and redirect back to the cart
                deleteCookie('env')
                deleteCookie('invoicedata')
                deleteCookie('modal')

                window.location.replace(baseUrl + "rest/V1/bitpay-bpcheckout/close?orderID=" + orderId);

            } //endif
        });
        setTimeout(function() {
            bitpay.showInvoice(invoiceID)
        }, 500);
    };
});
