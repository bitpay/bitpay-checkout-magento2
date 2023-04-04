<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Block\Adminhtml\Sales;

use Bitpay\BPCheckout\Model\BitpayInvoiceRepository;
use Bitpay\BPCheckout\Model\Config;
use Magento\Shipping\Helper\Data as ShippingHelper;
use Magento\Tax\Helper\Data as TaxHelper;

class Info extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
    /** @var BitpayInvoiceRepository $bitpayInvoiceRepository */
    protected $bitpayInvoiceRepository;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param BitpayInvoiceRepository $bitpayInvoiceRepository
     * @param array $data
     * @param ShippingHelper|null $shippingHelper
     * @param TaxHelper|null $taxHelper
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        BitpayInvoiceRepository $bitpayInvoiceRepository,
        array $data = [],
        ?ShippingHelper $shippingHelper = null,
        ?TaxHelper $taxHelper = null
    ) {
        $this->bitpayInvoiceRepository = $bitpayInvoiceRepository;
        parent::__construct($context, $registry, $adminHelper, $data, $shippingHelper, $taxHelper);
    }

    /**
     * Get BitPay additional info to display in order detail page
     * in Payment Information section
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getBitpayAdditionalInfo(): array
    {
        $order = $this->getOrder();
        $paymentMethod = $order->getPayment()->getMethod();
        if ($paymentMethod !== Config::BITPAY_PAYMENT_METHOD_NAME) {
            return [];
        }

        $bitpayInvoideData = $this->bitpayInvoiceRepository->getByOrderId($order->getId());
        if (!$bitpayInvoideData) {
            return $this->prepareResult();
        }

        $expirationTime = $bitpayInvoideData['expiration_time'];
        $acceptanceWindowTime = $bitpayInvoideData['acceptance_window'];
        if ($acceptanceWindowTime) {
            $acceptanceWindowTime = (int)ceil($acceptanceWindowTime/1000);
            $acceptanceWindowTime = date("d/m/Y H:i:s", $acceptanceWindowTime);
        }

        if ($expirationTime) {
            $expirationTime = (int)ceil($expirationTime/1000);
            $expirationTime = date("d/m/Y H:i:s", $expirationTime);
        }

        return $this->prepareResult($bitpayInvoideData['invoice_id'], $expirationTime, $acceptanceWindowTime);
    }

    /**
     * Prepare invoice result data
     *
     * @param string $invoiceId
     * @param string $expirationTime
     * @param string|null $acceptanceWindowTime
     * @return array
     */
    protected function prepareResult(
        string $invoiceId = '',
        string $expirationTime = '',
        ?string $acceptanceWindowTime = ''
    ): array {
        return [
            ['label' => 'Invoice ID', 'value' => $invoiceId],
            ['label' => 'Expiration Time', 'value' => $expirationTime],
            ['label' => 'Acceptance Window', 'value' => $acceptanceWindowTime]
        ];
    }
}
