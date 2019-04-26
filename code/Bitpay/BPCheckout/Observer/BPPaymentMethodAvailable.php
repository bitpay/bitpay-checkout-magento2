<?php
 
namespace Bitpay\BPCheckout\Observer;
 
use Magento\Framework\Event\ObserverInterface;
 
 
class BPPaymentMethodAvailable implements ObserverInterface
{
    /**
     * payment_method_is_active event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig 
    ) {
       
        $this->_scopeConfig = $scopeConfig;
       

    }
    public function getStoreConfig($_env)
    {
        $_val = $this->_scopeConfig->getValue(
            $_env, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $_val;

    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        if($observer->getEvent()->getMethodInstance()->getCode()=="bpcheckout"){
            $env = $this->getStoreConfig('payment/bpcheckout/bitpay_endpoint');
            $bitpay_token = $this->getStoreConfig('payment/bpcheckout/bitpay_devtoken');
            if ($env == 'prod'):
                $bitpay_token = $this->getStoreConfig('payment/bpcheckout/bitpay_prodtoken');
            endif;
            if($bitpay_token == '' || strlen($bitpay_token) != 44):
                #hide the payment method
                $checkResult = $observer->getEvent()->getResult();
                $checkResult->setData('is_available', false); //this is disabling the payment method at checkout page
            endif;


           
        }
  
    }
}
