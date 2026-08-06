define([], function () {
    'use strict';

    window.sendIpnRequest = function (url, orderId) {
        jQuery.ajax({
            url: url,
            method: 'POST',
            data: {
                form_key: window.FORM_KEY,
                order_id: orderId
            },
            dataType: 'json',
            complete: function () {
                window.location.reload();
            }
        });
    };
});
