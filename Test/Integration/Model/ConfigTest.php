<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Integration\Model;

use Bitpay\BPCheckout\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
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

    /**
     * @var ObjectManagerInterface $objectManager
     */
    private $objectManager;

    public function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $this->storeManagerInterface = $this->objectManager->get(StoreManagerInterface::class);
        $this->config = new Config(
            $this->scopeConfig,
            $this->storeManagerInterface
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
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_devtoken AMLTTY9x9TGXFPcsnLLjem1CaDJL3mRMWupBrm9baacy
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_endpoint test
     */
    public function testGetToken(): void
    {
        $this->assertEquals('AMLTTY9x9TGXFPcsnLLjem1CaDJL3mRMWupBrm9baacy', $this->config->getToken());
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
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_devtoken AMLTTY9x9TGXFPcsnLLjem1CaDJL3mRMWupBrm9baacy
     */
    public function testGetBitpayDevToken(): void
    {
        $this->assertEquals('AMLTTY9x9TGXFPcsnLLjem1CaDJL3mRMWupBrm9baacy', $this->config->getBitpayDevToken());
    }

    /**
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_prodtoken AMLTTY9xTGXFPcsnLLjem1CaDJL3mRMWupBrm9baac
     */
    public function testGetBitpayProdToken(): void
    {
        $this->assertEquals('AMLTTY9xTGXFPcsnLLjem1CaDJL3mRMWupBrm9baac', $this->config->getBitpayProdToken());
    }

    /**
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_refund_mapping closed
     */
    public function testGetBitpayRefundMapping(): void
    {
        $this->assertEquals('closed', $this->config->getBitpayRefundMapping());
    }
}
