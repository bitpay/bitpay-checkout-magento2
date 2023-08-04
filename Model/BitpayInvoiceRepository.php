<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model;

use Bitpay\BPCheckout\Model\ResourceModel\BitpayInvoice;

class BitpayInvoiceRepository
{
    private BitpayInvoice $bitpayInvoice;

    public function __construct(BitpayInvoice $bitpayInvoice)
    {
        $this->bitpayInvoice = $bitpayInvoice;
    }

    /**
     * Add BitPay Invoice data
     *
     * @param string $orderId
     * @param string $invoiceID
     * @param string $expirationTime
     * @param int|null $acceptanceWindow
     * @return void
     */
    public function add(string $orderId, string $invoiceID, string $expirationTime, ?int $acceptanceWindow): void
    {
        $this->bitpayInvoice->add($orderId, $invoiceID, $expirationTime, $acceptanceWindow);
    }

    /**
     * Get Invoice by order id
     *
     * @param string $orderId
     * @return array|null
     */
    public function getByOrderId(string $orderId): ?array
    {
        return $this->bitpayInvoice->getByOrderId($orderId);
    }
}
