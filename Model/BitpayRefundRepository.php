<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model;

use Bitpay\BPCheckout\Model\ResourceModel\BitpayRefund;

class BitpayRefundRepository
{
    private $bitpayRefund;

    public function __construct(BitpayRefund $bitpayRefund)
    {
        $this->bitpayRefund = $bitpayRefund;
    }

    public function add(string $orderId, string $refundId, float $amount): void
    {
        $this->bitpayRefund->add($orderId, $refundId, $amount);
    }

    public function getByOrderId(string $orderId): ?array
    {
        return $this->bitpayRefund->getByOrderId($orderId);
    }
}
