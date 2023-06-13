<?php

namespace Bitpay\BPCheckout\Observer;

use Bitpay\BPCheckout\Logger\Logger;
use Bitpay\BPCheckout\Model\Config;
use Magento\Framework\Event\ObserverInterface;

class BPEmail implements ObserverInterface
{
    /** @var Logger $logger */
    protected $logger;

    /** @var Config $config */
    protected $config;

    /**
     * @param Logger $logger
     * @param Config $config
     */
    public function __construct(
        Logger $logger,
        Config $config
    ) {
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Handle sending order email
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();
            $payment = $order->getPayment()->getMethodInstance()->getCode();
            $isSendOrderEmail = (bool) $this->config->getIsSendOrderEmail();
            if ($payment == "bpcheckout" && $isSendOrderEmail === false) {
                $this->stopNewOrderEmail($order);
            }
        } catch (\ErrorException $ee) {
            $this->logger->error($ee->getMessage());
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
        } catch (\Error $error) {
            $this->logger->error($error->getMessage());
        }
    }

    /**
     * Stop sending new order email
     *
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    public function stopNewOrderEmail(\Magento\Sales\Model\Order $order)
    {
        $order->setCanSendNewEmailFlag(false);
        $order->setSendEmail(false);
        try {
            $order->save();
        } catch (\ErrorException $ee) {
            $this->logger->error($ee->getMessage());
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
        } catch (\Error $error) {
            $this->logger->error($error->getMessage());
        }
    }
}
