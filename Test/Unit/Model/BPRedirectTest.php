<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Unit\Model;

use Bitpay\BPCheckout\Model\BitpayInvoiceRepository;
use Bitpay\BPCheckout\Model\BPRedirect;
use Bitpay\BPCheckout\Logger\Logger;
use Bitpay\BPCheckout\Model\Client;
use Bitpay\BPCheckout\Model\Config;
use Bitpay\BPCheckout\Model\Invoice;
use Bitpay\BPCheckout\Model\TransactionRepository;
use BitPaySDK\Model\Invoice\Buyer;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Bitpay\BPCheckout\Model\Ipn\BPCItem;
use Magento\Framework\Message\Manager;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Model\OrderRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BPRedirectTest extends TestCase
{
    /**
     * @var BPRedirect $bpRedirect
     */
    private $bpRedirect;

    /**
     * @var Seesion|MockObject $checkoutSession
     */
    private $checkoutSession;

    /**
     * @var RedirectInterface|MockObject $redirect
     */
    private $redirect;

    /**
     * @var \Magento\Framework\App\Response\Http|MockObject $response
     */
    private $response;

    /**
     * @var Order|MockObject $order
     */
    private $order;

    /**
     * @var TransactionRepository|MockObject $transactionRepository
     */
    private $transactionRepository;

    /**
     * @var Config|MockObject $config
     */
    private $config;

    /**
     * @var Client|MockObject $client
     */
    private $client;

    /**
     * @var OrderRepository $orderRepository
     */
    private $orderRepository;

    /**
     * @var BitpayInvoiceRepository $bitpayInvoiceRepository
     */
    private $bitpayInvoiceRepository;

    /**
     * @var ResponseFactory|MockObject $responseFactory
     */
    private $responseFactory;

    /**
     * @var Invoice|MockObject $invoice
     */
    private $invoice;

    /**
     * @var Manager|MockObject $messageManager
     */
    private $messageManager;

    /**
     * @var Registry|MockObject $registry
     */
    private $registry;

    /**
     * @var UrlInterface|MockObject $url
     */
    private $url;

    /**
     * @var Logger|MockObject $logger
     */
    private $logger;

    /**
     * @var PageFactory|MockObject $resultPageFactory
     */
    private $resultPageFactory;

    public function setUp(): void
    {
        $this->checkoutSession = $this->getMock(Session::class);
        $this->client = $this->getMock(Client::class);
        $this->redirect = $this->getMock(RedirectInterface::class);
        $this->response = $this->getMock(\Magento\Framework\App\Response\Http::class);
        $this->order = $this->getMock(\Magento\Sales\Model\Order::class);
        $this->config = $this->getMock(Config::class);
        $this->transactionRepository = $this->getMock(TransactionRepository::class);
        $this->responseFactory = $this->getMock(ResponseFactory::class);
        $this->invoice = $this->getMock(Invoice::class);
        $this->messageManager = $this->getMock(Manager::class);
        $this->registry = $this->getMock(Registry::class);
        $this->url = $this->getMock(UrlInterface::class);
        $this->logger = $this->getMock(Logger::class);
        $this->resultPageFactory = $this->getMock(PageFactory::class);
        $this->orderRepository = $this->getMock(OrderRepository::class);
        $this->bitpayInvoiceRepository = $this->getMock(BitpayInvoiceRepository::class);
        $this->bpRedirect = $this->getClass();
    }

    /**
     * @param $ux
     * @return void
     * @throws \Exception
     * @dataProvider getUxDataProvider
     */
    public function testExecute($ux): void
    {
        $incrementId = '0000012121';
        $bitpayToken = 'A32nRffe34dF2312vmm';
        $baseUrl = 'http://localhost';
        $method = $this->getMock(MethodInterface::class);
        $payment = $this->getMock(\Magento\Quote\Model\Quote\Payment::class);
        $billingAddress = $this->getMock(\Magento\Sales\Model\Order\Address::class);
        $lastOrderId = 12;

        $params = new DataObject($this->getParams($incrementId, $bitpayToken));
        $this->checkoutSession->expects($this->once())
            ->method('getData')
            ->with('last_order_id')
            ->willReturn($lastOrderId);

        $billingAddress->expects($this->once())->method('getData')
            ->willReturn(['first_name' => 'test', 'last_name' => 'test1']);
        $billingAddress->expects($this->once())->method('getFirstName')->willReturn('test');
        $billingAddress->expects($this->once())->method('getLastName')->willReturn('test1');
        $order = $this->getOrder($incrementId, $payment, $billingAddress, $lastOrderId);
        $this->prepareConfig($baseUrl, $ux);
        $method->expects($this->once())->method('getCode')->willReturn(Config::BITPAY_PAYMENT_METHOD_NAME);
        $payment->expects($this->once())->method('getMethodInstance')->willReturn($method);
        $this->order->expects($this->once())->method('load')->with($lastOrderId)->willReturn($order);

        $invoice = $this->prepareInvoice($params);

        $bitpayClient = new \BitPaySDK\Client();
        $this->client->expects($this->once())->method('initialize')->willReturn($bitpayClient);

        $this->invoice->expects($this->once())->method('BPCCreateInvoice')->willReturn($invoice);

        $this->orderRepository->expects($this->once())->method('save')->willReturn($order);
        $this->bitpayInvoiceRepository->expects($this->once())->method('add');
        $this->transactionRepository->expects($this->once())->method('add');

        if ($ux === 'modal') {
            $this->prepareResponse();
        }

        $this->bpRedirect->execute();
    }

    /**
     * @return array[]
     */
    public function getUxDataProvider(): array
    {
        return [
            ['modal'], ['redirect']
        ];
    }

    public function testExecuteNoOrderId(): void
    {
        $response = $this->getMock(\Magento\Framework\HTTP\PhpEnvironment\Response::class);
        $this->checkoutSession->expects($this->once())
            ->method('getData')
            ->with('last_order_id')
            ->willReturn(null);
        $response->expects($this->once())->method('sendResponse')->willReturnSelf();
        $this->response->expects($this->once())->method('setRedirect')->willReturn($response);

        $this->bpRedirect->execute();
    }

    public function testExecuteNoBitpayPaymentMethod(): void
    {
        $incrementId = '000000222222';
        $lastOrderId = 11;
        $baseUrl = 'http://localhost';
        $this->checkoutSession->expects($this->once())
            ->method('getData')
            ->with('last_order_id')
            ->willReturn($lastOrderId);

        $page = $this->getMock(\Magento\Framework\View\Result\Page::class);
        $method = $this->getMock(MethodInterface::class);
        $payment = $this->getMock(\Magento\Quote\Model\Quote\Payment::class);
        $method->expects($this->once())->method('getCode')->willReturn('checkmo');
        $payment->expects($this->once())->method('getMethodInstance')->willReturn($method);
        $order = $this->getMock(Order::class);
        $order->expects($this->once())->method('getIncrementId')->willReturn($incrementId);
        $order->expects($this->once())->method('getPayment')->willReturn($payment);
        $this->order->expects($this->once())->method('load')->with($lastOrderId)->willReturn($order);
        $this->config->expects($this->once())->method('getBaseUrl')->willReturn($baseUrl);
        $this->resultPageFactory->expects($this->once())->method('create')->wilLReturn($page);

        $this->bpRedirect->execute();
    }

    /**
     * @param $exceptionType
     * @return void
     * @throws \Exception
     * @dataProvider exceptionTypeDataProvider
     */
    public function testExecuteException($exceptionType): void
    {
        $incrementId = '0000012121';
        $baseUrl = 'http://localhost';
        $method = $this->getMock(MethodInterface::class);
        $payment = $this->getMock(\Magento\Quote\Model\Quote\Payment::class);
        $billingAddress = $this->getMock(\Magento\Sales\Model\Order\Address::class);
        $lastOrderId = 12;

        $this->checkoutSession->expects($this->once())
            ->method('getData')
            ->with('last_order_id')
            ->willReturn($lastOrderId);

        $billingAddress->expects($this->once())->method('getData')
            ->willReturn(['first_name' => 'test', 'last_name' => 'test1']);
        $billingAddress->expects($this->once())->method('getFirstName')->willReturn('test');
        $billingAddress->expects($this->once())->method('getLastName')->willReturn('test1');
        $order = $this->getOrder($incrementId, $payment, $billingAddress, null);
        $this->prepareConfig($baseUrl, 'redirect');
        $method->expects($this->once())->method('getCode')->willReturn(Config::BITPAY_PAYMENT_METHOD_NAME);
        $payment->expects($this->once())->method('getMethodInstance')->willReturn($method);
        $this->order->expects($this->once())->method('load')->with($lastOrderId)->willReturn($order);

        $client = new \BitPaySDK\Client();
        $this->client->expects($this->once())->method('initialize')->willReturn($client);
        $this->prepareResponse();

        $this->invoice->expects($this->once())
            ->method('BPCCreateInvoice')
            ->willThrowException(new $exceptionType('something went wrong'));

        $this->bpRedirect->execute();
    }

    public function exceptionTypeDataProvider(): array
    {
        return [
            [new \Exception], [new \Error]
        ];
    }

    private function prepareResponse(): void
    {
        $response = $this->getMock(\Magento\Framework\HTTP\PhpEnvironment\Response::class);
        $response->expects($this->once())->method('setRedirect')->willReturnSelf();
        $this->responseFactory->expects($this->once())->method('create')->willReturn($response);
    }

    private function getOrder(string $incrementId, MockObject $payment, MockObject $billingAddress, ?int $orderId)
    {
        $order = $this->getMock(Order::class);
        $order->expects($this->once())->method('getIncrementId')->willReturn($incrementId);
        if ($orderId) {
            $order->expects($this->once())->method('getId')->willReturn($orderId);
        }

        $order->expects($this->once())->method('getPayment')->willReturn($payment);
        $order->expects($this->once())->method('setState')->willReturn($order);
        $order->expects($this->once())->method('setStatus')->willReturn($order);
        $order->expects($this->any())->method('getCustomerEmail')->willReturn('test@example.com');
        $order->expects($this->any())->method('getBillingAddress')->willReturn($billingAddress);

        return $order;
    }

    private function prepareConfig(string $baseUrl, string $ux): void
    {
        $this->config->expects($this->once())->method('getBPCheckoutOrderStatus')->willReturn('pending');
        $this->config->expects($this->once())->method('getBitpayUx')->willReturn($ux);
        $this->config->expects($this->once())->method('getBaseUrl')->willReturn($baseUrl);
    }

    private function getMock(string $type): MockObject
    {
        return $this->getMockBuilder($type)->disableOriginalConstructor()->getMock();
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
            'token' => $bitpayToken
        ];
    }

    private function getClass(): BPRedirect
    {
        return new BPRedirect(
            $this->checkoutSession,
            $this->redirect,
            $this->response,
            $this->order,
            $this->config,
            $this->transactionRepository,
            $this->responseFactory,
            $this->invoice,
            $this->messageManager,
            $this->registry,
            $this->url,
            $this->logger,
            $this->resultPageFactory,
            $this->client,
            $this->orderRepository,
            $this->bitpayInvoiceRepository
        );
    }

    /**
     * @param DataObject $params
     * @return \BitPaySDK\Model\Invoice\Invoice
     */
    private function prepareInvoice(DataObject $params): \BitPaySDK\Model\Invoice\Invoice
    {
        $invoice = new \BitPaySDK\Model\Invoice\Invoice($params->getData('price'), $params->getData('currency'));
        $buyer = new Buyer();
        $buyer->setEmail($params->getData('buyer')['email']);
        $buyer->setName($params->getData('buyer')['name']);
        $invoice->setBuyer($buyer);
        $invoice->setOrderId($params->getData('orderId'));
        $invoice->setId('test');
        $invoice->setCloseURL($params->getData('closeURL'));
        $invoice->setRedirectURL($params->getData('redirectURL'));
        $invoice->setNotificationURL($params->getData('notificationURL'));
        $invoice->setExtendedNotifications($params->getData('extendedNotifications'));
        $invoice->setExpirationTime('23323423423423');

        return $invoice;
    }
}
