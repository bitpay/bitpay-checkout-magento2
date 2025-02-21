<?php

declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Unit\Model\Ipn;

use Bitpay\BPCheckout\Model\BitpayInvoiceRepository;
use Bitpay\BPCheckout\Model\Client as ClientFactory;
use Bitpay\BPCheckout\Model\Ipn\IpnNotificationSender;
use BitPaySDK\Model\Invoice\Buyer;
use PHPUnit\Framework\TestCase;

class IpnNotificationSenderTest extends TestCase
{
    private const EXISTING_ORDER_ID = '11';
    private const NON_EXISTING_ORDER_ID = '121312321';
    private const INVOICE_ID = '123';
    private const INVOICE_TOKEN = 'token';

    public function testSendIpnNotification(): void
    {
        $clientFactory = $this->createMock(ClientFactory::class);
        $client = $this->createMock(\BitPaySDK\Client::class);
        $bitpayInvoiceRepository = $this->createMock(BitpayInvoiceRepository::class);
        $bitpayInvoiceRepository->method('getByOrderId')->with(self::EXISTING_ORDER_ID)
            ->willReturn(['invoice_id' => self::INVOICE_ID]);
        $clientFactory->method('initialize')->willReturn($client);
        $invoice = $this->prepareInvoice();
        $client->expects($this->once())->method('getInvoice')->willReturn($invoice);

        $client->expects(self::once())->method('requestInvoiceNotification')->with(self::INVOICE_ID, self::INVOICE_TOKEN);

        $testedClass = $this->getTestedClass($clientFactory, $bitpayInvoiceRepository);
        $testedClass->execute(self::EXISTING_ORDER_ID);
    }

    public function testIncorrectSendIpnNotification(): void
    {
        $clientFactory = $this->createMock(ClientFactory::class);
        $client = $this->createMock(\BitPaySDK\Client::class);
        $bitpayInvoiceRepository = $this->createMock(BitpayInvoiceRepository::class);
        $bitpayInvoiceRepository->method('getByOrderId')->with(self::NON_EXISTING_ORDER_ID)
            ->willReturn(null);
        $clientFactory->method('initialize')->willReturn($client);

        $client->expects(self::never())->method('requestInvoiceNotification')->with(self::INVOICE_ID, self::INVOICE_TOKEN);
        $this->expectException(\RuntimeException::class);

        $testedClass = $this->getTestedClass($clientFactory, $bitpayInvoiceRepository);
        $testedClass->execute(self::NON_EXISTING_ORDER_ID);
    }

    /**
     * @return \BitPaySDK\Model\Invoice\Invoice
     */
    private function prepareInvoice(): \BitPaySDK\Model\Invoice\Invoice
    {
        $invoice = new \BitPaySDK\Model\Invoice\Invoice(12.00, 'USD');
        $invoice->setAmountPaid(1232132);
        $invoice->setToken(self::INVOICE_TOKEN);
        $buyer = new Buyer();
        $buyer->setName('test');
        $buyer->setEmail('test@example.com');
        $buyer->setAddress1('12 test road');
        $invoice->setBuyer($buyer);

        return $invoice;
    }

    private function getTestedClass(ClientFactory $clientFactory, BitpayInvoiceRepository $bitpayInvoiceRepository)
    {
        return new IpnNotificationSender($clientFactory, $bitpayInvoiceRepository);
    }
}
