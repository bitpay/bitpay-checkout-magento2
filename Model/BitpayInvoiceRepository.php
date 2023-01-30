<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model;

use Bitpay\BPCheckout\Model\ResourceModel\BitpayInvoice;

class BitpayInvoiceRepository
{
    private $bitpayInvoice;

    public function __construct(BitpayInvoice $bitpayInvoice)
    {
        $this->bitpayInvoice = $bitpayInvoice;
    }

    public function add(string $orderId, string $invoiceID, string $expirationTime, ?int $acceptanceWindow): void
    {
        $this->bitpayInvoice->add($orderId, $invoiceID, $expirationTime, $acceptanceWindow);
    }

    public function getByOrderId(string $orderId): ?array
    {
        return $this->bitpayInvoice->getByOrderId($orderId);
    }
}
