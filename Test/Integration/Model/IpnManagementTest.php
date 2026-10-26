<?php

namespace Bitpay\BPCheckout\Test\Integration\Model;

use Bitpay\BPCheckout\Model\Client;
use Bitpay\BPCheckout\Model\Config;
use Bitpay\BPCheckout\Model\Invoice;
use Bitpay\BPCheckout\Model\IpnManagement;
use Bitpay\BPCheckout\Model\TransactionRepository;
use BitPaySDK\Model\Invoice\Buyer;
use Magento\Framework\ObjectManagerInterface;
use Bitpay\BPCheckout\Logger\Logger;
use Bitpay\BPCheckout\Model\BitpayInvoiceRepository;
use Bitpay\BPCheckout\Model\Ipn\WebhookVerifier;
use Bitpay\BPCheckout\Helper\ReturnHash;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Webapi\Rest\Response;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IpnManagementTest extends TestCase
{
    /**
     * @var IpnManagement $ipnManagement
     */
    private $ipnManagement;

    /**
     * @var ResponseFactory $responseFactory
     */
    private $responseFactory;

    /**
     * @var UrlInterface $url
     */
    private $url;

    /**
     * @var Session $checkoutSession
     */
    private $checkoutSession;

    /**
     * @var QuoteFactory $quoteFactory
     */
    private $quoteFactory;

    /**
     * @var OrderFactory|MockObject $orderFactory
     */
    private $orderFactory;

    /**
     * @var Registry $coreRegistry
     */
    private $coreRegistry;

    /**
     * @var Logger $logger
     */
    private $logger;

    /**
     * @var Config $config
     */
    private $config;

    /**
     * @var Json $serializer
     */
    private $serializer;

    /**
     * @var TransactionRepository $transactionRepository
     */
    private $transactionRepository;

    /**
     * @var Invoice|MockObject $invoice
     */
    private $invoice;

    /**
     * @var Request $request
     */
    private $request;

    /**
     * @var ObjectManagerInterface $objectManager
     */
    private $objectManager;

    /**
     * @var Client|MockObject $client
     */
    private $client;

    /**
     * @var Response $response
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
    private $webhookVerifier;

    /**
     * @var ReturnHash $returnHash
     */
    private $returnHash;

    public function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->coreRegistry = $this->objectManager->get(Registry::class);
        $this->responseFactory = $this->objectManager->get(ResponseFactory::class);
        $this->url = $this->objectManager->get(UrlInterface::class);
        $this->quoteFactory = $this->objectManager->get(QuoteFactory::class);
        $this->orderFactory = $this->objectManager->get(OrderFactory::class);
        $this->checkoutSession = $this->objectManager->get(Session::class);
        $this->logger = $this->objectManager->get(Logger::class);
        $this->config = $this->objectManager->get(Config::class);
        $this->serializer = $this->objectManager->get(Json::class);
        $this->transactionRepository = $this->objectManager->get(TransactionRepository::class);
        /**
         * @var Invoice|MockObject
         */
        $this->invoice = $this->getMockBuilder(Invoice::class)->disableOriginalConstructor()->getMock();
        $this->request = $this->objectManager->get(Request::class);
        /**
         * @var Client|MockObject
         */
        $this->client = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->getMock();
        $this->response = $this->objectManager->get(Response::class);
        $this->bitpayInvoiceRepository = $this->objectManager->get(BitpayInvoiceRepository::class);
        $this->encryptor =$this->objectManager->get(EncryptorInterface::class);
        $this->webhookVerifier = $this->objectManager->get(WebhookVerifier::class);
        $this->returnHash = $this->objectManager->get(ReturnHash::class);

        $this->ipnManagement = new IpnManagement(
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
            $this->returnHash
        );
    }

    /**
     * A close request without the order's return hash must not touch the order.
     *
     * @magentoDataFixture Bitpay_BPCheckout::Test/Integration/_files/order.php
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_invoice_close_handling delete_order
     */
    public function testPostCloseWithoutReturnIdKeepsOrder()
    {
        $order = $this->orderFactory->create()->loadByIncrementId('100000001');
        $this->request->setParam('orderID', $order->getIncrementId());

        $this->ipnManagement->postClose();

        $this->assertEquals($order->getId(), $this->orderFactory->create()->loadByIncrementId('100000001')->getId());
    }

    /**
     * A close request with a wrong return hash must not touch the order.
     *
     * @magentoDataFixture Bitpay_BPCheckout::Test/Integration/_files/order.php
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_invoice_close_handling delete_order
     */
    public function testPostCloseWithInvalidReturnIdKeepsOrder()
    {
        $order = $this->orderFactory->create()->loadByIncrementId('100000001');
        $this->request->setParam('orderID', $order->getIncrementId());
        $this->request->setParam('return_id', 'not-the-right-hash');

        $this->ipnManagement->postClose();

        $this->assertEquals($order->getId(), $this->orderFactory->create()->loadByIncrementId('100000001')->getId());
    }

    /**
     * @magentoDataFixture Bitpay_BPCheckout::Test/Integration/_files/transaction.php
     * @magentoDataFixture Bitpay_BPCheckout::Test/Integration/_files/order.php
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_endpoint test
     * @magentoDataFixture Bitpay_BPCheckout::Test/Integration/_files/config.php
     */
    public function testPostIpn()
    {
        $orderId = '100000001';
        $orderInvoiceId = 'VjvZuvsWT36tzYX65ZXk4xq';
        $data = [
            'data' => [
                'orderId' => $orderId,
                'id' => $orderInvoiceId,
                'amountPaid' => 12312321,
                'buyerFields' => ['buyerName' => 'test', 'buyerEmail' => 'test@example.com']],
            'event' => [
                'name' => 'invoice_completed'
            ]
        ];

        $content = $this->serializer->serialize($data);
        $this->request->setContent($content);
        $params = new DataObject($this->getParams());
        $invoice = $this->prepareInvoice($params);
        $bitpayClient = $this->getMockBuilder(\BitPaySDK\Client::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->client->expects($this->once())->method('initialize')->willReturn($bitpayClient);
        $bitpayClient->expects($this->once())->method('getInvoice')->willReturn($invoice);
        $this->invoice->expects($this->once())
            ->method('getBPCCheckInvoiceStatus')
            ->willReturn('complete');

        $this->ipnManagement->postIpn();

        $order = $this->orderFactory->create()->loadByIncrementId('100000001');
        $result = $this->transactionRepository->findBy($orderId, $orderInvoiceId);

        $this->assertEquals('complete', $result[0]['transaction_status']);
        $this->assertEquals('100000001', $order->getIncrementId());
        $this->assertEquals('processing', $order->getState());
        $this->assertEquals('processing', $order->getStatus());
    }

    /**
     * @param DataObject $params
     * @return \BitPaySDK\Model\Invoice\Invoice
     */
    private function prepareInvoice(DataObject $params): \BitPaySDK\Model\Invoice\Invoice
    {
        $invoice = new \BitPaySDK\Model\Invoice\Invoice(
            $params->getData('price'),
            $params->getData('currency')
        );
        $buyer = new Buyer();
        $buyer->setName($params->getData('buyer')['name']);
        $buyer->setEmail($params->getData('buyer')['email']);
        $invoice->setBuyer($buyer);
        $invoice->setId('test');
        $invoice->setOrderId($params->getData('orderId'));
        $invoice->setRedirectURL($params->getData('redirectURL'));
        $invoice->setNotificationURL($params->getData('notificationURL'));
        $invoice->setCloseURL($params->getData('closeURL'));
        $invoice->setExpirationTime(23323423423423);
        $invoice->setAmountPaid(12312321);
        $invoice->setExtendedNotifications($params->getData('extendedNotifications'));

        return $invoice;
    }

    private function getParams(): array
    {
        $baseUrl = $this->config->getBaseUrl();
        return [
            'extension_version' => Config::EXTENSION_VERSION,
            'price' => 23,
            'currency' => 'USD',
            'buyer' => new DataObject(['name' => 'test', 'email' => 'test@example.com']),
            'orderId' => '00000123231',
            'redirectURL' => $baseUrl . 'bitpay-invoice/?order_id=00000123231',
            'notificationURL' => $baseUrl . 'rest/V1/bitpay-bpcheckout/ipn',
            'closeURL' => $baseUrl . 'rest/V1/bitpay-bpcheckout/close?orderID=00000123231',
            'extendedNotifications' => true,
            'token' => 'AMLTTY9x9TGXFPcsnLLjem1CaDJL3mRMWupBrm9baacy',
            'invoiceID' => 'RCYxvSq4djGwuWgcBDaGbT'
        ];
    }
}
