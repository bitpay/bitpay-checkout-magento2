<?php

declare(strict_types=1);

namespace Bitpay\BPCheckout\Block\Adminhtml\Order;

 use Magento\Sales\Block\Adminhtml\Order\View as OrderView;

class View
{
    public function beforeSetLayout(OrderView $subject): void
    {
        $url = $subject->getUrl('bitpay/order_ipn/resend');
        $orderId = $subject->getOrderId();

        $subject->addButton(
            'order-view-resend-ipn',
            [
                'label' => __('Resend IPN'),
                'class' => __('primary'),
                'id' => 'order-view-resend-ipn',
                'onclick' => 'sendIpnRequest(\'' . $url . '\', \'' . $orderId . '\')'
            ],
            -1
        );
    }
}
