<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model\Ipn;

use Bitpay\BPCheckout\Model\Config;
use Magento\Framework\DataObject;

class BPCItem
{
    private $token = '';
    private $itemParams;
    private $invoiceEndpoint = '';
    private $env = '';

    public function __construct(string $token, DataObject $itemParams, string $env)
    {
        $this->token = $token;
        $this->itemParams = $itemParams;
        $this->env = $env;
        $this->invoiceEndpoint = Config::API_HOST_DEV;
        if($this->env == 'prod') {
            $this->invoiceEndpoint = Config::API_HOST_PROD;
        }
        return $this->invoiceEndpoint;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return DataObject
     */
    public function getItemParams(): DataObject
    {
        return $this->itemParams;
    }

    /**
     * @return string
     */
    public function getInvoiceEndpoint(): string
    {
        return $this->invoiceEndpoint;
    }
}
