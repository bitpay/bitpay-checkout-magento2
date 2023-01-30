<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Block\Adminhtml\Sales;

use Bitpay\BPCheckout\Model\Config;
use Magento\Shipping\Helper\Data as ShippingHelper;
use Magento\Tax\Helper\Data as TaxHelper;

class Info extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        array $data = [],
        ?ShippingHelper $shippingHelper = null,
        ?TaxHelper $taxHelper = null
    ) {
        parent::__construct($context, $registry, $adminHelper, $data, $shippingHelper, $taxHelper);
    }

    public function getBitpayAdditionalInfo(): array
    {
        $order = $this->getOrder();
        $paymentMethod = $order->getPayment()->getMethod();
        if ($paymentMethod !== Config::BITPAY_PAYMENT_METHOD_NAME) {
            return [];
        }

        $expirationTime = $order->getData('expiration_time');
        $acceptanceWindowTime = $order->getData('acceptance_window');
        if ($acceptanceWindowTime) {
            $acceptanceWindowTime = (int)ceil($order->getData('acceptance_window')/1000);
            $acceptanceWindowTime = date("d/m/Y H:i:s", $acceptanceWindowTime);
        }

        if ($expirationTime) {
            $expirationTime = (int)ceil($order->getData('expiration_time')/1000);
            $expirationTime = date("d/m/Y H:i:s", $expirationTime);
        }

        return [
            ['label' => 'Invoice ID', 'value' => $order->getData('bitpay_invoice_id')],
            ['label' => 'Expiration Time', 'value' => $expirationTime],
            ['label' => 'Acceptance Window', 'value' => $acceptanceWindowTime]
        ];
    }

}
