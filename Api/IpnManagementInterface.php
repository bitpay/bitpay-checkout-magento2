<?php
namespace Bitpay\BPCheckout\Api;

interface IpnManagementInterface
{
    /**
     * POST for ipn api
     *
     * @return string
     */
    public function postIpn();

    /**
     * POST for close api
     *
     * @return string
     */
    public function postClose();
}
