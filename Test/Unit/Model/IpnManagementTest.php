<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Unit\Model;

use Bitpay\BPCheckout\Model\Client;
use Bitpay\BPCheckout\Model\Config;
use Bitpay\BPCheckout\Model\Invoice;
use Bitpay\BPCheckout\Model\IpnManagement;
use Bitpay\BPCheckout\Model\Ipn\WebhookVerifier;
use Bitpay\BPCheckout\Logger\Logger;
use Bitpay\BPCheckout\Model\BitpayInvoiceRepository;
use Bitpay\BPCheckout\Helper\ReturnHash;
use Bitpay\BPCheckout\Model\TransactionRepository;
use BitPaySDK\Model\Invoice\Buyer;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
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
     * @var OrderFactory|MockObject
     */
    private $orderFactory;

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

    /**
     * @var Client|MockObject
     */
    private $client;

    /**
     * @var \Magento\Framework\Webapi\Rest\Response|MockObject
     */
    private $response;

    /**
     * @var BitpayInvoiceRepository|MockObject $bitpayInvoiceRepository
     */
    private $bitpayInvoiceRepository;

    /**
     * @var EncryptorInterface|MockObject $encryptor
     */
    private $encryptor;

    /**
     * @var WebhookVerifier|MockObject $webhookVerifier
     */
    protected $webhookVerifier;
    
    /**
     * @var ReturnHash|MockObject
     */
    private $returnHashHelper;

    public function setUp(): void
    {
        $this->coreRegistry = $this->getMock(Registry::class);
        $this->responseFactory = $this->getMock(ResponseFactory::class);
        $this->url = $this->getMock(UrlInterface::class);
        $this->quoteFactory = $this->getMock(QuoteFactory::class);
        $this->orderFactory = $this->getMock(\Magento\Sales\Model\OrderFactory::class);
        $this->checkoutSession = $this->getMock(Session::class);
        $this->logger = $this->getMock(Logger::class);
        $this->config = $this->getMock(Config::class);
        $this->serializer = $this->getMock(Json::class);
        $this->transactionRepository = $this->getMock(TransactionRepository::class);
        $this->invoice = $this->getMock(Invoice::class);
        $this->request = $this->getMock(Request::class);
        $this->client = $this->getMock(Client::class);
        $this->response = $this->getMock(\Magento\Framework\Webapi\Rest\Response::class);
        $this->bitpayInvoiceRepository = $this->getMock(BitpayInvoiceRepository::class);
        $this->encryptor = $this->getMock(EncryptorInterface::class);
        $this->webhookVerifier = $this->getMock(WebhookVerifier::class);
        $this->returnHashHelper = $this->getMock(ReturnHash::class);
        $this->ipnManagement = $this->getClass();
    }

    public function testPostCloseDeletesUnpaidOrder(): void
    {
        $order = $this->prepareCloseRequest();
        $this->prepareUnpaidInvoice('new');
        $this->expectQuoteRestored($order);
        $this->config->method('getBitpayInvoiceCloseHandling')->willReturn('delete_order');

        $order->expects($this->once())->method('delete')->willReturnSelf();

        $this->ipnManagement->postClose();
    }

    public function testPostCloseDeletesOrderWithExpiredInvoice(): void
    {
        $order = $this->prepareCloseRequest();
        $this->prepareUnpaidInvoice('expired');
        $this->expectQuoteRestored($order);
        $this->config->method('getBitpayInvoiceCloseHandling')->willReturn('delete_order');

        $order->expects($this->once())->method('delete')->willReturnSelf();

        $this->ipnManagement->postClose();
    }

    public function testPostCloseKeepOrder(): void
    {
        $order = $this->prepareCloseRequest();
        $this->config->method('getBitpayCheckoutSuccess')->willReturn('standard');
        $this->config->method('getBitpayInvoiceCloseHandling')->willReturn('keep_order');
        $this->checkoutSession
            ->method('__call')
            ->willReturnCallback(fn($operation) => match ([$operation]) {
                ['setLastSuccessQuoteId'] => $this->checkoutSession,
                ['setLastQuoteId'] => $this->checkoutSession,
                ['setLastOrderId'] => $this->checkoutSession
            });

        $order->expects($this->never())->method('delete');

        $this->ipnManagement->postClose();
    }

    public function testPostCloseWithoutReturnIdDoesNothing(): void
    {
        $order = $this->prepareCloseRequest('');
        $this->returnHashHelper->expects($this->never())->method('isValid');
        $this->quoteFactory->expects($this->never())->method('create');

        $order->expects($this->never())->method('delete');

        $this->ipnManagement->postClose();
    }

    public function testPostCloseWithInvalidReturnIdDoesNothing(): void
    {
        $order = $this->prepareCloseRequest('wrong-hash', false);
        $this->quoteFactory->expects($this->never())->method('create');

        $order->expects($this->never())->method('delete');

        $this->ipnManagement->postClose();
    }

    public function testPostCloseForUnknownOrderDoesNothing(): void
    {
        $order = $this->prepareCloseRequest('some-hash', true, 'bpcheckout', null);
        $this->returnHashHelper->expects($this->never())->method('isValid');
        $this->quoteFactory->expects($this->never())->method('create');

        $order->expects($this->never())->method('delete');

        $this->ipnManagement->postClose();
    }

    public function testPostCloseForOtherPaymentMethodDoesNothing(): void
    {
        $order = $this->prepareCloseRequest('valid-hash', true, 'checkmo');
        $this->quoteFactory->expects($this->never())->method('create');

        $order->expects($this->never())->method('delete');

        $this->ipnManagement->postClose();
    }

    public function testPostCloseForInvoicedOrderDoesNothing(): void
    {
        $order = $this->prepareCloseRequest();
        $this->config->method('getBitpayInvoiceCloseHandling')->willReturn('delete_order');
        $order->method('getState')->willReturn('new');
        $order->method('canInvoice')->willReturn(false);
        $this->quoteFactory->expects($this->never())->method('create');

        $order->expects($this->never())->method('delete');

        $this->ipnManagement->postClose();
    }

    public function testPostCloseWhenIpnAlreadyMovedTheTransactionDoesNothing(): void
    {
        $order = $this->prepareCloseRequest();
        $this->config->method('getBitpayInvoiceCloseHandling')->willReturn('delete_order');
        $order->method('getState')->willReturn('new');
        $order->method('canInvoice')->willReturn(true);
        $this->transactionRepository->method('findByOrderIdAndTransactionStatus')->willReturn(null);
        $this->client->expects($this->never())->method('initialize');
        $this->quoteFactory->expects($this->never())->method('create');

        $order->expects($this->never())->method('delete');

        $this->ipnManagement->postClose();
    }

    public function testPostCloseWhenInvoiceIsPaidInBitPayDoesNothing(): void
    {
        $order = $this->prepareCloseRequest();
        $this->config->method('getBitpayInvoiceCloseHandling')->willReturn('delete_order');
        $order->method('getState')->willReturn('new');
        $order->method('canInvoice')->willReturn(true);
        $this->prepareInvoiceStatus('paid');
        $this->quoteFactory->expects($this->never())->method('create');

        $order->expects($this->never())->method('delete');

        $this->ipnManagement->postClose();
    }

    public function testPostCloseQuoteNotFound(): void
    {
        $order = $this->prepareCloseRequest();
        $this->prepareUnpaidInvoice('new');
        $this->config->method('getBitpayInvoiceCloseHandling')->willReturn('delete_order');
        $order->method('getState')->willReturn('new');
        $order->method('canInvoice')->willReturn(true);
        $order->method('getData')->willReturn(['quote_id' => 21]);
        $quote = $this->getMock(Quote::class);
        $quote->expects($this->once())->method('loadByIdWithoutStore')->willReturnSelf();
        $quote->expects($this->once())->method('getId')->willReturn(null);
        $this->quoteFactory->expects($this->once())->method('create')->willReturn($quote);

        $order->expects($this->never())->method('delete');

        $this->ipnManagement->postClose();
    }

    public function testPostCloseExeception(): void
    {
        $order = $this->prepareCloseRequest();
        $this->config->method('getBitpayInvoiceCloseHandling')->willReturn('delete_order');
        $order->method('getState')->willReturn('new');
        $order->method('canInvoice')->willReturn(true);
        $this->transactionRepository->method('findByOrderIdAndTransactionStatus')
            ->willReturn([['transaction_id' => 'inv1']]);
        $this->client->method('initialize')->willThrowException(new \Exception('BitPay unavailable'));
        $this->logger->expects($this->once())->method('error');

        $order->expects($this->never())->method('delete');

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
        $data = null;
        $serializer = new Json();
        $serializerData = $serializer->serialize($data);

        $this->serializer->expects($this->once())->method('unserialize')
            ->willThrowException(new \InvalidArgumentException());
        $this->request->expects($this->once())->method('getContent')->willReturn($serializerData);

        $this->ipnManagement->postIpn();
    }

    public function testPostIpnTransactionNotFound(): void
    {
        $orderInvoiceId = '12';
        $eventName = 'ivoice_confirmed';
        $serializer = new Json();
        $data = $this->prepareData($orderInvoiceId, $eventName);
        $client = $this->getMockBuilder(\BitPaySDK\Client::class)->disableOriginalConstructor()->getMock();

        $this->serializer->expects($this->once())->method('unserialize')->willReturn($data);
        $serializerData = $serializer->serialize($data);
        $this->request->expects($this->once())->method('getContent')->willReturn($serializerData);

        $invoice = $this->prepareInvoice();
        $client->expects($this->once())->method('getInvoice')->willReturn($invoice);
        $this->client->expects($this->once())->method('initialize')->willReturn($client);
        $this->transactionRepository->expects($this->once())->method('findBy')->willReturn([]);

        $this->ipnManagement->postIpn();
    }

    public function testPostIpnValidatorError(): void
    {
        $eventName = 'ivoice_confirmed';
        $orderInvoiceId = '12';
        $data = [
            'data' => [
                'orderId' => '00000012',
                'id' => $orderInvoiceId,
                'buyerFields' => [
                    'buyerName' => 'test',
                    'buyerEmail' => 'test1@example.com',
                    'buyerAddress1' => '12 test road'
                ],
                'amountPaid' => 1232132
            ],
            'event' => ['name' => $eventName]
        ];
        $serializer = new Json();
        $serializerData = $serializer->serialize($data);
        $this->serializer->expects($this->once())->method('unserialize')->willReturn($data);
        $this->request->expects($this->once())->method('getContent')->willReturn($serializerData);

        $invoice = $this->prepareInvoice();
        $client = $this->getMockBuilder(\BitPaySDK\Client::class)->disableOriginalConstructor()->getMock();
        $client->expects($this->once())->method('getInvoice')->willReturn($invoice);
        $this->client->expects($this->once())->method('initialize')->willReturn($client);
        $this->transactionRepository->expects($this->once())->method('findBy')->willReturn([]);

        $this->response->expects($this->once())->method('addMessage')->with(
            "Email from IPN data ('{$data['data']['buyerFields']['buyerEmail']}') does not match with " .
            "email from invoice ('{$invoice->getBuyer()->getEmail()}')",
            500
        );

        $this->ipnManagement->postIpn();
    }

    public function testPostIpnNoValidatorErrorWhenEmailCasingMismatch(): void
    {
        $eventName = 'ivoice_confirmed';
        $orderInvoiceId = '12';
        $data = [
            'data' => [
                'orderId' => '00000012',
                'id' => $orderInvoiceId,
                'buyerFields' => [
                    'buyerName' => 'test',
                    'buyerEmail' => 'Test@exaMple.COM',
                    'buyerAddress1' => '12 test road'
                ],
                'amountPaid' => 1232132
            ],
            'event' => ['name' => $eventName]
        ];
        $serializer = new Json();
        $serializerData = $serializer->serialize($data);
        $this->serializer->expects($this->once())->method('unserialize')->willReturn($data);
        $this->request->expects($this->once())->method('getContent')->willReturn($serializerData);

        $invoice = $this->prepareInvoice();
        $client = $this->getMockBuilder(\BitPaySDK\Client::class)->disableOriginalConstructor()->getMock();
        $client->expects($this->once())->method('getInvoice')->willReturn($invoice);
        $this->client->expects($this->once())->method('initialize')->willReturn($client);
        $this->transactionRepository->expects($this->once())->method('findBy')->willReturn([]);

        $this->response->expects($this->never())->method('addMessage');

        $this->ipnManagement->postIpn();
    }

    public function testPostIpnCompleteInvalid(): void
    {
        $this->preparePostIpn('invoice_completed', 'test');

        $this->ipnManagement->postIpn();
    }

    public function testPostIpnHmacVerificationSuccess(): void
    {
        $this->bitpayInvoiceRepository->expects($this->once())->method('getByOrderId')->willReturn([
            'order_id' => 12,
            'invoice_id' => '12',
            'expiration_time' => 1726740384932,
            'acceptance_window'=> '',
            'bitpay_token' => '0:3:testtokenencoded'
        ]);
        $this->encryptor->expects($this->once())->method('decrypt')->willReturn('testtoken');
        $this->request->expects($this->once())->method('getHeader')->with('x-signature')->willReturn('test');
        $this->webhookVerifier->expects($this->once())->method('isValidHmac')->willReturn(true);
        $this->response->expects($this->never())->method('addMessage');

        $this->preparePostIpn('invoice_completed', 'test');

        $this->ipnManagement->postIpn();
    }

    public function testPostIpnHmacVerificationFailure(): void
    {
        $orderInvoiceId = '12';
        $data = $this->prepareData($orderInvoiceId, 'invoice_completed');
        $serializer = new Json();
        $serializerData = $serializer->serialize($data);
        $this->serializer->expects($this->once())->method('unserialize')->willReturn($data);
        $this->request->expects($this->once())->method('getContent')->willReturn($serializerData);

        $this->bitpayInvoiceRepository->expects($this->once())->method('getByOrderId')->willReturn([
            'order_id' => 12,
            'invoice_id' => '12',
            'expiration_time' => 1726740384932,
            'acceptance_window'=> '',
            'bitpay_token' => '0:3:testtokenencoded'
        ]);
        $this->encryptor->expects($this->once())->method('decrypt')->willReturn('testtoken');
        $this->request->expects($this->once())->method('getHeader')->with('x-signature')->willReturn('test');
        $this->webhookVerifier->expects($this->once())->method('isValidHmac')->willReturn(false);
        
        $this->response->expects($this->once())
            ->method('addMessage')
            ->with('HMAC Verification Failed!', 500)
            ->willReturnSelf();

        $this->ipnManagement->postIpn();
    }

    private function preparePostIpn(string $eventName, string $invoiceStatus): void
    {
        $orderInvoiceId = '12';
        $data = $this->prepareData($orderInvoiceId, $eventName);
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

        $invoice = $this->prepareInvoice();
        $client = $this->getMockBuilder(\BitPaySDK\Client::class)->disableOriginalConstructor()->getMock();
        $client->expects($this->once())->method('getInvoice')->willReturn($invoice);
        $this->client->expects($this->once())->method('initialize')->willReturn($client);

        $this->config->expects($this->once())->method('getBitpayEnv')->willReturn('test');
        $this->config->expects($this->once())->method('getToken')->willReturn('test');
        $this->invoice->expects($this->once())->method('getBPCCheckInvoiceStatus')->willReturn($invoiceStatus);
        $order = $this->getMock(Order::class);
        $order->expects($this->once())
            ->method('loadByIncrementId')
            ->with($data['data']['orderId'])
            ->willReturnSelf();
        $this->orderFactory->expects($this->once())
            ->method('create')
            ->willReturn($order);
    }

    /**
     * Prepare a close request and return the order mock
     *
     * The response mock expects exactly one redirect, which every postClose path must send.
     */
    private function prepareCloseRequest(
        string $returnId = 'valid-hash',
        bool $isValidHash = true,
        string $paymentMethod = 'bpcheckout',
        ?int $orderEntityId = 5
    ): MockObject {
        $orderId = '000000012';
        $this->url->method('getUrl')->willReturn('http://localhost/checkout/cart?reload=1');
        $this->request->method('getParam')->willReturnMap([
            ['orderID', '', $orderId],
            ['return_id', '', $returnId]
        ]);

        $response = $this->getMock(\Magento\Framework\HTTP\PhpEnvironment\Response::class);
        $response->expects($this->once())->method('setRedirect')->willReturnSelf();
        $this->responseFactory->method('create')->willReturn($response);

        $payment = $this->getMock(\Magento\Sales\Model\Order\Payment::class);
        $payment->method('getMethod')->willReturn($paymentMethod);

        $order = $this->getMock(Order::class);
        $order->expects($this->once())->method('loadByIncrementId')->with($orderId)->willReturnSelf();
        $order->method('getId')->willReturn($orderEntityId);
        $order->method('getIncrementId')->willReturn($orderId);
        $order->method('getPayment')->willReturn($payment);
        $this->orderFactory->method('create')->willReturn($order);

        $this->returnHashHelper->method('isValid')->with($returnId, $order)->willReturn($isValidHash);

        return $order;
    }

    private function prepareUnpaidInvoice(string $invoiceStatus): void
    {
        $this->prepareInvoiceStatus($invoiceStatus);
    }

    private function prepareInvoiceStatus(string $invoiceStatus): void
    {
        $this->transactionRepository->method('findByOrderIdAndTransactionStatus')
            ->with('000000012', 'new')
            ->willReturn([['transaction_id' => 'inv1']]);
        $invoice = $this->getMockBuilder(\BitPaySDK\Model\Invoice\Invoice::class)
            ->disableOriginalConstructor()->getMock();
        $invoice->method('getStatus')->willReturn($invoiceStatus);
        $client = $this->getMockBuilder(\BitPaySDK\Client::class)->disableOriginalConstructor()->getMock();
        $client->expects($this->once())->method('getInvoice')->with('inv1')->willReturn($invoice);
        $this->client->method('initialize')->willReturn($client);
    }

    private function expectQuoteRestored(MockObject $order): void
    {
        $order->method('getState')->willReturn('new');
        $order->method('canInvoice')->willReturn(true);
        $order->method('getData')->willReturn(['quote_id' => 21]);
        $quote = $this->getMock(Quote::class);
        $quote->expects($this->once())->method('loadByIdWithoutStore')->willReturnSelf();
        $quote->method('getId')->willReturn(21);
        $quote->expects($this->once())->method('setIsActive')->willReturnSelf();
        $quote->expects($this->once())->method('setReservedOrderId')->willReturnSelf();
        $this->quoteFactory->expects($this->once())->method('create')->willReturn($quote);
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
            $this->orderFactory,
            $this->quoteFactory,
            $this->logger,
            $this->config,
            $this->serializer,
            $this->transactionRepository,
            $this->invoice,
            $this->request,
            $this->client,
            $this->response,
            $this->bitpayInvoiceRepository,
            $this->encryptor,
            $this->webhookVerifier,
            $this->returnHashHelper
        );
    }

    /**
     * @return \BitPaySDK\Model\Invoice\Invoice
     */
    private function prepareInvoice(): \BitPaySDK\Model\Invoice\Invoice
    {
        $invoice = new \BitPaySDK\Model\Invoice\Invoice(12.00, 'USD');
        $invoice->setAmountPaid(1232132);
        $buyer = new Buyer();
        $buyer->setName('test');
        $buyer->setEmail('test@example.com');
        $buyer->setAddress1('12 test road');
        $invoice->setBuyer($buyer);
        return $invoice;
    }

    /**
     * @param string $orderInvoiceId
     * @param string $eventName
     * @return array
     */
    private function prepareData(string $orderInvoiceId, string $eventName): array
    {
        $data = [
            'data' => [
                'orderId' => '00000012',
                'id' => $orderInvoiceId,
                'buyerFields' => [
                    'buyerName' => 'test',
                    'buyerEmail' => 'test@example.com',
                    'buyerAddress1' => '12 test road'
                ],
                'amountPaid' => 1232132
            ],
            'event' => ['name' => $eventName]
        ];
        return $data;
    }
}
