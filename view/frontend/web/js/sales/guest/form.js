require(['mage/storage'], function (storage) {
    // autofill form guest form
    setTimeout(function () {
        storage.get('/bitpay-invoice/customer/data').done(function (data) {
            if (!data) {
                return;
            }
            document.getElementById("oar-order-id").value = data.incrementId;
            document.getElementById("oar-billing-lastname").value = data.billingAddress.lastname;
            document.getElementById("oar_email").value = data.email;
        });
    },
    1000);
});
