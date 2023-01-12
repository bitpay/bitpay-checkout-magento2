<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Unit\Model;

use Bitpay\BPCheckout\Model\Config;
use Bitpay\BPCheckout\Model\Invoice;
use Bitpay\BPCheckout\Model\IpnManagement;
use Bitpay\BPCheckout\Api\IpnManagementInterface;
use Bitpay\BPCheckout\Logger\Logger;
use Bitpay\BPCheckout\Model\Ipn\BPCItem;
use Bitpay\BPCheckout\Model\TransactionRepository;
use Hoa\Iterator\Mock;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\App\Response;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IpnManagementTest extends TestCase
{
    /**
     * @var ResponseFactory|MockObject
     */
    private $responseFactory;

    /**
     * @var UrlInterface|MockObject
     */
    private $url;

    /**
     * @var Session|MockObject
     */
    private $checkoutSession;

    /**
     * @var QuoteFactory|MockObject
     */
    private $quoteFactory;

    /**
     * @var OrderInterface|MockObject
     */
    private $orderInterface;

    /**
     * @var Registry|MockObject
     */
    private $coreRegistry;

    /**
     * @var Logger|MockObject
     */
    private $logger;

    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var Json|MockObject
     */
    private $serializer;

    /**
     * @var TransactionRepository|MockObject
     */
    private $transactionRepository;

    /**
     * @var Invoice|MockObject
     */
    private $invoice;

    /**
     * @var Request|MockObject
     */
    private $request;

    /**
     * @var IpnManagement $ipnManagement
     */
    private $ipnManagement;

    public function setUp(): void
    {
        $this->coreRegistry = $this->getMock(Registry::class);
        $this->responseFactory = $this->getMock(ResponseFactory::class);
        $this->url = $this->getMock(UrlInterface::class);
        $this->quoteFactory = $this->getMock(QuoteFactory::class);
        $this->orderInterface = $this->getMock(\Magento\Sales\Model\Order::class);
        $this->checkoutSession = $this->getMock(Session::class);
        $this->logger = $this->getMock(Logger::class);
        $this->config = $this->getMock(Config::class);
        $this->serializer = $this->getMock(Json::class);
        $this->transactionRepository = $this->getMock(TransactionRepository::class);
        $this->invoice = $this->getMock(Invoice::class);
        $this->request = $this->getMock(Request::class);
        $this->ipnManagement = $this->getClass();
    }

    public function testPostClose(): void
    {
        $orderId = '000000012';
        $quoteId = 21;
        $response = $this->getMock(\Magento\Framework\HTTP\PhpEnvironment\Response::class);
        $order = $this->getMock(Order::class);
        $quote = $this->getMock(Quote::class);
        $this->url->expects($this->once())
            ->method('getUrl')
            ->willReturn('http://localhost/checkout/cart?reload=1');

        $this->responseFactory->expects($this->once())->method('create')->willReturn($response);
        $this->request->expects($this->once())->method('getParam')->willReturn($orderId);
        $order->expects($this->once())->method('getData')->willReturn(['quote_id' => $quoteId]);
        $this->orderInterface->expects($this->once())->method('loadByIncrementId')->willReturn($order);

        $quote->expects($this->once())->method('loadByIdWithoutStore')->willReturnSelf();
        $quote->expects($this->once())->method('getId')->willReturn($quoteId);
        $quote->expects($this->once())->method('setIsActive')->willReturnSelf();
        $quote->expects($this->once())->method('setReservedOrderId')->willReturnSelf();

        $this->quoteFactory->expects($this->once())->method('create')->willReturn($quote);

        $response->expects($this->once())->method('setRedirect')->willReturnSelf();

        $this->ipnManagement->postClose();
    }

    public function testPostCloseQuoteNotFound(): void
    {
        $orderId = '000000012';
        $quoteId = 21;
        $response = $this->getMock(\Magento\Framework\HTTP\PhpEnvironment\Response::class);
        $order = $this->getMock(Order::class);
        $quote = $this->getMock(Quote::class);
        $this->url->expects($this->once())
            ->method('getUrl')
            ->willReturn('http://localhost/checkout/cart?reload=1');

        $this->responseFactory->expects($this->once())->method('create')->willReturn($response);
        $this->request->expects($this->once())->method('getParam')->willReturn($orderId);
        $order->expects($this->once())->method('getData')->willReturn(['quote_id' => $quoteId]);
        $this->orderInterface->expects($this->once())->method('loadByIncrementId')->willReturn($order);
        $quote->expects($this->once())->method('loadByIdWithoutStore')->willReturnSelf();
        $quote->expects($this->once())->method('getId')->willReturn(null);
        $this->quoteFactory->expects($this->once())->method('create')->willReturn($quote);

        $response->expects($this->once())->method('setRedirect')->willReturnSelf();

        $this->ipnManagement->postClose();
    }

    public function testPostCloseExeception(): void
    {
        $orderId = '000000012';
        $quoteId = 21;
        $response = $this->getMock(\Magento\Framework\HTTP\PhpEnvironment\Response::class);
        $order = $this->getMock(Order::class);
        $quote = $this->getMock(Quote::class);
        $this->url->expects($this->once())
            ->method('getUrl')
            ->willReturn('http://localhost/checkout/cart?reload=1');
        $this->responseFactory->expects($this->once())->method('create')->willReturn($response);
        $this->request->expects($this->once())->method('getParam')->willReturn($orderId);
        $order->expects($this->once())->method('getData')->willReturn([]);
        $this->orderInterface->expects($this->once())->method('loadByIncrementId')->willReturn($order);

        $response->expects($this->once())->method('setRedirect')->willReturnSelf();

        $this->ipnManagement->postClose();
    }

    public function testPostIpnComplete(): void
    {
        $this->preparePostIpn('invoice_completed', 'complete');

        $this->ipnManagement->postIpn();
    }

    public function testPostIpnConfirmed(): void
    {
        $this->preparePostIpn('invoice_confirmed', 'confirmed');

        $this->ipnManagement->postIpn();
    }

    public function testPostIpnPaidInFull(): void
    {
        $this->preparePostIpn('invoice_paidInFull', 'paid');

        $this->ipnManagement->postIpn();
    }

    public function testPostIpnFailedToConfirm(): void
    {
        $this->preparePostIpn('invoice_failedToConfirm', 'invalid');

        $this->ipnManagement->postIpn();
    }

    public function testPostIpnDeclined(): void
    {
        $this->preparePostIpn('invoice_declined', 'declined');

        $this->ipnManagement->postIpn();
    }

    public function testPostIpnRefund(): void
    {
        $this->preparePostIpn('invoice_refundComplete', 'refund');

        $this->ipnManagement->postIpn();
    }

    public function testPostIpnException(): void
    {
        $token = bin2hex(random_bytes(20));
        $orderInvoiceId = '12';
        $data = null;
        $serializer = new Json();
        $serializerData = $serializer->serialize($data);

        $this->serializer->expects($this->once())->method('unserialize')->willThrowException(new \InvalidArgumentException());
        $this->request->expects($this->once())->method('getContent')->willReturn($serializerData);

        $this->ipnManagement->postIpn();
    }

    public function testPostIpnTransactionNotFound(): void
    {
        $token = bin2hex(random_bytes(20));
        $eventName = 'ivoice_confirmed';
        $orderInvoiceId = '12';
        $data = [
            'data' => ['orderId' => '00000012', 'id' => $orderInvoiceId],
            'event' => ['name' => $eventName]
        ];
        $serializer = new Json();
        $serializerData = $serializer->serialize($data);
        $this->serializer->expects($this->once())->method('unserialize')->willReturn($data);
        $this->request->expects($this->once())->method('getContent')->willReturn($serializerData);
        $this->transactionRepository->expects($this->once())->method('findBy')->willReturn([]);

        $this->ipnManagement->postIpn();
    }

    public function testPostIpnCompleteInvalid(): void
    {
        $this->preparePostIpn('invoice_completed', 'test');

        $this->ipnManagement->postIpn();
    }

    private function preparePostIpn(string $eventName, string $invoiceStatus): void
    {
        $token = bin2hex(random_bytes(20));
        $orderInvoiceId = '12';
        $data = [
            'data' => ['orderId' => '00000012', 'id' => $orderInvoiceId],
            'event' => ['name' => $eventName]
        ];
        $serializer = new Json();
        $serializerData = $serializer->serialize($data);
        $this->serializer->expects($this->once())->method('unserialize')->willReturn($data);
        $this->request->expects($this->once())->method('getContent')->willReturn($serializerData);
        $this->transactionRepository->expects($this->once())->method('findBy')->willReturn([
            'id' => '1',
            'order_id' => '12',
            'transaction_id' => 'VjvZuvsWT6tzYX65ZXk4xq',
            'transaction_status' => 'new'
        ]);

        $this->config->expects($this->once())->method('getBitpayEnv')->willReturn('test');
        $this->config->expects($this->once())->method('getToken')->willReturn('test');
        $item = new BPCItem($token, new DataObject(['invoiceID' => $orderInvoiceId, 'extension_version' => Config::EXTENSION_VERSION]), 'test');
        $this->invoice->expects($this->once())->method('getBPCCheckInvoiceStatus')->willReturn($invoiceStatus);
        $order = $this->getMock(Order::class);
        $this->orderInterface->expects($this->once())->method('loadByIncrementId')->willReturn($order);
    }

    private function getMock(string $className): MockObject
    {
        return $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
    }

    private function getClass(): IpnManagement
    {
        return new IpnManagement(
            $this->responseFactory,
            $this->url,
            $this->coreRegistry,
            $this->checkoutSession,
            $this->orderInterface,
            $this->quoteFactory,
            $this->logger,
            $this->config,
            $this->serializer,
            $this->transactionRepository,
            $this->invoice,
            $this->request
        );
    }
}
