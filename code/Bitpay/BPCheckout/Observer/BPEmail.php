<?php
namespace Bitpay\BPCheckout\Observer;
use Magento\Framework\Event\ObserverInterface;

class BPEmail implements ObserverInterface
{
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    try{
        $order = $observer->getEvent()->getOrder();
        $this->_current_order = $order;

        $payment = $order->getPayment()->getMethodInstance()->getCode();

        if($payment == "bpcheckout"){
            #$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/bitpay.log');
            #$logger = new \Zend\Log\Logger();
            #$logger->addWriter($writer);
            #$logger->info('Not sending email');
            $this->stopNewOrderEmail($order);
        }
    }
    catch (\ErrorException $ee){

    }
    catch (\Exception $ex)
    {

    }
    catch (\Error $error){

    }

}

public function stopNewOrderEmail(\Magento\Sales\Model\Order $order){
    $order->setCanSendNewEmailFlag(false);
    $order->setSendEmail(false);
    try{
        $order->save();
    }
    catch (\ErrorException $ee){

    }
    catch (\Exception $ex)
    {

    }
    catch (\Error $error){

    }
}
} 