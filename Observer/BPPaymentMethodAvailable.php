<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Observer;

use Bitpay\BPCheckout\Model\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class BPPaymentMethodAvailable implements ObserverInterface
{
    protected Config $config;

    /**
     * payment_method_is_active event handler.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Handle BitPay payment method active status
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($observer->getEvent()->getMethodInstance()->getCode() !== "bpcheckout") {
            return;
        }

        // Magento only dispatches this event for active methods, so the token is the only thing left to check.
        // Without a merchant token BitPay cannot create invoices, so it must not be offered at checkout.
        if (!$this->config->getMerchantTokenData()) {
            $observer->getEvent()->getResult()->setData('is_available', false);
        }
    }
}
