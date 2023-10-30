<?php

declare(strict_types=1);

namespace Bitpay\BPCheckout\Observer;

use Bitpay\BPCheckout\Model\BitPayRefundOnline;
use Bitpay\BPCheckout\Model\Config;
use Magento\Framework\Event\ObserverInterface;

class BitPayPaymentRefund implements ObserverInterface
{
    protected \Magento\Framework\App\RequestInterface $request;
    protected BitPayRefundOnline $bitPayRefundOnline;

    /**
     * @param BitPayRefundOnline $bitPayRefundOnline
     */
    public function __construct(\Magento\Framework\App\RequestInterface $request, BitPayRefundOnline $bitPayRefundOnline)
    {
        $this->request = $request;
        $this->bitPayRefundOnline = $bitPayRefundOnline;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $creditMemo = $observer->getData('creditmemo');
        if (!$creditMemo) {
            return;
        }

        $order = $creditMemo->getOrder();
        $paymentMethod = $order->getPayment()->getMethod();
        if ($paymentMethod !== Config::BITPAY_PAYMENT_METHOD_NAME) {
            return;
        }

        $data = $this->request->getPost('creditmemo');
        $doOffline = isset($data['do_offline']) && (bool)$data['do_offline'];

        if (!$doOffline) {
            $this->bitPayRefundOnline->execute($creditMemo);
        }
    }
}
