<?php

namespace Bitpay\Core\Observer;

use Bitpay\Core\Helper\Data;
use Bitpay\Core\Model\Method\Bitcoin;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

class Bitpayemail implements ObserverInterface {

    /**
     * @var Data
     */
	protected $helper;

    /**
     * Bitpayemail constructor.
     * @param Data $helper
     */
	public function __construct(Data $helper) {
		$this->helper = $helper;
	}

    /**
     * If the customer has not already been notified by email send the notification now that there's a new order.
     *
     * @param Observer $observer
     */
	public function execute(Observer $observer) {
		$orderIds       = $observer->getEvent()->getOrderIds();
		$objectManager  = ObjectManager::getInstance();

		/* @var $order Order */
		$order      = $objectManager->get('Magento\Sales\Model\Order')->load($orderIds[0]);
		$payment    = $order->getPayment();
		$methodCode = $payment->getMethodInstance()->getCode();

		if ($methodCode === Bitcoin::CODE && ! $order->getEmailSent()) {
			$this->helper->logInfo('Order email not sent so I am calling NewOrderEmail now...');

			/* @var $orderSender \Magento\Sales\Model\Order\Email\Sender\OrderSender */
			$orderSender = $objectManager->get('Magento\Sales\Model\Order\Email\Sender\OrderSender');

			if ($orderSender->send($order)) {
				$this->helper->logInfo('Order email sent successfully.');
			}
		}

	}

}

