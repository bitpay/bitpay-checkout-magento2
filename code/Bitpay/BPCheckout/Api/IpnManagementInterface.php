<?php

namespace Bitpay\BPCheckout\Api;

interface IpnManagementInterface
{

    /**
     * POST for ipn api
     * @param string $param
     * @return string
     */
    public function postIpn();

     /**
     * POST for close api
     * @param string $param
     * @return string
     */
    public function postClose();

    
}
