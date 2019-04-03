<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Bitpay\Core\Model\Order\Payment\State;

use Magento\Sales\Model\Order\Payment\State\AuthorizeCommand as BaseAuthorizeCommand;
use Bitpay\Core\Model\Method\Bitcoin;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;

class AuthorizeCommand extends BaseAuthorizeCommand
{

    public function execute(OrderPaymentInterface $payment, $amount, OrderInterface $order)
    {
        /* @var $paymentMethod Order\Payment */
        /* @var $order Order */

        $paymentMethod      = $order->getPayment();
        $paymentMethodCode  = $paymentMethod->getMethodInstance()->getCode();

        if ($paymentMethodCode !== Bitcoin::CODE) {
            $state = Order::STATE_PROCESSING;
            $status = false;
            $formattedAmount = $order->getBaseCurrency()->formatTxt($amount);

            if ($payment->getIsTransactionPending()) {
                $state = Order::STATE_PAYMENT_REVIEW;
                $message = __(
                    'We will authorize %1 after the payment is approved at the payment gateway.',
                    $formattedAmount
                );
            }
            else {
                if ($payment->getIsFraudDetected()) {
                    $state = Order::STATE_PROCESSING;
                    $message = __(
                        'Order is suspended as its authorizing amount %1 is suspected to be fraudulent.',
                        $formattedAmount
                    );
                } else {
                    $message = __('Authorized amount of %1', $formattedAmount);
                }
            }
       }
       else {
            $state = Order::STATE_NEW;
            $status = false;
            $formattedAmount = $order->getBaseCurrency()->formatTxt($amount);
            $message = __('Authorized amount of %1', $formattedAmount);
       }

       if ($payment->getIsFraudDetected()) {
            $status = Order::STATUS_FRAUD;
        }

        $this->setOrderStateAndStatus($order, $status, $state);

        return $message;
    }


}
