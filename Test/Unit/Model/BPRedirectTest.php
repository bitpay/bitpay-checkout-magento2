<?php
declare(strict_types=1);

namespace Bitpay\BPCheckout\Test\Unit\Model;

use Bitpay\BPCheckout\Model\BPRedirect;
use Bitpay\BPCheckout\Logger\Logger;
use Bitpay\BPCheckout\Model\Config;
use Bitpay\BPCheckout\Model\Invoice;
use Bitpay\BPCheckout\Model\TransactionRepository;
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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
     * @var ActionFlag|MockObject $actionFlag
     */
    private $actionFlag;

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
        $this->actionFlag = $this->getMock(ActionFlag::class);
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
        $this->bpRedirect = $this->getClass();
    }

    public function testExecuteModal(): void
    {
        $incrementId = '0000012121';
        $bitpayToken = 'A32nRffe34dF2312vmm';
        $baseUrl = 'http://localhost';
        $method = $this->getMock(MethodInterface::class);
        $payment = $this->getMock(\Magento\Quote\Model\Quote\Payment::class);
        $billingAddress = $this->getMock(\Magento\Sales\Model\Order\Address::class);
        $lastOrderId = 12;
        $env = 'test';

        $params = new DataObject($this->getParams($incrementId, $bitpayToken));
        $this->checkoutSession->expects($this->once())
            ->method('getData')
            ->with('last_order_id')
            ->willReturn($lastOrderId);

        $item = new BPCItem($bitpayToken, $params, $env);
        $billingAddress->expects($this->once())->method('getData')
            ->willReturn(['first_name' => 'test', 'last_name' => 'test1']);
        $billingAddress->expects($this->once())->method('getFirstName')->willReturn('test');
        $billingAddress->expects($this->once())->method('getLastName')->willReturn('test1');
        $order = $this->getOrder($incrementId, $payment, $billingAddress);
        $this->prepareConfig($env, $bitpayToken, $baseUrl, 'modal');
        $method->expects($this->once())->method('getCode')->willReturn(Config::BITPAY_PAYMENT_METHOD_NAME);
        $payment->expects($this->once())->method('getMethodInstance')->willReturn($method);
        $this->order->expects($this->once())->method('load')->with($lastOrderId)->willReturn($order);
        $this->invoice->expects($this->once())->method('BPCCreateInvoice')->willReturn(['data' => ['id' => 232]]);

        $this->prepareResponse();

        $this->bpRedirect->execute();
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

    public function testExecuteException(): void
    {
        $incrementId = '0000012121';
        $bitpayToken = 'A32nRffe34dF2312vmm';
        $baseUrl = 'http://localhost';
        $method = $this->getMock(MethodInterface::class);
        $payment = $this->getMock(\Magento\Quote\Model\Quote\Payment::class);
        $billingAddress = $this->getMock(\Magento\Sales\Model\Order\Address::class);
        $lastOrderId = 12;
        $env = 'test';

        $params = new DataObject($this->getParams($incrementId, $bitpayToken));
        $this->checkoutSession->expects($this->once())
            ->method('getData')
            ->with('last_order_id')
            ->willReturn($lastOrderId);

        $item = new BPCItem($bitpayToken, $params, $env);
        $billingAddress->expects($this->once())->method('getData')
            ->willReturn(['first_name' => 'test', 'last_name' => 'test1']);
        $billingAddress->expects($this->once())->method('getFirstName')->willReturn('test');
        $billingAddress->expects($this->once())->method('getLastName')->willReturn('test1');
        $order = $this->getOrder($incrementId, $payment, $billingAddress);
        $this->prepareConfig($env, $bitpayToken, $baseUrl, 'modal');
        $method->expects($this->once())->method('getCode')->willReturn(Config::BITPAY_PAYMENT_METHOD_NAME);
        $payment->expects($this->once())->method('getMethodInstance')->willReturn($method);
        $this->order->expects($this->once())->method('load')->with($lastOrderId)->willReturn($order);
        $this->invoice->expects($this->once())
            ->method('BPCCreateInvoice')
            ->willThrowException(new \Exception('Something went wrong'));
        $this->prepareResponse();

        $this->bpRedirect->execute();
    }

    public function testExecuteRedirect(): void
    {
        $incrementId = '0000012121';
        $bitpayToken = 'A32nRffe34dF2312vmm';
        $baseUrl = 'http://localhost';
        $method = $this->getMock(MethodInterface::class);
        $payment = $this->getMock(\Magento\Quote\Model\Quote\Payment::class);
        $billingAddress = $this->getMock(\Magento\Sales\Model\Order\Address::class);
        $lastOrderId = 12;
        $env = 'test';

        $params = new DataObject($this->getParams($incrementId, $bitpayToken));
        $this->checkoutSession->expects($this->once())
            ->method('getData')
            ->with('last_order_id')
            ->willReturn($lastOrderId);

        $item = new BPCItem($bitpayToken, $params, $env);
        $billingAddress->expects($this->once())->method('getData')
            ->willReturn(['first_name' => 'test', 'last_name' => 'test1']);
        $billingAddress->expects($this->once())->method('getFirstName')->willReturn('test');
        $billingAddress->expects($this->once())->method('getLastName')->willReturn('test1');
        $order = $this->getOrder($incrementId, $payment, $billingAddress);
        $this->prepareConfig($env, $bitpayToken, $baseUrl, 'redirect');
        $method->expects($this->once())->method('getCode')->willReturn(Config::BITPAY_PAYMENT_METHOD_NAME);
        $payment->expects($this->once())->method('getMethodInstance')->willReturn($method);
        $this->order->expects($this->once())->method('load')->with($lastOrderId)->willReturn($order);
        $this->invoice->expects($this->once())
            ->method('BPCCreateInvoice')
            ->willReturn(['data' => ['id' => 232, 'url' => 'https://localhost/example']]);

        $this->bpRedirect->execute();
    }

    private function prepareResponse(): void
    {
        $response = $this->getMock(\Magento\Framework\HTTP\PhpEnvironment\Response::class);
        $response->expects($this->once())->method('setRedirect')->willReturnSelf();
        $this->responseFactory->expects($this->once())->method('create')->willReturn($response);
    }

    private function getOrder(string $incrementId, MockObject $payment, MockObject $billingAddress)
    {
        $order = $this->getMock(Order::class);
        $order->expects($this->once())->method('getIncrementId')->willReturn($incrementId);
        $order->expects($this->once())->method('getPayment')->willReturn($payment);
        $order->expects($this->once())->method('setState')->willReturn($order);
        $order->expects($this->once())->method('setStatus')->willReturn($order);
        $order->expects($this->once())->method('save')->willReturn($order);
        $order->expects($this->any())->method('getCustomerEmail')->willReturn('test@example.com');
        $order->expects($this->any())->method('getBillingAddress')->willReturn($billingAddress);

        return $order;
    }

    private function prepareConfig(string $env, string $bitpayToken, string $baseUrl, string $ux): void
    {
        $this->config->expects($this->once())->method('getBPCheckoutOrderStatus')->willReturn('pending');
        $this->config->expects($this->once())->method('getBitpayEnv')->willReturn($env);
        $this->config->expects($this->once())->method('getToken')->willReturn($bitpayToken);
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
            $this->actionFlag,
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
            $this->resultPageFactory
        );
    }
}
