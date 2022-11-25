<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Unit\Block;

use Bitpay\BPCheckout\Block\Index;
use Magento\Framework\View\Element\Template\Context;
use Bitpay\BPCheckout\Model\Config;
use Magento\Framework\App\Config as ScopeConfig;
use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    /**
     * @var Index $index
     */
    private $index;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $config;

    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    private $context;

    /**
     * @var ScopeConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfig;

    /**
     * @var Http|\PHPUnit\Framework\MockObject\MockObject $request
     */
    private $request;

    const SECURE_URL = 'https://localhost';
    const ENV = 'test';
    const ORDER_ID = '0000001212';

    public function setUp(): void
    {
        $this->prepareContext();
        $this->config = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $this->index = new Index(
            $this->context,
            $this->config
        );
    }

    public function testGetBaseSecureUrlTest(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('web/secure/base_url')->willReturn(self::SECURE_URL);

        $this->assertEquals(self::SECURE_URL, $this->index->getBaseSecureUrl());
    }

    public function testGetBitpayEnv(): void
    {
        $this->config->expects($this->once())->method('getBitpayEnv')->willReturn(self::ENV);

        $this->assertEquals(self::ENV, $this->index->getBitpayEnv());
    }

    public function testGetModalParam(): void
    {
        $this->request->expects($this->once())
            ->method('getParam')
            ->with('m')
            ->willReturn(1);

        $this->assertEquals(1, $this->index->getModalParam());
    }

    public function testGetOrderId(): void
    {
        $this->request->expects($this->once())->method('getParam')
            ->with('order_id')
            ->willReturn(self::ORDER_ID);

        $this->assertEquals(self::ORDER_ID, $this->index->getOrderId());
    }

    private function prepareContext(): void
    {
        $this->request = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->disableOriginalConstructor()->getMock();
        $this->scopeConfig = $this->getMockBuilder(ScopeConfig::class)->disableOriginalConstructor()->getMock();
        $this->context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->context->expects($this->once())->method('getScopeConfig')->willReturn($this->scopeConfig);
        $this->context->expects($this->once())->method('getRequest')->willReturn($this->request);
    }
}
