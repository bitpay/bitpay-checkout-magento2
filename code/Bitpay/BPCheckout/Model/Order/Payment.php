<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Bitpay\Core\Model\Order;

use Bitpay\Core\Model\Method\Bitcoin;
use Magento\Framework\DataObject;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as BasePayment;

/**
 * Order payment information
 *
 * @method \Magento\Sales\Model\ResourceModel\Order\Payment _getResource()
 * @method \Magento\Sales\Model\ResourceModel\Order\Payment getResource()
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Payment extends BasePayment
{
   

       /**
     * Authorize or authorize and capture payment on gateway, if applicable
     * This method is supposed to be called only when order is placed
     *
     * @return $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function place()
    {
        $this->_eventManager->dispatch('sales_order_payment_place_start', ['payment' => $this]);
        $order = $this->getOrder();

        $this->setAmountOrdered($order->getTotalDue());
        $this->setBaseAmountOrdered($order->getBaseTotalDue());
        $this->setShippingAmount($order->getShippingAmount());
        $this->setBaseShippingAmount($order->getBaseShippingAmount());

        $methodInstance = $this->getMethodInstance();
        $methodInstance->setStore($order->getStoreId());

        $orderState = Order::STATE_NEW;
        $orderStatus = $methodInstance->getConfigData('order_status');
        $isCustomerNotified = $order->getCustomerNoteNotify();

        // Do order payment validation on payment method level
        $methodInstance->validate();
        $action     = $methodInstance->getConfigPaymentAction();
        $payment    = $order->getPayment();
        $paymentMethodCode = $payment->getMethodInstance()->getCode();
        
        if ($action) {
            if ($methodInstance->isInitializeNeeded()) {
                $stateObject = new DataObject();
                // For method initialization we have to use original config value for payment action
                $methodInstance->initialize($methodInstance->getConfigData('payment_action'), $stateObject);

                if ($paymentMethodCode !== Bitcoin::CODE) {
                    $orderState = $stateObject->getData('state') ?: $orderState;
                    $orderStatus = $stateObject->getData('status') ?: $orderStatus;
                }

                $isCustomerNotified = $stateObject->hasData('is_notified')
                    ? $stateObject->getData('is_notified')
                    : $isCustomerNotified;
            }
            else {
                 $this->processAction($action, $order);

                 if ($paymentMethodCode !== Bitcoin::CODE){
                    $orderState = Order::STATE_PROCESSING;
                    $orderState = $order->getState() ? $order->getState() : $orderState;
                    $orderStatus = $order->getStatus() ? $order->getStatus() : $orderStatus;
                }
                
            }
        }  else {
            $order->setState($orderState)->setStatus($orderStatus);
        }

        $isCustomerNotified = $isCustomerNotified ?: $order->getCustomerNoteNotify();

        if (!array_key_exists($orderStatus, $order->getConfig()->getStateStatuses($orderState))) {
            $orderStatus = $order->getConfig()->getStateDefaultStatus($orderState);
        }

        $this->updateOrder($order, $orderState, $orderStatus, $isCustomerNotified);

        $this->_eventManager->dispatch('sales_order_payment_place_end', ['payment' => $this]);

        return $this;
    }

    /**
     * @param $transaction
     * @param $message
     */
    public function addTransactionCommentsToOrder($transaction, $message)
    {
        $order = $this->getOrder();
        $payment = $order->getPayment();
        $paymentMethodCode = $payment->getMethodInstance()->getCode();

        $message = $this->_appendTransactionToMessage($transaction, $message);

        if ($paymentMethodCode !== Bitcoin::CODE) {
            $order->addStatusHistoryComment($message);
        }
    }

    //@codeCoverageIgnoreEnd
}
