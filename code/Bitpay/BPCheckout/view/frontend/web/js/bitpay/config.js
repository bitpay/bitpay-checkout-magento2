if (window.location.pathname.indexOf('sales/guest/form') != -1) {

    //autofill form
    if (document.cookie.indexOf('oar_order_id') !== -1) {
        $guest = gC("guest")

        setTimeout(function () {
                jQuery("#oar-order-id").val(gC("oar_order_id"))
                jQuery("#oar-billing-lastname").val(gC("oar_billing_lastname"))
                jQuery("#oar_email").val(gC("oar_email"))
                dC("oar_order_id")
                dC("oar_billing_lastname")
                dC("oar_email")
            },
            2000);
    }
}
if (window.location.hash.indexOf('#payment') != -1) {
    
   /*
    var $checkC = gC("firstname")
    if(typeof $checkC != "undefined"){
        //set the fields
        jQuery('input[name="firstname"]').val(gC("firstname"))
        jQuery('input[name="lastname"]').val(gC("lastname"))
        jQuery('input[name="company"]').val(gC("company"))
        jQuery('input[name="street[0]"]').val(gC("street"))
        jQuery('input[name="city"]').val(gC("city"))
        jQuery('input[name="postcode"]').val(gC("postcode"))
        jQuery('input[name="telephone"]').val(gC("telephone"))

    }else{
        //save the info
       
     
        gcC("firstname") =  jQuery('input[name="firstname"]').val()
        gcC("lastname") =  jQuery('input[name="lastname"]').val()
        gcC("company") =  jQuery('input[name="company"]').val()
        gcC("street") =  jQuery('input[name="street[0]"]').val()
        gcC("city") =  jQuery('input[name="city"]').val()
        gcC("postcode") =  jQuery('input[name="postcode"]').val()
        gcC("telephone") =  jQuery('input[name="telephone"]').val()
       
    }
     */

}
function gC(cname) {
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
    function dC(cname) {
        document.cookie = cname + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/;';
    }

