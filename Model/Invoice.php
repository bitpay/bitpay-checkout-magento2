<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model;

use Bitpay\BPCheckout\Model\Ipn\BPCItem;
use Magento\Sales\Model\Order;

class Invoice
{
    public function BPCCreateInvoice(BPCItem $item)
    {
        $post_fields = json_encode($item->getItemParams()->getData());
        $pluginInfo = $item->getItemParams()['extension_version'];
        $request_headers = [];
        $request_headers[] = 'X-BitPay-Plugin-Info: ' . $pluginInfo;
        $request_headers[] = 'Content-Type: application/json';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://' . $item->getInvoiceEndpoint().'/invoices');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        
        curl_close($ch);

        return ($result);
    }
}
