<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model\Ipn;

use Bitpay\BPCheckout\Model\Config;
use Magento\Framework\DataObject;

class BPCItem
{
    private string $token;
    private DataObject $itemParams;
    private string $invoiceEndpoint;

    public function __construct(
        string $token,
        DataObject $itemParams,
        string $env
    ) {
        $this->token = $token;
        $this->itemParams = $itemParams;
        $this->invoiceEndpoint = Config::API_HOST_DEV;
        if (strtolower($env) === 'prod') {
            $this->invoiceEndpoint = Config::API_HOST_PROD;
        }
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
