<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Config
{
    public const BITPAY_ENV = 'payment/bpcheckout/bitpay_endpoint';
    public const BITPAY_DEV_TOKEN = 'payment/bpcheckout/bitpay_devtoken';
    public const BITPAY_PROD_TOKEN = 'payment/bpcheckout/bitpay_prodtoken';
    public const BITPAY_IPN_MAPPING = 'payment/bpcheckout/bitpay_ipn_mapping';
    public const BITPAY_REFUND_MAPPING = 'payment/bpcheckout/bitpay_refund_mapping';
    public const BITPAY_CANCEL_MAPPING = 'payment/bpcheckout/bitpay_cancel_mapping';
    public const BITPAY_PAYMENT_ACTIVE = 'payment/bpcheckout/active';
    public const BPCHECKOUT_ORDER_STATUS = 'payment/bpcheckout/order_status';
    public const BITPAY_UX = 'payment/bpcheckout/bitpay_ux';
    public const BITPAY_MERCHANT_TOKEN_DATA = 'bitpay_merchant_facade/authenticate/token_data';
    public const BITPAY_MERCHANT_PRIVATE_KEY_PATH = 'bitpay_merchant_facade/authenticate/private_key_path';
    public const BITPAY_MERCHANT_PASSWORD = 'bitpay_merchant_facade/authenticate/password';
    public const BITPAY_SEND_ORDER_EMAIL = 'payment/bpcheckout/send_order_email';
    public const BITPAY_DEV_TOKEN_URL = 'https://test.bitpay.com/tokens';
    public const BITPAY_PROD_TOKEN_URL = 'https://bitpay.com/tokens';
    public const API_HOST_DEV = 'test.bitpay.com';
    public const API_HOST_PROD = 'bitpay.com';
    public const EXTENSION_VERSION = 'Bitpay_BPCheckout_Magento2_9.2.0';
    public const BITPAY_PAYMENT_METHOD_NAME = 'bpcheckout';
    public const BITPAY_PAYMENT_ICON = 'Pay-with-BitPay-CardGroup.svg';
    public const BITPAY_PAYMENT_DIR_IMAGES = 'images';
    public const BITPAY_MODULE_NAME = 'Bitpay_BPCheckout';
    public const BITPAY_API_TOKEN_PATH = 'dashboard/merchant/api-tokens';
    public const BITPAY_MERCHANT_FACADE = 'merchant';

    private ScopeConfigInterface $scopeConfig;
    private StoreManagerInterface $storeManagerInterface;
    private EncryptorInterface $encryptor;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManagerInterface,
        EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->encryptor = $encryptor;
    }

    /**
     * Get BitPay environment
     *
     * @return string|null
     */
    public function getBitpayEnv():? string
    {
        return $this->scopeConfig->getValue(self::BITPAY_ENV, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get BitPay Ipn mapping
     *
     * @return string|null
     */
    public function getBitpayIpnMapping():? string
    {
        return $this->scopeConfig->getValue(self::BITPAY_IPN_MAPPING, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get BitPay Refund mapping
     *
     * @return string|null
     */
    public function getBitpayRefundMapping():? string
    {
        return $this->scopeConfig->getValue(self::BITPAY_REFUND_MAPPING, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get BitPay cancel mapping
     *
     * @return string|null
     */
    public function getBitpayCancelMapping():? string
    {
        return $this->scopeConfig->getValue(self::BITPAY_CANCEL_MAPPING, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get BitPay order status
     *
     * @return string|null
     */
    public function getBPCheckoutOrderStatus():? string
    {
        return $this->scopeConfig->getValue(self::BPCHECKOUT_ORDER_STATUS, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get BitPay Ux
     *
     * @return string|null
     */
    public function getBitpayUx():? string
    {
        return $this->scopeConfig->getValue(self::BITPAY_UX, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get token
     *
     * @return string|null
     */
    public function getToken(): ?string
    {
        $tokenData = $this->getMerchantTokenData();
        if (!$tokenData) {
            return null;
        }

        $tokenData = $this->encryptor->decrypt($tokenData);
        if (!$tokenData) {
            return null;
        }

        $tokenData = json_decode($tokenData, true);
        if (!isset($tokenData['data'])) {
            return null;
        }

        return $tokenData['data'][0]['token'];
    }

    /**
     * Retrieve base URL
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBaseUrl(): string
    {
        return $this->storeManagerInterface->getStore()->getBaseUrl();
    }

    /**
     * Get Merchant Token data
     *
     * @return string|null
     */
    public function getMerchantTokenData(): ?string
    {
        return $this->scopeConfig->getValue(self::BITPAY_MERCHANT_TOKEN_DATA, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get private key path
     *
     * @return string|null
     */
    public function getPrivateKeyPath(): ?string
    {
        return $this->scopeConfig->getValue(self::BITPAY_MERCHANT_PRIVATE_KEY_PATH, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get Merchant Facade password
     *
     * @return string|null
     */
    public function getMerchantFacadePassword(): ?string
    {
        return $this->scopeConfig->getValue(self::BITPAY_MERCHANT_PASSWORD, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get whether send order email
     *
     * @return string|null
     */
    public function getIsSendOrderEmail(): ?string
    {
        return $this->scopeConfig->getValue(self::BITPAY_SEND_ORDER_EMAIL, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Is payment active
     *
     * @return bool
     */
    public function isPaymentActive(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::BITPAY_PAYMENT_ACTIVE, ScopeInterface::SCOPE_STORE);
    }
}
