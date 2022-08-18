if (window.location.pathname.indexOf('sales/guest/form') != -1) {

    //autofill form
    if (document.cookie.indexOf('oar_order_id') !== -1) {
        $guest = getCookie("guest")

        setTimeout(function () {
                document.getElementById("oar-order-id").value = getCookie("oar_order_id")
                document.getElementById("oar-billing-lastname").value = getCookie("oar_billing_lastname")
                document.getElementById("oar_email").value = getCookie("oar_email")
                deleteCookie("oar_order_id")
                deleteCookie("oar_billing_lastname")
                deleteCookie("oar_email")
            },
            2000);
    }
}
if (window.location.pathname == "/checkout/") {
    //try and load info from cookie
    setTimeout(function () {
        var $checkC = getCookie("buyer_first_name")
        if(typeof $checkC != undefined && $checkC != ""){
            document.querySelector('input[name="firstname"]').value = 'ttsetsett'
            document.querySelector('input[name="lastname"]').value = getCookie("buyer_last_name").replace(/\+/g, ' ')
            document.querySelector('input[name="street[0]"]').value = getCookie("buyer_street").replace(/\+/g, ' ')
            document.querySelector('input[name="city"]').value = getCookie("buyer_city").replace(/\+/g, ' ')
            document.querySelector('input[name="postcode"]').value = getCookie("buyer_postcode").replace(/\+/g, ' ')
            document.querySelector('input[name="telephone"]').value = getCookie("buyer_telephone").replace(/\+/g, ' ')
            document.getElementById('customer-email').value = getCookie("buyer_email").replace(/\+/g, ' ')
        }
 },
 3500);
}


function getParams() {
    var vars = {};
    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
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
function deleteCookie(cname) {
    document.cookie = cname + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/;';
}