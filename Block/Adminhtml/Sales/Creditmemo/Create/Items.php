<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Block\Adminhtml\Sales\Creditmemo\Create;

use Bitpay\BPCheckout\Model\Config;
use Bitpay\BPCheckout\Model\TransactionRepository;

class Items extends \Magento\Sales\Block\Adminhtml\Order\Creditmemo\Create\Items
{
    protected $bitpayTransactionRepository;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Data $salesData,
        TransactionRepository $bitpayTransactionRepository,
        array $data = []
    ) {
        $this->bitpayTransactionRepository = $bitpayTransactionRepository;
        parent::__construct($context, $stockRegistry, $stockConfiguration, $registry, $salesData, $data);
    }

    public function isBitpayPaymentMethod(): bool
    {
        return $this->getOrder()->getPayment()->getMethod() === Config::BITPAY_PAYMENT_METHOD_NAME;
    }

    protected function _prepareLayout()
    {
        if ($this->isBitpayPaymentMethod()) {
            $this->addChild(
                'submit_bitpay_button',
                \Magento\Backend\Block\Widget\Button::class,
                [
                    'label' => __('Refund'),
                    'class' => 'save submit-button refund primary',
                    'onclick' => 'disableElements(\'submit-button\');submitCreditMemo()'
                ]
            );

            $this->addChild(
                'submit_offline',
                \Magento\Backend\Block\Widget\Button::class,
                [
                    'label' => __('Refund Offline'),
                    'class' => 'save submit-button primary',
                    'onclick' => 'disableElements(\'submit-button\');submitCreditMemoOffline()'
                ]
            );
            
            return $this;
        }
        return parent::_prepareLayout();
    }

}
