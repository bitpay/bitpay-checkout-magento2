<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Observer;

use Bitpay\BPCheckout\Model\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class BPPaymentMethodAvailable implements ObserverInterface
{

    protected $config;

    /**
     * payment_method_is_active event handler.
     *
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function execute(Observer $observer)
    {
        if ($observer->getEvent()->getMethodInstance()->getCode() !== "bpcheckout") {
            return;
        }

        $tokenData = $this->config->getMerchantTokenData();
        if (!$tokenData = $this->config->getMerchantTokenData()) {
            #hide the payment method
            $checkResult = $observer->getEvent()->getResult();
            $checkResult->setData('is_available', false); //this is disabling the payment method at checkout page

            return;
        }
    }
}
