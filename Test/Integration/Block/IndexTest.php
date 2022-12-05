<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Integration\Block;

use Bitpay\BPCheckout\Block\Index;
use Magento\Framework\View\Element\Template;
use Bitpay\BPCheckout\Model\Config;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;

class IndexTest extends TestCase
{
    /**
     * @var ObjectManagerInterface $objectManager
     */
    private $objectManager;

    /**
     * @var Template\Context $context
     */
    private $context;

    /**
     * @var Config $config
     */
    private $config;

    /**
     * @var Index $index
     */
    private $index;
    
    public function setUp(): void
    {
        $this->objectManager =  Bootstrap::getObjectManager();
        $this->context = $this->objectManager->get(Template\Context::class);
        $this->config = $this->objectManager->get(Config::class);
        $this->index = new Index(
            $this->context,
            $this->config
        );
    }

    /**
     * @return void
     */
    public function testGetBaseSecureUrl(): void
    {
        $scopeConfig = $this->objectManager->get(\Magento\Framework\App\Config::class);
        $baseSecureUrl = $scopeConfig->getValue('web/secure/base_url');
        $this->assertEquals($baseSecureUrl, $this->index->getBaseSecureUrl());
    }

    /**
     * @return void
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_endpoint test
     */
    public function testGetBitpayEnv(): void
    {
        $this->assertEquals('test', $this->index->getBitpayEnv());
    }

    public function testGetModalParam(): void
    {
        $requst = $this->context->getRequest();
        $requst->setParams(['m' => '1']);

        $this->assertEquals(1, $this->index->getModalParam());
    }

    public function testOrderId(): void
    {
        $requst = $this->context->getRequest();
        $requst->setParams(['order_id' => '000000012']);

        $this->assertEquals('000000012', $this->index->getOrderId());
    }
}
