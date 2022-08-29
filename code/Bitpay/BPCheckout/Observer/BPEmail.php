<?php
namespace Bitpay\BPCheckout\Observer;
use Bitpay\BPCheckout\Logger\Logger;
use Magento\Framework\Event\ObserverInterface;

class BPEmail implements ObserverInterface
{
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
    try{
        $order = $observer->getEvent()->getOrder();
        $this->_current_order = $order;

        $payment = $order->getPayment()->getMethodInstance()->getCode();

        if($payment == "bpcheckout"){
            $this->stopNewOrderEmail($order);
        }
    }
    catch (\ErrorException $ee){
        $this->logger->error($ee->getMessage());
    }
    catch (\Exception $ex)
    {
        $this->logger->error($ex->getMessage());
    }
    catch (\Error $error){
        $this->logger->error($error->getMessage());
    }

}

public function stopNewOrderEmail(\Magento\Sales\Model\Order $order){
    $order->setCanSendNewEmailFlag(false);
    $order->setSendEmail(false);
    try{
        $order->save();
    }
    catch (\ErrorException $ee){
        $this->logger->error($ee->getMessage());
    }
    catch (\Exception $ex)
    {
        $this->logger->error($ex->getMessage());
    }
    catch (\Error $error){
        $this->logger->error($error->getMessage());
    }
}
} 