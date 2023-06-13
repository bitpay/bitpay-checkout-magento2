<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Unit\Model\Ipn;

use Bitpay\BPCheckout\Model\Config;
use Bitpay\BPCheckout\Model\Ipn\BPCItem;
use Magento\Framework\DataObject;
use PHPUnit\Framework\TestCase;

class BPCItemTest extends TestCase
{
    /**
     * @var BPCItem $bpcItem
     */
    private $bpcItem;

    public const TOKEN = 'Tdew2124wre32313Df';

    public function setUp(): void
    {
        $itemParams = new DataObject($this->getItemParams());
        $this->bpcItem = new BPCItem(
            self::TOKEN,
            $itemParams,
            'test'
        );
    }

    public function testGetToken(): void
    {
        $this->assertEquals('Tdew2124wre32313Df', $this->bpcItem->getToken());
    }

    public function testGetItemParams(): void
    {
        $this->assertEquals('USD', $this->bpcItem->getItemParams()['currency']);
    }

    public function testGetInvoiceEndpoint(): void
    {
        $this->assertEquals(Config::API_HOST_DEV, $this->bpcItem->getInvoiceEndpoint());
    }

    public function testGetInvoiceProdEndpoint(): void
    {
        $itemParams = new DataObject($this->getItemParams());
        $bpcItem = new BPCItem(self::TOKEN, $itemParams, 'prod');

        $this->assertEquals(Config::API_HOST_PROD, $bpcItem->getInvoiceEndpoint());
    }

    private function getItemParams(): array
    {
        return [
            'extension_version' => Config::EXTENSION_VERSION,
            'price' => 23,
            'currency' => 'USD',
            'buyer' => [],
            'orderId' => '00000123231',
            'redirectURL' =>'http://localhost/bitpay-invoice/?order_id=00000123231',
            'notificationURL' => 'http://localhost/rest/V1/bitpay-bpcheckout/ipn',
            'closeURL' => 'http://localhost/rest/V1/bitpay-bpcheckout/close?orderID=00000123231',
            'extendedNotifications' => true,
            'token' => self::TOKEN
        ];
    }
}
