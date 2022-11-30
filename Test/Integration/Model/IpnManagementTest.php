<?php

namespace Bitpay\BPCheckout\Test\Integration\Model;

use Bitpay\BPCheckout\Model\Config;
use Bitpay\BPCheckout\Model\Invoice;
use Bitpay\BPCheckout\Model\IpnManagement;
use Bitpay\BPCheckout\Model\TransactionRepository;
use Magento\Framework\ObjectManagerInterface;
use Bitpay\BPCheckout\Api\IpnManagementInterface;
use Bitpay\BPCheckout\Logger\Logger;
use Bitpay\BPCheckout\Model\Ipn\BPCItem;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

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
     * @var OrderInterface $url
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
     * @var OrderInterface $orderInterface
     */
    private $orderInterface;

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
     * @var Invoice|\PHPUnit\Framework\MockObject\MockObject $invoice
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

    public function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->coreRegistry = $this->objectManager->get(Registry::class);
        $this->responseFactory = $this->objectManager->get(ResponseFactory::class);
        $this->url = $this->objectManager->get(UrlInterface::class);
        $this->quoteFactory = $this->objectManager->get(QuoteFactory::class);
        $this->orderInterface = $this->objectManager->get(OrderInterface::class);
        $this->checkoutSession = $this->objectManager->get(Session::class);
        $this->logger = $this->objectManager->get(Logger::class);
        $this->config = $this->objectManager->get(Config::class);
        $this->serializer = $this->objectManager->get(Json::class);
        $this->transactionRepository = $this->objectManager->get(TransactionRepository::class);
        $this->invoice = $this->getMockBuilder(Invoice::class)->disableOriginalConstructor()->getMock();
        $this->request = $this->objectManager->get(Request::class);
        $this->ipnManagement = new IpnManagement(
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

    /**
     * @magentoDataFixture Bitpay_BPCheckout::Test/Integration/_files/order.php
     */
    public function testPostClose()
    {
        $order = $this->orderInterface->loadByIncrementId('100000001');
        $this->request->setParam('orderID', $order->getEntityId());
        $quoteId = $order->getQuoteId();
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteFactory->create()->loadByIdWithoutStore($quoteId);

        $this->ipnManagement->postClose();
        $order = $this->orderInterface->loadByIncrementId('100000001');
        $this->assertEquals($quoteId, $this->checkoutSession->getQuoteId());
    }

    /**
     * @magentoDataFixture Bitpay_BPCheckout::Test/Integration/_files/transaction.php
     * @magentoDataFixture Bitpay_BPCheckout::Test/Integration/_files/order.php
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_endpoint test
     * @magentoConfigFixture current_store payment/bpcheckout/bitpay_devtoken AMLTTY9x9TGXFPcsnLLjem1CaDJL3mRMWupBrm9baacy
     *
     */
    public function testPostIpn()
    {
        $orderId = '100000001';
        $orderInvoiceId = 'VjvZuvsWT36tzYX65ZXk4xq';
        $data = [
            'data' => ['orderId' => $orderId, 'id' => $orderInvoiceId],
            'event' => ['name' => 'invoice_completed']
        ];
        $content = $this->serializer->serialize($data);
        $this->request->setContent($content);

        $this->invoice->expects($this->once())->method('getBPCCheckInvoiceStatus')->willReturn('complete');

        $this->ipnManagement->postIpn();

        $order = $this->orderInterface->loadByIncrementId($orderId);
        $result = $this->transactionRepository->findBy($orderId, $orderInvoiceId);

        $this->assertEquals('complete', $result[0]['transaction_status']);
        $this->assertEquals('100000001', $order->getIncrementId());
        $this->assertEquals('processing', $order->getState());
        $this->assertEquals('processing', $order->getStatus());
    }
}
