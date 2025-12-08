require(['Magento_Customer/js/customer-data', 'mage/url'], function (customerData, url) {
    'use strict';

    var queryString = window.location.search,
        urlParams = new URLSearchParams(queryString),
        reload = urlParams.get('reload');

    if (reload) {
        customerData.initStorage();
        customerData.invalidate(['cart']);
        window.history.pushState(null, null, url.build('checkout/cart'));
    }
});
