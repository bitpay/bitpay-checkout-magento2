<?php

class Buttons { 
   function __construct() {   
}

function getButtons(){
   
   
   $button_url = 'https://bitpay.com/resources/paymentButtons';
   $ch = curl_init();
   curl_setopt($ch, CURLOPT_URL, $button_url);
   curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   $result = curl_exec($ch);
   curl_close ($ch);
   return $result;
  
}


}

?>
