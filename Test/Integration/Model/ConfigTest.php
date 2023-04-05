<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Integration\Model;

use Bitpay\BPCheckout\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var Config $config
     */
    private $config;

    /**
     * @var ScopeConfigInterface $scopeConfig
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface $storeManagerInterface
     */
    private $storeManagerInterface;

    /** @var EncryptorInterface $encryptor */
    private $encryptor;

    /**
     * @var ObjectManagerInterface $objectManager
     */
    private $objectManager;

    //phpcs:ignore
    private const DECODED_MERCHANT_DATA = '{"data":{"0":{"token":"HK4huiR44343ByCLfxwN95wNJXVv3HUU3ZRcTwZh51FtCXij","pairingCode":"5Vt432zcwh"}}}';
    //phpcs:ignore
    private const ENCODED_MERCHANT_DATA = '0:3:uypNhzezLLyRrkExqXXhiCB595zsfnTrp/1hY5thRVYVMpkzgUYRPpTe802dM6NuHbyrYbIQUl6a6bFuINKhiN5yJNO9mJTnUc0OcCqdOwCgboS9kw+je9icSnE=';

    public function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $this->storeManagerInterface = $this->objectManager->get(StoreManagerInterface::class);
        $this->encryptor = $this->objectManager->get(EncryptorInterface::class);
        $this->config = new Config(
            $this->scopeConfig,
            $this->storeManagerInterface,
            $this->encryptor
        );
    }

    /**
     * @return void
     * @magentoConfigFixture current_store payment/bpcheckout/order_status new
     */
    public function testGetBPCheckoutOrderStatus(): void
    {
        $this->assertEquals('new', $this->config->getBPCheckoutOrderStatus());
    }

    /**
     * @magentoDataFixture Bitpay_BPCheckout::Test/Integration/_files/config.php
     */
    public function testGetToken(): void
    {
        $token = json_decode(self::DECODED_MERCHANT_DATA, true)['data'][0]['token'];
        $this->assertEquals($token, $this->config->getToken());
    }

    public function testGetBaseUrl(): void
    {
        $url = $this->storeManagerInterface->getStore()->getBaseUrl();
        $this->assertEquals($url, $this->config->getBaseUrl());
    }

    /**
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_ux modal
     */
    public function testGetBitpayUx(): void
    {
        $this->assertEquals('modal', $this->config->getBitpayUx());
    }

    /**
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_ipn_mapping pending
     */
    public function testGetBitpayIpnMapping(): void
    {
        $this->assertEquals('pending', $this->config->getBitpayIpnMapping());
    }

    /**
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_endpoint test
     */
    public function testGetBitpayEnv(): void
    {
        $this->assertEquals('test', $this->config->getBitpayEnv());
    }

    /**
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_cancel_mapping cancel
     */
    public function testGetBitpayCancelMapping(): void
    {
        $this->assertEquals('cancel', $this->config->getBitpayCancelMapping());
    }

    /**
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_refund_mapping closed
     */
    public function testGetBitpayRefundMapping(): void
    {
        $this->assertEquals('closed', $this->config->getBitpayRefundMapping());
    }

    /**
     * @magentoConfigFixture current_store bitpay_merchant_facade/authenticate/token_data 0:3:uypNhzezLLyRrkExqXXhiCB595zsfnTrp/1hY5thRVYVMpkzgUYRPpTe802dM6NuHbyrYbIQUl6a6bFuINKhiN5yJNO9mJTnUc0OcCqdOwCgboS9kw+je9icSnE=
     */
    public function testGetMerchantTokenData(): void
    {
        $this->assertEquals(self::ENCODED_MERCHANT_DATA, $this->config->getMerchantTokenData());
    }

    /**
     * @magentoConfigFixture current_store bitpay_merchant_facade/authenticate/private_key_path /app/secure/private.key
     */
    public function testGetPrivateKeyPath(): void
    {
        $this->assertEquals('/app/secure/private.key', $this->config->getPrivateKeyPath());
    }

    /**
     * phpcs:ignore
     * @magentoConfigFixture current_store bitpay_merchant_facade/authenticate/password 0:3:qHccgp+LBPr1uzar1cQjTwQBwnH3A+GB3giLnEZMm+3mSezk
     */
    public function testGetMerchantFacadePassword(): void
    {
        $this->assertEquals(
            '0:3:qHccgp+LBPr1uzar1cQjTwQBwnH3A+GB3giLnEZMm+3mSezk',
            $this->config->getMerchantFacadePassword()
        );
    }

    /**
     * @magentoConfigFixture current_store payment/bpcheckout/send_order_email 0
     */
    public function testGetIsSendOrderEmail(): void
    {
        $this->assertEquals('0', $this->config->getIsSendOrderEmail());
    }

    /**
     * @magentoConfigFixture current_store payment/bpcheckout/active 0
     */
    public function testIsPaymentActive(): void
    {
        $this->assertEquals(false, $this->config->isPaymentActive());
    }
}
