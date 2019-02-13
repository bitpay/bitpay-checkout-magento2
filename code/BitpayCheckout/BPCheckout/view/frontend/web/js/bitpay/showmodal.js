
function showModal(env, response) {
if (env == 'test') {
    bitpay.enableTestMode()
}
response = JSON.parse(response);
console.log('response', response);
    window.addEventListener("message", function (event) {
        payment_status = event.data.status;
        if (payment_status == "paid") {
           // window.location.href = response.redirectURL;
            jQuery(".columns").show()
            return;
        }
    }, false);

    //hide the order info
   
    //show the order info
    bitpay.onModalWillLeave(function () {
        if (payment_status != "paid") {
           // window.location.href = response.cartFix;
        } //endif
       
    });
    //show the modal
        bitpay.showInvoice(response.invoiceID);

}
