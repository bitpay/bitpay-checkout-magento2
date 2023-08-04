<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model;

use Bitpay\BPCheckout\Model\ResourceModel\BitpayRefund;

class BitpayRefundRepository
{
    private BitpayRefund $bitpayRefund;

    public function __construct(BitpayRefund $bitpayRefund)
    {
        $this->bitpayRefund = $bitpayRefund;
    }

    /**
     * Add BitPay Refund data
     *
     * @param string $orderId
     * @param string $refundId
     * @param float $amount
     * @return void
     */
    public function add(string $orderId, string $refundId, float $amount): void
    {
        $this->bitpayRefund->add($orderId, $refundId, $amount);
    }

    /**
     * Get Refund by order id
     *
     * @param string $orderId
     * @return array|null
     */
    public function getByOrderId(string $orderId): ?array
    {
        return $this->bitpayRefund->getByOrderId($orderId);
    }
}
