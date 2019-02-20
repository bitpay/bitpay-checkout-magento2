<?php

class Invoice
{

    public function __construct($item)
    {
        $this->item = $item;

    }

    public function checkInvoiceStatus($orderID)
    {

        $post_fields = ($this->item->item_params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://' . $this->item->item_params->invoice_endpoint . '/' . $post_fields->invoiceID);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function createInvoice()
    {
       
       
        $post_fields = json_encode($this->item->item_params);

        $pluginInfo = $this->item->item_params->extension_version;
        $request_headers = array();
        $request_headers[] = 'X-BitPay-Plugin-Info: ' . $pluginInfo;
        $request_headers[] = 'Content-Type: application/json';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://' . $this->item->invoice_endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);


        $this->invoiceData = $result;

        curl_close($ch);

    }

    public function getInvoiceData()
    {
        return $this->invoiceData;
    }

    public function getInvoiceURL()
    {
        $data = json_decode($this->invoiceData);
        return $data->data->url;
    }

    public function updateBuyersEmail($invoice_result, $buyers_email)
    {
        $invoice_result = json_decode($invoice_result);

        $update_fields = new stdClass();
        $update_fields->token = $this->item->item_params->token;
        $update_fields->buyerProvidedEmail = $buyers_email;
        $update_fields->invoiceId = $invoice_result->data->id;
        $update_fields = json_encode($update_fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://' . $this->item->buyers_email_endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $update_fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;

    }

    public function updateBuyerCurrency($invoice_result, $buyer_currency)
    {
        $invoice_result = json_decode($invoice_result);

        $update_fields = new stdClass();
        $update_fields->token = $this->item->item_params->token;
        $update_fields->buyerSelectedTransactionCurrency = $buyer_currency;
        $update_fields->invoiceId = $invoice_result->data->id;
        $update_fields = json_encode($update_fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://' . $this->item->buyer_transaction_endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $update_fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;

    }

}
