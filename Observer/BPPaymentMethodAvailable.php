<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Observer;

use Bitpay\BPCheckout\Model\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class BPPaymentMethodAvailable implements ObserverInterface
{

    /** @var Config $config
     *
     */
    protected $config;

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

        $tokenData = $this->config->getMerchantTokenData();
        if (!$tokenData && !$this->config->isPaymentActive()) {
            #hide the payment method
            $checkResult = $observer->getEvent()->getResult();
            $checkResult->setData('is_available', false); //this is disabling the payment method at checkout page

            return;
        }
    }
}
