define([
    'mage/mage',
    'bitpay'
], function () {
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
                document.getElementById("bitpay-header").innerHTML = 'Thank you for your purchase.';
                document.getElementById("#success-bitpay-page").style.display = 'block';

                return;
            }
        }, false);

        //show the order info
        bitpay.onModalWillLeave(function () {
            if (!isPaid) {
                document.getElementById("bitpay-header").innerHTML = 'Redirecting to cart...';
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
