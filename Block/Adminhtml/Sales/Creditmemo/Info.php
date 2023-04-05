<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Block\Adminhtml\Sales\Creditmemo;

use Bitpay\BPCheckout\Model\BitpayRefundRepository;
use Bitpay\BPCheckout\Model\Config;
use Magento\Directory\Model\PriceCurrency;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Shipping\Helper\Data as ShippingHelper;
use Magento\Tax\Helper\Data as TaxHelper;

class Info extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
    /** @var BitpayRefundRepository $bitpayRefundRepository */
    protected $bitpayRefundRepository;

    /** @var PriceCurrency $priceCurrency */
    protected $priceCurrency;

    /** @var Config $config */
    protected $config;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param BitpayRefundRepository $bitpayRefundRepository
     * @param PriceCurrency $priceCurrency
     * @param Config $config
     * @param array $data
     * @param ShippingHelper|null $shippingHelper
     * @param TaxHelper|null $taxHelper
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        BitpayRefundRepository $bitpayRefundRepository,
        PriceCurrency $priceCurrency,
        Config $config,
        array $data = [],
        ?ShippingHelper $shippingHelper = null,
        ?TaxHelper $taxHelper = null
    ) {
        $this->bitpayRefundRepository = $bitpayRefundRepository;
        $this->config = $config;
        $this->priceCurrency = $priceCurrency;
        parent::__construct($context, $registry, $adminHelper, $data, $shippingHelper, $taxHelper);
    }

    /**
     * Get BitPay additional info to display in creditmemo detail page
     *
     * @return array
     */
    public function getBitpayAdditionalInfo(): array
    {
        if (!$this->config->isPaymentActive()) {
            return [];
        }

        $creditmemo = $this->getCreditmemo();
        $order = $creditmemo->getOrder();
        if (!$this->isBitpayPaymentMethod($order)) {
            return [];
        }

        $refundData = $this->bitpayRefundRepository->getByOrderId($order->getId());
        if (!$refundData) {
            return [];
        }

        $amount = $this->priceCurrency->format($refundData['amount']);

        return [
            'refund_id' => $refundData['refund_id'],
            'amount' => $amount
        ];
    }

    /**
     * Get creditmemo
     *
     * @return Creditmemo
     */
    public function getCreditmemo()
    {
        return $this->_coreRegistry->registry('current_creditmemo');
    }

    /**
     * Check whether is BitPay payment method
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    protected function isBitpayPaymentMethod(\Magento\Sales\Model\Order $order): bool
    {
        return $order->getPayment()->getMethod() === Config::BITPAY_PAYMENT_METHOD_NAME;
    }
}
