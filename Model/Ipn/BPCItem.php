<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model\Ipn;

use Bitpay\BPCheckout\Model\Config;
use Magento\Framework\DataObject;

class BPCItem
{
    /** @var string $token */
    private $token = '';

    /** @var DataObject $itemParams */
    private $itemParams;

    /** @var string $invoiceEndpoint */
    private $invoiceEndpoint = '';

    /** @var string $env */
    private $env = '';

    /**
     * @param string $token
     * @param DataObject $itemParams
     * @param string $env
     */
    public function __construct(string $token, DataObject $itemParams, string $env)
    {
        $this->token = $token;
        $this->itemParams = $itemParams;
        $this->env = $env;
        $this->invoiceEndpoint = Config::API_HOST_DEV;
        if ($this->env == 'prod') {
            $this->invoiceEndpoint = Config::API_HOST_PROD;
        }
        return $this->invoiceEndpoint;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Get item params
     *
     * @return DataObject
     */
    public function getItemParams(): DataObject
    {
        return $this->itemParams;
    }

    /**
     * Get invoice endpoint
     *
     * @return string
     */
    public function getInvoiceEndpoint(): string
    {
        return $this->invoiceEndpoint;
    }
}
