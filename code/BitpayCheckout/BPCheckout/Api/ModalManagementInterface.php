<?php


namespace BitpayCheckout\BPCheckout\Api;

interface ModalManagementInterface
{

    /**
     * POST for modal api
     * @param string $param
     * @return string
     */
    public function postModal();
}
