/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        rendererList.push({
            type: 'bpcheckout',
            component: 'Bitpay_BPCheckout/js/view/payment/method-renderer/bpcheckout-method'
        });

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
