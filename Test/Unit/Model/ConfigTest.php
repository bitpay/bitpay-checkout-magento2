<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Unit\Model;

use Bitpay\BPCheckout\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var Config $config
     */
    private $config;

    /**
     * @var ScopeConfigInterface|MockObject $scopeConfig
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface|MockObject $storeManagerInterface
     */
    private $storeManagerInterface;

    /**
     * @var EncryptorInterface|MockObject $encryptor
     */
    private $encryptor;

    public function setUp(): void
    {
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerInterface = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->encryptor = $this->getMockBuilder(EncryptorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->config = new Config(
            $this->scopeConfig,
            $this->storeManagerInterface,
            $this->encryptor
        );
    }

    public function testGetBitpayUx(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::BITPAY_UX, ScopeInterface::SCOPE_STORE)
            ->willReturn('modal');
        $this->assertEquals('modal', $this->config->getBitpayUx());
    }

    public function testGetBitpayIpnMapping(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::BITPAY_IPN_MAPPING, ScopeInterface::SCOPE_STORE)
            ->willReturn('pending');
        $this->assertEquals('pending', $this->config->getBitpayIpnMapping());
    }

    public function testGetToken(): void
    {
        $tokenEncryptData = ':3:zduacP+9hbAhK4XgHh/RCZhPTxVS44234324234232hgffd';
        $tokenDecryptData = '{"data":{"0":{"token":"34GB93@jf234222","pairingCode":"12334"}}}';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::BITPAY_MERCHANT_TOKEN_DATA, ScopeInterface::SCOPE_STORE)
            ->willReturn($tokenEncryptData);

        $this->encryptor->expects($this->once())->method('decrypt')->willReturn($tokenDecryptData);
        $this->config->getToken();
    }

    public function testEncryptTokenMerchantDataEmpty(): void
    {
        $tokenEncryptData = ':3:zduacP+9hbAhK4XgHh/RCZhPTxVS44234324234232hgffd';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::BITPAY_MERCHANT_TOKEN_DATA, ScopeInterface::SCOPE_STORE)
            ->willReturn($tokenEncryptData);

        $this->config->getToken();
    }

    public function testTokenDataEmpty(): void
    {
        $tokenEncryptData = ':3:zduacP+9hbAhK4XgHh/RCZhPTxVS44234324234232hgffd';
        $tokenDecryptData = '{"token":"34GB93@jf234222","pairingCode":"12334"}}';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::BITPAY_MERCHANT_TOKEN_DATA, ScopeInterface::SCOPE_STORE)
            ->willReturn($tokenEncryptData);

        $this->encryptor->expects($this->once())->method('decrypt')->willReturn($tokenDecryptData);

        $this->config->getToken();
    }

    public function testDecryptTokenMerchantEmpty(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::BITPAY_MERCHANT_TOKEN_DATA, ScopeInterface::SCOPE_STORE)
            ->willReturn(null);

        $this->config->getToken();
    }

    public function testGetBitpayEnv(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::BITPAY_ENV, ScopeInterface::SCOPE_STORE)
            ->willReturn('test');
        $this->assertEquals('test', $this->config->getBitpayEnv());
    }

    public function testGetBitpayRefundMapping(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::BITPAY_REFUND_MAPPING, ScopeInterface::SCOPE_STORE)
            ->willReturn('closed');
        $this->assertEquals('closed', $this->config->getBitpayRefundMapping());
    }

    public function testGetBitpayCancelMapping(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::BITPAY_CANCEL_MAPPING, ScopeInterface::SCOPE_STORE)
            ->willReturn('cancel');
        $this->assertEquals('cancel', $this->config->getBitpayCancelMapping());
    }

    public function testGetBPCheckoutOrderStatus(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::BPCHECKOUT_ORDER_STATUS, ScopeInterface::SCOPE_STORE)
            ->willReturn('new');
        $this->assertEquals('new', $this->config->getBPCheckoutOrderStatus());
    }

    public function testGetBaseUrl(): void
    {
        $baseUrl = 'http://localhost';
        $store = $this->getMockBuilder(Store::class)->disableOriginalConstructor()->getMock();
        $store->expects($this->once())->method('getBaseUrl')->willReturn($baseUrl);
        $this->storeManagerInterface->expects($this->once())->method('getStore')->willReturn($store);

        $this->assertEquals($baseUrl, $this->config->getBaseUrl());
    }

    public function testGetPrivateKeyPath(): void
    {
        $path = 'var/www/html/test';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::BITPAY_MERCHANT_PRIVATE_KEY_PATH, ScopeInterface::SCOPE_STORE)
            ->willReturn($path);

        $this->assertEquals($path, $this->config->getPrivateKeyPath());
    }

    public function testGetMerchantFacadePassword(): void
    {
        $password = 'tefsfs342423sdfsst';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::BITPAY_MERCHANT_PASSWORD, ScopeInterface::SCOPE_STORE)
            ->willReturn($password);

        $this->assertEquals($password, $this->config->getMerchantFacadePassword());
    }

    public function testGetIsSendOrderEmail(): void
    {
        $isSendOrderEmail = '1';
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::BITPAY_SEND_ORDER_EMAIL, ScopeInterface::SCOPE_STORE)
            ->willReturn($isSendOrderEmail);

        $this->assertEquals($isSendOrderEmail, $this->config->getIsSendOrderEmail());
    }
}
