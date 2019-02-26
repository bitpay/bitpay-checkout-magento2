
function showModal(env, response) {
if (env == 'test') {
    bitpay.enableTestMode()
}
response = JSON.parse(response);
console.log('response', response);
    window.addEventListener("message", function (event) {
        payment_status = event.data.status;
        if (payment_status == "paid") {
            jQuery(".maincontent").show()
            return;
        }
    }, false);
   
    //show the order info
    bitpay.onModalWillLeave(function () {
        if (payment_status != "paid") {
            window.location.href = response.cartFix;
        } //endif
       
    });
    //show the modal
        bitpay.showInvoice(response.invoiceID);

}
