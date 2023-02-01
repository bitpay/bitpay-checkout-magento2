<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Config
{
    private $scopeConfig;
    private $storeManagerInterface;

    const BITPAY_ENV = 'payment/bpcheckout/bitpay_endpoint';
    const BITPAY_DEV_TOKEN = 'payment/bpcheckout/bitpay_devtoken';
    const BITPAY_PROD_TOKEN = 'payment/bpcheckout/bitpay_prodtoken';
    const BITPAY_IPN_MAPPING = 'payment/bpcheckout/bitpay_ipn_mapping';
    const BITPAY_REFUND_MAPPING = 'payment/bpcheckout/bitpay_refund_mapping';
    const BITPAY_CANCEL_MAPPING = 'payment/bpcheckout/bitpay_cancel_mapping';
    const BPCHECKOUT_ORDER_STATUS = 'payment/bpcheckout/order_status';
    const BITPAY_UX = 'payment/bpcheckout/bitpay_ux';
    const BITPAY_MERCHANT_TOKEN_DATA = 'bitpay_merchant_facade/authenticate/token_data';
    const BITPAY_MERCHANT_PRIVATE_KEY_PATH = 'bitpay_merchant_facade/authenticate/private_key_path';
    const BITPAY_MERCHANT_PASSWORD = 'bitpay_merchant_facade/authenticate/password';
    const BITPAY_SEND_ORDER_EMAIL = 'payment/bpcheckout/send_order_email';
    const BITPAY_DEV_TOKEN_URL = 'https://test.bitpay.com/tokens';
    const BITPAY_PROD_TOKEN_URL = 'https://bitpay.com/tokens';
    const API_HOST_DEV = 'test.bitpay.com';
    const API_HOST_PROD = 'bitpay.com';
    const EXTENSION_VERSION = 'Bitpay_BPCheckout_Magento2_8.1.0';
    const BITPAY_PAYMENT_METHOD_NAME = 'bpcheckout';
    const BITPAY_PAYMENT_ICON = 'Pay-with-BitPay-CardGroup.svg';
    const BITPAY_PAYMENT_DIR_IMAGES = 'images';
    const BITPAY_MODULE_NAME = 'Bitpay_BPCheckout';
    const BITPAY_API_TOKEN_PATH = 'dashboard/merchant/api-tokens';
    const BITPAY_MERCHANT_FACADE = 'merchant';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManagerInterface
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManagerInterface = $storeManagerInterface;
    }

    public function getBitpayEnv():? string
    {
        return $this->scopeConfig->getValue(self::BITPAY_ENV, ScopeInterface::SCOPE_STORE);
    }

    public function getBitpayDevToken():? string
    {
        return $this->scopeConfig->getValue(self::BITPAY_DEV_TOKEN, ScopeInterface::SCOPE_STORE);
    }

    public function getBitpayProdToken():? string
    {
        return $this->scopeConfig->getValue(self::BITPAY_PROD_TOKEN, ScopeInterface::SCOPE_STORE);
    }

    public function getBitpayIpnMapping():? string
    {
        return $this->scopeConfig->getValue(self::BITPAY_IPN_MAPPING, ScopeInterface::SCOPE_STORE);
    }

    public function getBitpayRefundMapping():? string
    {
        return $this->scopeConfig->getValue(self::BITPAY_REFUND_MAPPING, ScopeInterface::SCOPE_STORE);
    }

    public function getBitpayCancelMapping():? string
    {
        return $this->scopeConfig->getValue(self::BITPAY_CANCEL_MAPPING, ScopeInterface::SCOPE_STORE);
    }

    public function getBPCheckoutOrderStatus():? string
    {
        return $this->scopeConfig->getValue(self::BPCHECKOUT_ORDER_STATUS, ScopeInterface::SCOPE_STORE);
    }

    public function getBitpayUx():? string
    {
        return $this->scopeConfig->getValue(self::BITPAY_UX, ScopeInterface::SCOPE_STORE);
    }

    public function getToken(): string
    {
        $env = $this->getBitpayEnv();
        if ($env === 'prod') {
            return $this->getBitpayProdToken() !== null ? $this->getBitpayProdToken() : '';
        }

        return $this->getBitpayDevToken() !== null ? $this->getBitpayDevToken() : '';
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBaseUrl(): string
    {
        return $this->storeManagerInterface->getStore()->getBaseUrl();
    }

    /**
     * @return string|null
     */
    public function getMerchantTokenData(): ?string
    {
        return $this->scopeConfig->getValue(self::BITPAY_MERCHANT_TOKEN_DATA, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string|null
     */
    public function getPrivateKeyPath(): ?string
    {
        return $this->scopeConfig->getValue(self::BITPAY_MERCHANT_PRIVATE_KEY_PATH, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string|null
     */
    public function getMerchantFacadePassword(): ?string
    {
        return $this->scopeConfig->getValue(self::BITPAY_MERCHANT_PASSWORD, ScopeInterface::SCOPE_STORE);
    }

    public function getIsSendOrderEmail(): ?string
    {
        return $this->scopeConfig->getValue(self::BITPAY_SEND_ORDER_EMAIL, ScopeInterface::SCOPE_STORE);
    }
}
