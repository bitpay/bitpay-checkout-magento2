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
            //$(".page-main").show()
            document.querySelector(".page-main").style.visibility = "visible"
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
    document.querySelector(".page-main").style.visibility = "hidden"


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
    setTimeout(function () {
        showModal(getCookie('env'), getCookie('invoicedata'))    
    }, 750);
   
}
