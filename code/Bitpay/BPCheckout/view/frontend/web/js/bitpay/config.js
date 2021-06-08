
    document.querySelector(".page-main").style.visibility = "hidden"

function showModal(env, response) {
    
    if (env == 'test') {
        bitpay.enableTestMode()
    }

    response = JSON.parse(response);
    var is_paid = false

    window.addEventListener("message", function (event) {
        payment_status = event.data.status;
        if (payment_status == "paid") {
            is_paid = true
            //clear the cookies
            deleteCookie('env')
            deleteCookie('invoicedata')
            deleteCookie('modal')
            //$(".page-main").show()
            document.querySelector(".page-main").style.visibility = "visible"
            return;
        }
    }, false);

    //show the order info
    bitpay.onModalWillLeave(function () {
        if (!is_paid) {
            //clear the cookies
            deleteCookie('env')
            deleteCookie('invoicedata')
            deleteCookie('modal')
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
if (window.location.href.indexOf("checkout/onepage/success/") > -1 && getCookie('modal') == 1) {
  // setTimeout(function () {
        showModal(getCookie('env'), getCookie('invoicedata'))
    //}, 750);

}
//guest checkout 
//autofill the guest info


if (window.location.pathname.indexOf('sales/guest/form') != -1) {
    //autofill form
    if (document.cookie.indexOf('oar_order_id') !== -1) {
        setTimeout(function () {
                jQuery("#oar-order-id").val(getCookie("oar_order_id"))
                jQuery("#oar-billing-lastname").val(getCookie("oar_billing_lastname"))
                jQuery("#oar_email").val(getCookie("oar_email"))
                deleteCookie("oar_order_id")
                deleteCookie("oar_billing_lastname")
                deleteCookie("oar_email")
            },
            1500);
    }
}
