require(['Magento_Customer/js/customer-data', 'mage/url'], function (customerData, url) {
    var queryString = window.location.search;
    var urlParams = new URLSearchParams(queryString);
    var reload = urlParams.get('reload')
    if (reload) {
        customerData.initStorage()
        customerData.invalidate(['cart']);
        window.history.pushState(null, null, url.build('checkout/cart'))
    }
});
