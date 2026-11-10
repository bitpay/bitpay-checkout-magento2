<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Unit\Controller\Invoice;

use Bitpay\BPCheckout\Controller\Invoice\Pay;
use Bitpay\BPCheckout\Model\Client;
use Bitpay\BPCheckout\Model\Config;
use Bitpay\BPCheckout\Model\TransactionRepository;
use BitPaySDK\Model\Invoice\Invoice;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\Manager;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PayTest extends TestCase
{
    private const ORDER_ENTITY_ID = '12';
    private const ORDER_INCREMENT_ID = '000000012';
    private const OTHER_INCREMENT_ID = '000000099';
    private const INVOICE_ID = 'TgBCNTBKpiyVbxSUZtM3SU';
    private const INVOICE_URL = 'https://test.bitpay.com/invoice?id=TgBCNTBKpiyVbxSUZtM3SU';
    private const QUOTE_ID = '33';

    /**
     * @var RequestInterface|MockObject
     */
    private $request;

    /**
     * @var Manager|MockObject
     */
    private $messageManager;

    /**
     * @var RedirectFactory|MockObject
     */
    private $resultRedirectFactory;

    /**
     * @var Redirect|MockObject
     */
    private $redirect;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepository;

    /**
     * @var TransactionRepository|MockObject
     */
    private $transactionRepository;

    /**
     * @var Session|MockObject
     */
    private $checkoutSession;

    /**
     * @var Client|MockObject
     */
    private $client;

    /**
     * @var \BitPaySDK\Client|MockObject
     */
    private $bitpayClient;

    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var Order|MockObject
     */
    private $order;

    /**
     * @var Pay
     */
    private $pay;

    public function setUp(): void
    {
        $this->request = $this->createMock(RequestInterface::class);
        $this->messageManager = $this->createMock(Manager::class);
        $this->resultRedirectFactory = $this->createMock(RedirectFactory::class);
        $this->redirect = $this->createMock(Redirect::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->transactionRepository = $this->createMock(TransactionRepository::class);
        $this->checkoutSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->addMethods(['setLastSuccessQuoteId', 'setLastQuoteId', 'setLastOrderId'])
            ->getMock();
        $this->client = $this->createMock(Client::class);
        $this->bitpayClient = $this->createMock(\BitPaySDK\Client::class);
        $this->config = $this->createMock(Config::class);
        $this->order = $this->createMock(Order::class);

        $this->resultRedirectFactory->method('create')->willReturn($this->redirect);
        $this->client->method('initialize')->willReturn($this->bitpayClient);
        $this->order->method('getIncrementId')->willReturn(self::ORDER_INCREMENT_ID);
        $this->order->method('getEntityId')->willReturn(self::ORDER_ENTITY_ID);
        $this->order->method('getQuoteId')->willReturn(self::QUOTE_ID);

        $this->pay = new Pay(
            $this->request,
            $this->messageManager,
            $this->resultRedirectFactory,
            $this->orderRepository,
            $this->transactionRepository,
            $this->checkoutSession,
            $this->client,
            $this->config
        );
    }

    public function testExecuteRedirectsToInvoiceForOwnOrder(): void
    {
        $this->prepareValidRequest();
        $this->prepareSessionLastRealOrder(self::ORDER_INCREMENT_ID);
        $this->order->method('canInvoice')->willReturn(true);
        $this->transactionRepository->expects($this->once())
            ->method('findBy')
            ->with(self::ORDER_INCREMENT_ID, self::INVOICE_ID)
            ->willReturn([['order_id' => self::ORDER_INCREMENT_ID, 'transaction_id' => self::INVOICE_ID]]);
        $this->bitpayClient->expects($this->once())
            ->method('getInvoice')
            ->with(self::INVOICE_ID)
            ->willReturn($this->prepareInvoice(self::ORDER_INCREMENT_ID, 'new'));

        $this->checkoutSession->expects($this->once())
            ->method('setLastSuccessQuoteId')->with(self::QUOTE_ID)->willReturnSelf();
        $this->checkoutSession->expects($this->once())
            ->method('setLastQuoteId')->with(self::QUOTE_ID)->willReturnSelf();
        $this->checkoutSession->expects($this->once())
            ->method('setLastOrderId')->with(self::ORDER_ENTITY_ID)->willReturnSelf();
        $this->redirect->expects($this->once())->method('setUrl')->with(self::INVOICE_URL)->willReturnSelf();
        $this->redirect->expects($this->never())->method('setPath');
        $this->messageManager->expects($this->never())->method('addErrorMessage');

        $this->assertSame($this->redirect, $this->pay->execute());
    }

    public function testExecuteRejectsOrderThatIsNotInSession(): void
    {
        $this->prepareValidRequest();
        $this->prepareSessionLastRealOrder(self::OTHER_INCREMENT_ID);
        $this->order->method('canInvoice')->willReturn(true);

        $this->transactionRepository->expects($this->never())->method('findBy');
        $this->client->expects($this->never())->method('initialize');
        $this->assertRejected('Invalid request!');
    }

    public function testExecuteRejectsWhenSessionHasNoOrder(): void
    {
        $this->prepareValidRequest();
        $this->prepareSessionLastRealOrder(null);
        $this->order->method('canInvoice')->willReturn(true);

        $this->transactionRepository->expects($this->never())->method('findBy');
        $this->client->expects($this->never())->method('initialize');
        $this->assertRejected('Invalid request!');
    }

    public function testExecuteRejectsInvoiceNotLinkedToOrder(): void
    {
        $this->prepareValidRequest();
        $this->prepareSessionLastRealOrder(self::ORDER_INCREMENT_ID);
        $this->order->method('canInvoice')->willReturn(true);
        $this->transactionRepository->expects($this->once())
            ->method('findBy')
            ->with(self::ORDER_INCREMENT_ID, self::INVOICE_ID)
            ->willReturn(null);

        $this->client->expects($this->never())->method('initialize');
        $this->assertRejected('Invalid request!');
    }

    public function testExecuteRejectsInvoiceThatBelongsToAnotherOrder(): void
    {
        $this->prepareValidRequest();
        $this->prepareSessionLastRealOrder(self::ORDER_INCREMENT_ID);
        $this->order->method('canInvoice')->willReturn(true);
        $this->transactionRepository->method('findBy')
            ->willReturn([['order_id' => self::ORDER_INCREMENT_ID, 'transaction_id' => self::INVOICE_ID]]);
        $this->bitpayClient->method('getInvoice')
            ->willReturn($this->prepareInvoice(self::OTHER_INCREMENT_ID, 'new'));

        $this->assertRejected('Invalid request!');
    }

    public function testExecuteRejectsPaidInvoice(): void
    {
        $this->prepareValidRequest();
        $this->prepareSessionLastRealOrder(self::ORDER_INCREMENT_ID);
        $this->order->method('canInvoice')->willReturn(true);
        $this->transactionRepository->method('findBy')
            ->willReturn([['order_id' => self::ORDER_INCREMENT_ID, 'transaction_id' => self::INVOICE_ID]]);
        $this->bitpayClient->method('getInvoice')
            ->willReturn($this->prepareInvoice(self::ORDER_INCREMENT_ID, 'paid'));

        $this->assertRejected('The invoice has already been paid!');
    }

    public function testExecuteRejectsWhenCloseHandlingIsNotKeepOrder(): void
    {
        $this->request->method('getParam')->willReturnMap([
            ['order_id', null, self::ORDER_ENTITY_ID],
            ['invoice_id', null, self::INVOICE_ID],
        ]);
        $this->config->method('getBitpayCheckoutSuccess')->willReturn('standard');
        $this->config->method('getBitpayInvoiceCloseHandling')->willReturn('delete_order');

        $this->orderRepository->expects($this->never())->method('get');
        $this->assertRejected('Invalid request!');
    }

    private function prepareValidRequest(): void
    {
        $this->request->method('getParam')->willReturnMap([
            ['order_id', null, self::ORDER_ENTITY_ID],
            ['invoice_id', null, self::INVOICE_ID],
        ]);
        $this->config->method('getBitpayCheckoutSuccess')->willReturn('standard');
        $this->config->method('getBitpayInvoiceCloseHandling')->willReturn('keep_order');
        $this->orderRepository->method('get')->with(self::ORDER_ENTITY_ID)->willReturn($this->order);
    }

    private function prepareSessionLastRealOrder(?string $incrementId): void
    {
        $this->checkoutSession->method('getData')->with('last_real_order_id')->willReturn($incrementId);
    }

    private function prepareInvoice(string $orderId, string $status): Invoice
    {
        $invoice = new Invoice(12.00, 'USD');
        $invoice->setId(self::INVOICE_ID);
        $invoice->setOrderId($orderId);
        $invoice->setStatus($status);
        $invoice->setUrl(self::INVOICE_URL);

        return $invoice;
    }

    private function assertRejected(string $message): void
    {
        $this->checkoutSession->expects($this->never())->method('setLastOrderId');
        $this->messageManager->expects($this->once())->method('addErrorMessage')->with($message);
        $this->redirect->expects($this->once())->method('setPath')->with('checkout/cart')->willReturnSelf();
        $this->redirect->expects($this->never())->method('setUrl');

        $this->assertSame($this->redirect, $this->pay->execute());
    }
}
