<?php
declare(strict_types=1);
namespace Bitpay\BPCheckout\Test\Unit\Model;

use Bitpay\BPCheckout\Model\Invoice;
use Bitpay\BPCheckout\Model\Ipn\BPCItem;
use Magento\Checkout\Model\Session;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Service\InvoiceService;
use Bitpay\BPCheckout\Logger\Logger;
use Bitpay\BPCheckout\Model\Config;
use Magento\Store\Api\StoreManagementInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;

class InvoiceTest extends TestCase
{
    /**
     * @var Invoice $invoice
     */
    private $invoice;

    /**
     * @var InvoiceService|MockObject $invoiceService
     */
    private $invoiceService;

    /**
     * @var Logger|MockObject $logger
     */
    private $logger;

    /**
     * @var Transaction|MockObject $transaction
     */
    private $transaction;

    /**
     * @var Config|MockObject $config
     */
    private $config;

    /**
     * @var Session|MockObject $checkoutSession
     */
    private $checkoutSession;

    /**
     * @var OrderSender|MockObject $orderSender
     */
    private $orderSender;

    /**
     * @var OrderRepository $orderRepository
     */
    private $orderRepository;

    public function setUp(): void
    {
        $this->invoiceService = $this->getMock(InvoiceService::class);
        $this->logger = $this->getMock(Logger::class);
        $this->transaction = $this->getMock(Transaction::class);
        $this->config = $this->getMock(Config::class);
        $this->checkoutSession = $this->getMock(Session::class);
        $this->orderSender = $this->getMock(OrderSender::class);
        $this->orderRepository = $this->getMock(OrderRepository::class);
        $this->invoice = new Invoice(
            $this->invoiceService,
            $this->logger,
            $this->transaction,
            $this->config,
            $this->checkoutSession,
            $this->orderSender,
            $this->orderRepository
        );
    }

    public function testDeclined(): void
    {
        $env = 'test';
        $incrementId = '00000210000';
        $bitpayToken = bin2hex(random_bytes(20));
        $params = new DataObject($this->getParams($incrementId, $bitpayToken));
        $order = $this->getMock(Order::class);
        $historyStatus = $this->getMock(\Magento\Sales\Model\Order\Status\History::class);
        $invoiceStatus = 'expired';
        $item = new BPCItem($bitpayToken, $params, $env);

        $order->expects($this->once())->method('addStatusHistoryComment')->willReturn($historyStatus);
        $this->config->expects($this->once())->method('getBitpayCancelMapping')->willReturn(null);

        $this->invoice->declined($order, $invoiceStatus, $item);
    }

    public function testDeclinedWithCancelMapping(): void
    {
        $env = 'test';
        $incrementId = '00000240000';
        $bitpayToken = bin2hex(random_bytes(10));
        $params = new DataObject($this->getParams($incrementId, $bitpayToken));
        $order = $this->getMock(Order::class);
        $historyStatus = $this->getMock(\Magento\Sales\Model\Order\Status\History::class);
        $invoiceStatus = 'declined';
        $item = new BPCItem($bitpayToken, $params, $env);

        $order->expects($this->once())->method('addStatusHistoryComment')->willReturn($historyStatus);
        $this->config->expects($this->once())->method('getBitpayCancelMapping')->willReturn('cancel');
        $order->expects($this->once())->method('setState')->willReturnSelf();
        $order->expects($this->once())->method('setStatus')->willReturnSelf();

        $this->invoice->declined($order, $invoiceStatus, $item);
    }

    public function testComplete(): void
    {
        $incrementId = '00000240000';
        $env = 'test';
        $bitpayToken = bin2hex(random_bytes(10));
        $params = new DataObject($this->getParams($incrementId, $bitpayToken));
        $order = $this->getMock(Order::class);
        $item = new BPCItem($bitpayToken, $params, $env);

        $order->expects($this->once())->method('setState')->willReturnSelf();
        $order->expects($this->once())->method('setStatus')->willReturnSelf();

        $invoice = $this->getMock(Order\Invoice::class);
        $invoice->expects($this->once())->method('register')->willReturnSelf();
        $invoice->expects($this->once())->method('getOrder')->willReturn($order);
        $this->invoiceService->expects($this->once())->method('prepareInvoice')->willReturn($invoice);

        $this->transaction->expects($this->any())->method('addObject')->willReturnSelf();
        $this->transaction->expects($this->any())->method('addObject')->willReturnSelf();

        $this->invoice->complete($order, $item);
    }

    public function testCompleteInvoiceExeception(): void
    {
        $incrementId = '00000240000';
        $env = 'test';
        $bitpayToken = bin2hex(random_bytes(10));
        $params = new DataObject($this->getParams($incrementId, $bitpayToken));
        $order = $this->getMock(Order::class);
        $item = new BPCItem($bitpayToken, $params, $env);

        $order->expects($this->once())->method('setState')->willReturnSelf();
        $order->expects($this->once())->method('setStatus')->willReturnSelf();

        $this->invoiceService
            ->expects($this->once())
            ->method('prepareInvoice')
            ->willThrowException(new LocalizedException(new Phrase('Something went wrong')));

        $this->invoice->complete($order, $item);
    }

    public function testPaidInFull(): void
    {
        $bitpayToken = bin2hex(random_bytes(10));
        $params = new DataObject($this->getParams('00000240000', $bitpayToken));
        $order = $this->getMock(Order::class);
        $item = new BPCItem($bitpayToken, $params, 'test');

        $this->invoice->paidInFull($order, 'paid', $item);
    }

    public function testPaidInFullInvalidStatus(): void
    {
        $bitpayToken = bin2hex(random_bytes(10));
        $params = new DataObject($this->getParams('00000240000', $bitpayToken));
        $order = $this->getMock(Order::class);
        $item = new BPCItem($bitpayToken, $params, 'test');

        $this->invoice->paidInFull($order, 'test', $item);
    }

    public function testFailedToConfirm(): void
    {
        $bitpayToken = bin2hex(random_bytes(10));
        $params = new DataObject($this->getParams('00000240000', $bitpayToken));
        $order = $this->getMock(Order::class);
        $item = new BPCItem($bitpayToken, $params, 'test');

        $this->invoice->failedToConfirm($order, 'invalid', $item);
    }

    public function testFailedToConfirmIvalidStatus(): void
    {
        $bitpayToken = bin2hex(random_bytes(10));
        $params = new DataObject($this->getParams('00000240000', $bitpayToken));
        $order = $this->getMock(Order::class);
        $item = new BPCItem($bitpayToken, $params, 'test');

        $this->invoice->failedToConfirm($order, 'test', $item);
    }

    public function testConfirmed(): void
    {
        $bitpayToken = bin2hex(random_bytes(10));
        $params = new DataObject($this->getParams('00000240000', $bitpayToken));
        $order = $this->getMock(Order::class);
        $item = new BPCItem($bitpayToken, $params, 'test');

        $this->config->expects($this->once())->method('getBitpayIpnMapping')->willReturn('processing');
        $order->expects($this->once())->method('setState')->willReturnSelf();
        $order->expects($this->once())->method('setStatus')->willReturnSelf();

        $invoice = $this->getMock(Order\Invoice::class);
        $invoice->expects($this->once())->method('register')->willReturnSelf();
        $invoice->expects($this->once())->method('getOrder')->willReturn($order);
        $this->invoiceService->expects($this->once())->method('prepareInvoice')->willReturn($invoice);

        $this->transaction->expects($this->any())->method('addObject')->willReturnSelf();
        $this->transaction->expects($this->any())->method('addObject')->willReturnSelf();

        $this->invoice->confirmed($order, 'confirmed', $item);
    }

    public function testConfirmedInvalidStatus(): void
    {
        $bitpayToken = bin2hex(random_bytes(10));
        $params = new DataObject($this->getParams('00000240000', $bitpayToken));
        $order = $this->getMock(Order::class);
        $item = new BPCItem($bitpayToken, $params, 'test');

        $this->invoice->confirmed($order, 'test', $item);
    }

    public function testConfirmIpnMapping(): void
    {
        $bitpayToken = bin2hex(random_bytes(10));
        $params = new DataObject($this->getParams('00000240000', $bitpayToken));
        $order = $this->getMock(Order::class);
        $item = new BPCItem($bitpayToken, $params, 'test');

        $this->config->expects($this->once())->method('getBitpayIpnMapping')->willReturn('test');
        $order->expects($this->once())->method('setState')->willReturnSelf();
        $order->expects($this->once())->method('setStatus')->willReturnSelf();

        $this->invoice->confirmed($order, 'confirmed', $item);
    }

    public function testRefundComplete(): void
    {
        $bitpayToken = bin2hex(random_bytes(10));
        $params = new DataObject($this->getParams('00000240000', $bitpayToken));
        $order = $this->getMock(Order::class);
        $item = new BPCItem($bitpayToken, $params, 'test');

        $this->config->expects($this->once())->method('getBitpayRefundMapping')->willReturn('closed');
        $order->expects($this->once())->method('setState')->willReturnSelf();
        $order->expects($this->once())->method('setStatus')->willReturnSelf();

        $this->invoice->refundComplete($order, $item);
    }

    public function testBPCCreateInvoice(): void
    {
        $client = $this->getMockBuilder(\BitPaySDK\Client::class)->disableOriginalConstructor()->getMock();
        $params = new DataObject([
            'extension_version' => Config::EXTENSION_VERSION,
            'price' => 15,
            'currency' => 'USD',
            'buyer' => new DataObject(['name' => 'test1', 'email' => 'test1@example.com']),
            'orderId' => '000000121211',
            'redirectURL' => 'http://localhost/bitpay-invoice/?order_id=000000121211',
            'notificationURL' => 'http://localhost/rest/V1/bitpay-bpcheckout/ipn',
            'closeURL' => 'http://localhost/rest/V1/bitpay-bpcheckout/close?orderID=000000121211',
            'extendedNotifications' => true,
            'token' => '34234fdlffdslfksdldl'
        ]);

        $invoice = new \BitPaySDK\Model\Invoice\Invoice($params->getData('price'), $params->getData('currency'));
        $client->expects($this->once())->method('createInvoice')->willReturn($invoice);
        $invoice = $this->invoice->BPCCreateInvoice($client, $params);

        $this->assertEquals(15, $invoice->getPrice());
        $this->assertEquals('USD', $invoice->getCurrency());
    }

    public function testGetBPCheckInvoiceStatus(): void
    {
        $invoiceId = '3213123';
        $invoice = new \BitPaySDK\Model\Invoice\Invoice(13.00, 'USD');
        $invoice->setStatus('pending');
        $client = $this->getMockBuilder(\BitPaySDK\Client::class)->disableOriginalConstructor()->getMock();
        $client->expects($this->once())->method('getInvoice')->willReturn($invoice);

        $status = $this->invoice->getBPCCheckInvoiceStatus($client, $invoiceId);
        $this->assertEquals('pending', $status);
    }

    private function getMock(string $className): MockObject
    {
        return $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
    }

    private function getParams(string $incrementId, string $bitpayToken): array
    {
        return [
            'extension_version' => Config::EXTENSION_VERSION,
            'price' => 12,
            'currency' => 'USD',
            'buyer' => new DataObject(['name' => 'test', 'email' => 'test@example.com']),
            'orderId' => trim($incrementId),
            'redirectURL' => 'http://localhost/bitpay-invoice/?order_id=' . $incrementId,
            'notificationURL' => 'http://localhost/rest/V1/bitpay-bpcheckout/ipn',
            'closeURL' => 'http://localhost/rest/V1/bitpay-bpcheckout/close?orderID=' . $incrementId,
            'extendedNotifications' => true,
            'token' => $bitpayToken,
            'invoiceID' => '12'
        ];
    }
}
