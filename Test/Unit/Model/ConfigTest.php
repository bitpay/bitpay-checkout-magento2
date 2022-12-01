<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Unit\Model;

use Bitpay\BPCheckout\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
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

    public function setUp(): void
    {
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)->disableOriginalConstructor()->getMock();
        $this->storeManagerInterface = $this->getMockBuilder(StoreManagerInterface::class)->disableOriginalConstructor()->getMock();
        $this->config = new Config(
            $this->scopeConfig,
            $this->storeManagerInterface
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

    public function testGetBitpayProdToken(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::BITPAY_PROD_TOKEN, ScopeInterface::SCOPE_STORE)
            ->willReturn('DFf3234dfsdfvssdt');
        $this->assertEquals('DFf3234dfsdfvssdt', $this->config->getBitpayProdToken());
    }

    public function testGetTokenDev(): void
    {
        $devToken = bin2hex(random_bytes(20));
        $this->scopeConfig
            ->expects($this->any())
            ->method('getValue')
            ->willReturnMap([
                [Config::BITPAY_ENV, ScopeInterface::SCOPE_STORE, null, 'test'],
                [Config::BITPAY_DEV_TOKEN, ScopeInterface::SCOPE_STORE, null, $devToken]
            ]);

        $this->assertEquals($devToken, $this->config->getToken());
    }

    public function testGetTokenProd(): void
    {
        $prodToken = bin2hex(random_bytes(20));
        $this->scopeConfig
            ->expects($this->any())
            ->method('getValue')
            ->willReturnMap([
                [Config::BITPAY_ENV, ScopeInterface::SCOPE_STORE, null, 'prod'],
                [Config::BITPAY_PROD_TOKEN, ScopeInterface::SCOPE_STORE, null, $prodToken]
            ]);

        $this->assertEquals($prodToken, $this->config->getToken());
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

    public function testGetBitpayDevToken(): void
    {
        $devToken = bin2hex(random_bytes(20));
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::BITPAY_DEV_TOKEN, ScopeInterface::SCOPE_STORE)
            ->willReturn($devToken);
        $this->assertEquals($devToken, $this->config->getBitpayDevToken());
    }
}
