<?php

namespace Bitpay\BPCheckout\Test\Integration\Model\Ipn;

use Bitpay\BPCheckout\Model\Config;
use Bitpay\BPCheckout\Model\Ipn\BPCItem;
use Magento\Framework\DataObject;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class BPCItemTest extends TestCase
{
    /**
     * @var BPCItem $bpcItem
     */
    private $bpcItem;

    private $token;

    public function setUp(): void
    {
        $this->token = bin2hex(random_bytes(10));
        $itemParams = new DataObject($this->getItemsParams());
        $this->bpcItem = new BPCItem(
            $this->token,
            $itemParams,
            'test'
        );
    }

    public function testGetToken(): void
    {
        $this->assertEquals($this->token, $this->bpcItem->getToken());
    }

    public function testGetItemParams(): void
    {
        $this->assertEquals(Config::EXTENSION_VERSION, $this->bpcItem->getItemParams()['extension_version']);
        $this->assertEquals('USD', $this->bpcItem->getItemParams()['currency']);
        $this->assertEquals('00000123231', $this->bpcItem->getItemParams()['orderId']);
    }

    public function testGetInvoiceEndpoint(): void
    {
        $this->assertEquals(Config::API_HOST_DEV, $this->bpcItem->getInvoiceEndpoint());
    }

    private function getItemsParams(): array
    {
        $objectManager = Bootstrap::getObjectManager();
        $scopeConfig = $objectManager->get(\Magento\Framework\App\Config::class);
        $baseSecureUrl = $scopeConfig->getValue('web/secure/base_url');
        return [
            'extension_version' => Config::EXTENSION_VERSION,
            'price' => 23,
            'currency' => 'USD',
            'buyer' => [],
            'orderId' => '00000123231',
            'redirectURL' => $baseSecureUrl . 'bitpay-invoice/?order_id=00000123231',
            'notificationURL' => $baseSecureUrl . 'rest/V1/bitpay-bpcheckout/ipn',
            'closeURL' => $baseSecureUrl . 'rest/V1/bitpay-bpcheckout/close?orderID=00000123231',
            'extendedNotifications' => true,
            'token' => $this->token
        ];
    }
}
