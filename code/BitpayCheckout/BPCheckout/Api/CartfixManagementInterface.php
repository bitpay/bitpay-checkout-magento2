<?php


namespace BitpayCheckout\BPCheckout\Api;

interface CartfixManagementInterface
{

    /**
     * POST for cartfix api
     * @param string $param
     * @return string
     */
    public function postCartfix($param);
}
