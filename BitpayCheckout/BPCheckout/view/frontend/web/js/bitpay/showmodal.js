
function showModal(env){
    jQuery("#bitpaybtn").text('Generating BitPay Invoice')
  
    setTimeout(function(){ 
    jQuery.post( "/showmodal/index/modal", function(data ) {
    jQuery("#bitpaybtn").prop("disabled",true)
       var response = JSON.parse(data)
        window.addEventListener("message", function (event) {
            payment_status = event.data.status;
            if (payment_status == "paid") {
                window.location.href =response.redirectURL;
                return;
            } 
        }, false);
            
            //hide the order info
            bitpay.onModalWillEnter(function () {
               $j(".main").hide()
            });
            //show the order info
            bitpay.onModalWillLeave(function () {
                if (payment_status != "paid") {
                  window.location.href = response.cartFix;
                } //endif
            });
            //show the modal
            if(env == 'test'){
            bitpay.enableTestMode()
            }
            setTimeout(function(){ bitpay.showInvoice(response.invoiceID); }, 10);
            
      });
    }, 1000);
}