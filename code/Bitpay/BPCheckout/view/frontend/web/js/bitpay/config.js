if (window.location.pathname.indexOf('sales/guest/form') != -1) {

    //autofill form
    if (document.cookie.indexOf('oar_order_id') !== -1) {
        $guest = getCookie("guest")

        setTimeout(function () {
                jQuery("#oar-order-id").val(getCookie("oar_order_id"))
                jQuery("#oar-billing-lastname").val(getCookie("oar_billing_lastname"))
                jQuery("#oar_email").val(getCookie("oar_email"))
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
        if(typeof $checkC != undefined){
        jQuery('input[name="firstname"]').val(getCookie("buyer_first_name").replace(/\+/g, ' '))
        jQuery('input[name="lastname"]').val(getCookie("buyer_last_name").replace(/\+/g, ' '))
        jQuery('input[name="street[0]"]').val(getCookie("buyer_street").replace(/\+/g, ' '))
        jQuery('input[name="city"]').val(getCookie("buyer_city").replace(/\+/g, ' '))
        jQuery('input[name="postcode"]').val(getCookie("buyer_postcode").replace(/\+/g, ' '))
        jQuery('input[name="telephone"]').val(getCookie("buyer_telephone").replace(/\+/g, ' '))
        }
 },
 3000);
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