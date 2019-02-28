function showModal(env, response) {
    if (env == 'test') {
        bitpay.enableTestMode()
    }

    response = JSON.parse(response);
    console.log('response', response);

    window.addEventListener("message", function (event) {
        payment_status = event.data.status;
        if (payment_status == "paid") {
            //clear the cookies
            deleteCookie('env')
            deleteCookie('invoicedata')
            deleteCookie('modal')
            jQuery(".page-main").show()
            return;
        }
    }, false);

    //show the order info
    bitpay.onModalWillLeave(function () {
        if (payment_status != "paid") {
            //clear the cookies
            deleteCookie('env')
            deleteCookie('invoicedata')
            deleteCookie('modal')
            window.location.href = response.cartFix;
        } //endif

    });
    //show the modal
    bitpay.showInvoice(response.invoiceID);


}

function deleteCookie(cname) {
    document.cookie = cname + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/;';

}

function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}


jQuery(document).ready(function () {
    //do some cookie testing


    if (window.location.href.indexOf("checkout/onepage/success/") > -1 && getCookie('modal') == 1) {
        jQuery(".page-main").hide()
        showModal(getCookie('env'), getCookie('invoicedata'))
    }
})
