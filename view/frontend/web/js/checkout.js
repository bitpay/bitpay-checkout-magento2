require([
    'mage/storage',
    'Magento_Checkout/js/checkout-data',
    'uiRegistry'
],
function (storage, checkoutData, registry) {
    'use strict';

    storage.get('/bitpay-invoice/customer/data').done(function (data) {
        var addressFormData = {};

        if (!data) {
            return;
        }
        addressFormData = {
            'firstname': data.billingAddress.firstname,
            'lastname': data.billingAddress.lastname,
            'street': {'0': data.billingAddress.street},
            'city': data.billingAddress.city,
            'postcode': data.billingAddress.postcode,
            'telephone': data.billingAddress.telephone,
            'region_id': data.billingAddress.region_id
        };

        registry.async('checkoutProvider')(function () {
            var shippingAddressData = checkoutData.getShippingAddressFromData();

            if (!shippingAddressData && window.isCustomerLoggedIn === false) {
                checkoutData.setShippingAddressFromData(addressFormData);
            }
            checkoutData.setInputFieldEmailValue(data.email);
        });
    });
});
