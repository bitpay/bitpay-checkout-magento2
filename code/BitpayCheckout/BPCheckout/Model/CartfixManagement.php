<?php


namespace BitpayCheckout\BPCheckout\Model;

class CartfixManagement implements \BitpayCheckout\BPCheckout\Api\CartfixManagementInterface
{

    /**
     * {@inheritdoc}
     */
    public function postCartfix($param)
    {
        return 'hello api POST return the $param ' . $param;
    }
}
